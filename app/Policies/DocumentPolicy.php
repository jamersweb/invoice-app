<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function review(User $user, Document $document): bool
    {
        return $user->hasRole(['Admin','Analyst']) || $user->can('review_documents');
    }

    public function assign(User $user, Document $document): bool
    {
        return $user->hasRole(['Admin','Analyst']);
    }
}


