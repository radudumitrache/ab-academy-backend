<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications owned by the authenticated teacher, with optional filters.
     */
    public function index(Request $request)
    {
        $query = Notification::where('notification_owner', Auth::id())
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
     * Mark all unseen notifications for the authenticated teacher as seen.
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
        $notification = Notification::where('notification_owner', Auth::id())->find($id);

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
     * Delete a notification owned by the authenticated teacher.
     */
    public function destroy($id)
    {
        $notification = Notification::where('notification_owner', Auth::id())->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }
}
