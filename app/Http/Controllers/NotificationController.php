<?php
namespace App\Http\Controllers;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }
    
    /**
     * Display user's notifications.
     */
    public function index()
    {
        $notifications = UserNotification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('notifications.index', compact('notifications'));
    }
    
    /**
     * Get user's unread notification count (for AJAX requests).
     */
    public function getUnreadCount()
    {
        try {
            $user_id = Auth::id();
            
            $count = UserNotification::where('user_id', $user_id)
                ->where('is_read', false)
                ->count();
                
            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting unread count: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get user's recent notifications for dropdown (for AJAX requests).
     */
    public function getRecent()
    {
        try {
            $user_id = Auth::id();
            
            // Get recent notifications
            $notifications = UserNotification::where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            
            // Format notifications for response
            $formatted = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'content' => $notification->content,
                    'icon_class' => $notification->icon_class ?: 'mdi mdi-bell',
                    'color_class' => $notification->color_class ?: 'bg-primary',
                    'image' => $notification->image,
                    'time_ago' => $notification->created_at->diffForHumans(),
                    'is_read' => (bool)$notification->is_read,
                    'action_url' => $notification->action_url,
                ];
            });
            
            // Get unread count
            $unreadCount = UserNotification::where('user_id', $user_id)
                ->where('is_read', false)
                ->count();
                
            return response()->json([
                'success' => true,
                'notifications' => $formatted,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting recent notifications: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent notifications',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = UserNotification::where('user_id', Auth::id())
                ->findOrFail($id);
                
            $success = $notification->markAsRead();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => $success
                ]);
            }
            
            if ($request->has('redirect') && $notification->action_url) {
                return redirect($notification->action_url);
            }
            
            return back()->with('success', 'Notification marked as read.');
        } catch (\Exception $e) {
            Log::error("Error marking notification as read: " . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark notification as read',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
            
            return back()->with('error', 'Failed to mark notification as read.');
        }
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $success = $this->notificationService->markAllAsRead(Auth::user());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => $success
                ]);
            }
            
            return back()->with('success', 'All notifications marked as read.');
        } catch (\Exception $e) {
            Log::error("Error marking all notifications as read: " . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark all notifications as read',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
            
            return back()->with('error', 'Failed to mark all notifications as read.');
        }
    }
    
    /**
     * Health check endpoint for debugging
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toDateTimeString(),
            'auth' => Auth::check() ? 'authenticated' : 'unauthenticated',
            'user_id' => Auth::id(),
        ]);
    }
}