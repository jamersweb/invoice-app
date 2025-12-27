<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
       use HasFactory;

       protected $fillable = ['customer_id', 'admin_id', 'last_message_at'];

       protected $casts = [
              'last_message_at' => 'datetime',
       ];

       public function customer(): BelongsTo
       {
              return $this->belongsTo(User::class, 'customer_id');
       }

       public function admin(): BelongsTo
       {
              return $this->belongsTo(User::class, 'admin_id');
       }

       public function messages(): HasMany
       {
              return $this->hasMany(ChatMessage::class, 'conversation_id');
       }

       public function latestMessage()
       {
              return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
       }
}
