<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactRequest::with('assignedUser');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%');
            });
        }

        $contactRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Forfaiting/ContactRequests/Index', [
            'contactRequests' => $contactRequests,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        ContactRequest::create($validated);

        return redirect()->back()->with('success', 'Contact request submitted successfully');
    }

    public function updateStatus(Request $request, ContactRequest $contactRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,resolved,archived',
            'admin_notes' => 'nullable|string',
        ]);

        $contactRequest->update($validated);

        return redirect()->back()->with('success', 'Contact request updated successfully');
    }

    public function assign(Request $request, ContactRequest $contactRequest)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $contactRequest->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', 'Contact request assigned successfully');
    }
}

