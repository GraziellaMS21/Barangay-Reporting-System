<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationsController extends Controller
{
    /**
     * Show the notifications page.
     */
    public function index(): \Illuminate\View\View
    {
        $notifications = AdminNotification::latest()->paginate(20);
        return view('admin.notifications', compact('notifications'));
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(AdminNotification $notification): \Illuminate\Http\RedirectResponse
    {
        $notification->update(['read' => true]);
        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(): \Illuminate\Http\RedirectResponse
    {
        AdminNotification::where('read', false)->update(['read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Send a test notification email to the logged-in admin.
     */
    public function sendTestEmail(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user  = $request->user();
        $email = $user?->email ?? 'admin@example.com';

        Mail::raw(
            "Hello {$user?->name},\n\nThis is a test notification from the Barangay Reporting System (Phase 12 – Notifications module).\n\nIf you received this, your notification setup is working correctly.",
            function ($msg) use ($email, $user) {
                $msg->to($email, $user?->name ?? 'Admin')
                    ->subject('[Barangay Reports] Test Notification');
            }
        );

        // Log the test notification in DB
        AdminNotification::create([
            'type'    => 'test',
            'message' => "Test email sent to {$email}.",
        ]);

        return back()->with('success', "Test email sent to {$email}. Check your inbox.");
    }

    /**
     * Delete a notification.
     */
    public function destroy(AdminNotification $notification): \Illuminate\Http\RedirectResponse
    {
        $notification->delete();
        return back()->with('success', 'Notification deleted.');
    }
}
