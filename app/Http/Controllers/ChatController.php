<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
       public function index(Request $request)
       {
              $user = auth()->user();

              $query = ChatConversation::with([
                     'customer',
                     'admin',
                     'latestMessage'
              ]);

              if ($user->hasRole('Admin')) {
                     // Admins see all conversations
              } else {
                     // Customers see only their own
                     $query->where('customer_id', $user->id);
              }

              $conversations = $query->orderBy('last_message_at', 'desc')->get();

              return Inertia::render('Chat/Index', [
                     'conversations' => $conversations,
                     'selectedId' => $request->query('selected') ? (int) $request->query('selected') : null,
              ]);
       }

       public function start(Request $request)
       {
              $user = auth()->user();
              $customerId = null;
              $adminId = null;

              if ($user->hasRole('Admin')) {
                     $request->validate([
                            'customer_id' => 'required|exists:users,id'
                     ]);
                     $customerId = $request->customer_id;
                     $adminId = $user->id;
              } else {
                     // Customer starts chat with system (first available admin)
                     $customerId = $user->id;
                     $adminId = \App\Models\User::role('Admin')->first()?->id;

                     if (!$adminId) {
                            return redirect()->back()->with('error', 'Support is currently unavailable. Please try again later.');
                     }
              }

              $conversation = ChatConversation::firstOrCreate([
                     'customer_id' => $customerId,
                     'admin_id' => $adminId,
              ]);

              if (!$conversation->last_message_at) {
                     $conversation->update(['last_message_at' => now()]);
              }

              return redirect()->route('chat.index', ['selected' => $conversation->id]);
       }

       public function messages(ChatConversation $conversation)
       {
              $this->authorizeAccess($conversation);

              $messages = $conversation->messages()
                     ->with('sender')
                     ->orderBy('created_at', 'asc')
                     ->get();

              // Mark as read
              $conversation->messages()
                     ->where('sender_id', '!=', auth()->id())
                     ->whereNull('read_at')
                     ->update(['read_at' => now()]);

              return response()->json($messages);
       }

       public function store(Request $request, ChatConversation $conversation)
       {
              $this->authorizeAccess($conversation);

              $validated = $request->validate([
                     'message' => 'required|string',
                     'attachments.*' => 'nullable|file|max:10240',
              ]);

              $attachments = [];
              if ($request->hasFile('attachments')) {
                     foreach ($request->file('attachments') as $file) {
                            $path = $file->store('chat_attachments', 'public');
                            $attachments[] = [
                                   'name' => $file->getClientOriginalName(),
                                   'path' => Storage::url($path),
                            ];
                     }
              }

              $message = ChatMessage::create([
                     'conversation_id' => $conversation->id,
                     'sender_id' => auth()->id(),
                     'message' => $validated['message'],
                     'attachments' => $attachments,
              ]);

              $conversation->update(['last_message_at' => now()]);

              // Notify recipient
              (new \App\Services\AppNotificationService())->notifyNewChatMessage($message);

              return response()->json($message->load('sender'));
       }

       protected function authorizeAccess(ChatConversation $conversation)
       {
              $user = auth()->user();
              if ($user->hasRole('Admin')) {
                     return;
              }

              if ($conversation->customer_id !== $user->id) {
                     abort(403);
              }
       }
}
