<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\MeetingAccount;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MeetingAccountController extends Controller
{
    /**
     * List all meeting accounts.
     */
    public function index()
    {
        $accounts = MeetingAccount::with('creator:id,username,email')->get();

        return response()->json([
            'message'  => 'Meeting accounts retrieved successfully',
            'count'    => $accounts->count(),
            'accounts' => $accounts,
        ]);
    }

    /**
     * Create a new meeting account.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'provider'      => 'required|string|in:zoom',
            'account_id'    => 'required|string|max:255',
            'client_id'     => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $data['created_by'] = Auth::id();

        $account = MeetingAccount::create($data);

        DatabaseLog::logAction('create', MeetingAccount::class, $account->id, "Meeting account '{$account->name}' ({$account->provider}) created");

        return response()->json([
            'message' => 'Meeting account created successfully',
            'account' => $account,
        ], 201);
    }

    /**
     * Show a single meeting account (credentials hidden via $hidden).
     */
    public function show($id)
    {
        $account = MeetingAccount::with('creator:id,username,email')->find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        return response()->json([
            'message' => 'Meeting account retrieved successfully',
            'account' => $account,
        ]);
    }

    /**
     * Update a meeting account.
     */
    public function update(Request $request, $id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string|max:255',
            'provider'      => 'sometimes|string|in:zoom',
            'account_id'    => 'sometimes|string|max:255',
            'client_id'     => 'sometimes|string|max:255',
            'client_secret' => 'sometimes|string|max:255',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $account->update($validator->validated());

        DatabaseLog::logAction('update', MeetingAccount::class, $account->id, "Meeting account '{$account->name}' updated");

        return response()->json([
            'message' => 'Meeting account updated successfully',
            'account' => $account->fresh(),
        ]);
    }

    /**
     * Delete a meeting account.
     */
    public function destroy($id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        $accountName = $account->name;
        $account->delete();

        DatabaseLog::logAction('delete', MeetingAccount::class, $id, "Meeting account '{$accountName}' deleted");

        return response()->json(['message' => 'Meeting account deleted successfully']);
    }

    /**
     * Test the credentials by attempting to fetch a Zoom access token.
     */
    public function test(ZoomService $zoom, $id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        try {
            $token = $zoom->getAccessToken($account);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Credentials test failed: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'Credentials are valid — access token obtained successfully',
        ]);
    }

    /**
     * Check the Zoom API directly for any meetings on this account that overlap
     * a given date + time window.
     *
     * GET /api/admin/meeting-accounts/{id}/check-meetings
     * Query params:
     *   date     string (Y-m-d)  required — in the admin's timezone
     *   time     string (H:i)    required — in the admin's timezone
     *   duration integer (min)   optional — window length to check, default 60
     */
    public function checkMeetings(Request $request, ZoomService $zoom, $id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date'     => 'required|date_format:Y-m-d',
            'time'     => 'required|date_format:H:i',
            'duration' => 'sometimes|integer|min:1|max:1440',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $timezone = Auth::user()->effective_timezone;
        $duration = (int) $request->input('duration', 60);

        // Convert the admin's local date+time to UTC for comparison
        $checkStart = TimezoneHelper::toUtc($request->date, $request->time, $timezone);
        $checkEnd   = $checkStart->copy()->addMinutes($duration);

        try {
            $meetings = $zoom->listMeetings($account);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Zoom API call failed: ' . $e->getMessage()], 502);
        }

        $overlapping = collect($meetings)
            ->filter(function ($meeting) use ($checkStart, $checkEnd) {
                if (empty($meeting['start_time']) || empty($meeting['duration'])) {
                    return false;
                }
                $mStart = Carbon::parse($meeting['start_time'])->utc();
                $mEnd   = $mStart->copy()->addMinutes((int) $meeting['duration']);

                return $mStart->lt($checkEnd) && $mEnd->gt($checkStart);
            })
            ->values()
            ->map(fn($m) => [
                'zoom_meeting_id' => $m['id'],
                'topic'           => $m['topic'] ?? null,
                'start_time'      => $m['start_time'],
                'duration'        => $m['duration'],
                'join_url'        => $m['join_url'] ?? null,
            ]);

        $checkedFrom  = TimezoneHelper::fromUtc($checkStart, $timezone);
        $checkedUntil = TimezoneHelper::fromUtc($checkEnd, $timezone);

        return response()->json([
            'message'       => $overlapping->isEmpty()
                ? 'No meetings found in this time window'
                : 'Meetings found in this time window',
            'account_id'    => $account->id,
            'account_name'  => $account->name,
            'checked_from'  => $checkedFrom['date'] . ' ' . $checkedFrom['time'],
            'checked_until' => $checkedUntil['date'] . ' ' . $checkedUntil['time'],
            'timezone'      => $timezone,
            'meeting_count' => $overlapping->count(),
            'meetings'      => $overlapping,
        ]);
    }
}
