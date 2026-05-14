<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AppNotificationController extends Controller
{
    #[OA\Get(
        path: "/api/notifications",
        summary: "Get all notifications for authenticated user",
        tags: ["Notifications"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $notifications = AppNotification::where('user_id', Auth::id())
            ->with('causer')
            ->orderBy('created_at', 'desc');

        if (isset($request->limit)) {
            $limit = $request->get('limit');
            $notifications = $notifications->paginate($limit);
        } else {
            $notifications = $notifications->get();
        }

        return response()->json($notifications);
    }

    #[OA\Post(
        path: "/api/notifications/{id}/mark-as-read",
        summary: "Mark a notification as read",
        tags: ["Notifications"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function markAsRead($id)
    {
        $notification = AppNotification::where('user_id', Auth::id())->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    #[OA\Post(
        path: "/api/notifications/mark-all-as-read",
        summary: "Mark all notifications as read",
        tags: ["Notifications"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function markAllAsRead()
    {
        AppNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    #[OA\Get(
        path: "/api/notifications/unread-count",
        summary: "Get unread notifications count",
        tags: ["Notifications"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function unreadCount()
    {
        $count = AppNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    #[OA\Delete(
        path: "/api/notifications/{id}",
        summary: "Delete a notification",
        tags: ["Notifications"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function destroy($id)
    {
        $notification = AppNotification::where('user_id', Auth::id())->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }
}
