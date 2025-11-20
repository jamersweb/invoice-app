<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->orderBy('scheduled_at', 'desc')->paginate(20);

        return Inertia::render('Forfaiting/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'recipient_type' => 'nullable|string|max:255',
            'recipient_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        Notification::create($validated);

        return redirect()->back()->with('success', 'Notification created successfully');
    }

    public function updateStatus(Request $request, Notification $notification)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,sent,failed',
        ]);

        $notification->update([
            'status' => $validated['status'],
            'sent_at' => $validated['status'] === 'sent' ? now() : $notification->sent_at,
        ]);

        return redirect()->back()->with('success', 'Notification status updated successfully');
    }
}

