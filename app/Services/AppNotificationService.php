<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AppNotificationService
{
       /**
        * Notify when a new chat message is received.
        */
       public function notifyNewChatMessage($message)
       {
              $conversation = $message->conversation;
              $sender = $message->sender;
              $recipient = $sender->id === $conversation->customer_id ? $conversation->admin : $conversation->customer;

              if (!$recipient) {
                     // If admin hasn't joined, notify all admins
                     $recipients = User::role('Admin')->get();
              } else {
                     $recipients = [$recipient];
              }

              foreach ($recipients as $to) {
                     Notification::create([
                            'type' => 'new_chat_message',
                            'title' => 'New Chat Message',
                            'message' => "You have a new message from {$sender->name}: " . substr($message->message, 0, 50) . "...",
                            'recipient_type' => 'user',
                            'recipient_id' => $to->id,
                            'status' => 'pending',
                            'metadata' => [
                                   'conversation_id' => $conversation->id,
                                   'message_id' => $message->id,
                            ],
                     ]);

                     // Optional: Send email
                     // Mail::to($to->email)->send(new NewChatMessageMail($message));
              }
       }

       /**
        * Notify when an investment is confirmed.
        */
       public function notifyInvestmentConfirmed($investment)
       {
              $investor = $investment->investor;
              if (!$investor)
                     return;

              Notification::create([
                     'type' => 'investment_confirmed',
                     'title' => 'Investment Confirmed',
                     'message' => "Your investment of {$investment->amount} {$investment->currency} for deal {$investment->transaction->transaction_number} has been confirmed.",
                     'recipient_type' => 'user',
                     'recipient_id' => $investor->id,
                     'status' => 'pending',
                     'metadata' => [
                            'investment_id' => $investment->id,
                     ],
              ]);
       }

       /**
        * Notify when profit allocation is finalized.
        */
       public function notifyProfitAllocationFinalized($transaction)
       {
              $allocations = $transaction->profitAllocations;

              foreach ($allocations as $allocation) {
                     // Find user by name if investor_id is missing, but better to use investor_id
                     $investor = User::where('name', $allocation->investor_name)->first();
                     if (!$investor)
                            continue;

                     Notification::create([
                            'type' => 'profit_finalized',
                            'title' => 'Profit Allocation Finalized',
                            'message' => "Profit allocation for deal {$transaction->transaction_number} has been finalized. Your share: {$allocation->individual_profit}.",
                            'recipient_type' => 'user',
                            'recipient_id' => $investor->id,
                            'status' => 'pending',
                            'metadata' => [
                                   'transaction_id' => $transaction->id,
                                   'allocation_id' => $allocation->id,
                            ],
                     ]);
              }
       }
}
