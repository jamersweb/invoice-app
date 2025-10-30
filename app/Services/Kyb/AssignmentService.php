<?php

namespace App\Services\Kyb;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AssignmentService
{
    public function nextAnalystId(): ?int
    {
        $analystIds = User::role('Analyst')->pluck('id')->all();
        if (empty($analystIds)) {
            return null;
        }
        $key = 'kyb_round_robin_index';
        $index = Cache::increment($key);
        if ($index === 1) {
            Cache::put($key, 0, 86400);
        }
        $i = ((int) Cache::get($key, 0)) % count($analystIds);
        return $analystIds[$i];
    }
}


