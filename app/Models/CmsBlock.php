<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'key','locale','title','body','cta_text','cta_href','image_url','is_active'
    ];
}


