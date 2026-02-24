<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user, with optional filters.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Notification::where('notification_owner', $userId)
            ->orderByDesc('notification_time');

        if ($request->has('is_seen')) {
            $query->filterIsSeen(filter_var($request->is_seen, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('notification_type')) {
            $query->where('notification_type', $request->notification_type);
        }

        if ($request->filled('notification_source')) {
            $query->where('notification_source', $request->notification_source);
        }

        if ($request->filled('notification_time')) {
            $query->filterNotificationTime($request->notification_time);
        }

        return response()->json([
            'message'       => 'Notifications retrieved successfully',
            'notifications' => $query->get(),
        ]);
    }

    /**
     * Manually create a notification.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'notification_owner'   => 'required|integer|exists:users,id',
                'notification_message' => 'required|string',
                'notification_time'    => 'nullable|date',
                'notification_source'  => 'nullable|in:' . implode(',', Notification::SOURCES),
                'notification_type'    => 'nullable|in:' . implode(',', Notification::TYPES),
            ]);

            $validated['notification_time'] = $validated['notification_time'] ?? now();
            $validated['is_seen'] = false;

            $notification = Notification::create($validated);

            return response()->json([
                'message'      => 'Notification created successfully',
                'notification' => $notification,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    /**
     * Mark all unseen notifications for the authenticated user as read.
     */
    public function markAllSeen()
    {
        Notification::where('notification_owner', Auth::id())
            ->where('is_seen', false)
            ->update(['is_seen' => true]);

        return response()->json(['message' => 'All notifications marked as seen']);
    }

    /**
     * Mark a single notification as seen.
     */
    public function markSeen($id)
    {
        $notification = Notification::where('notification_owner', Auth::id())
            ->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->update(['is_seen' => true]);

        return response()->json([
            'message'      => 'Notification marked as seen',
            'notification' => $notification,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $notification = Notification::where('notification_owner', Auth::id())
            ->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }
}
