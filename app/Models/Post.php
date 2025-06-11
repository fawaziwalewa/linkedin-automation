<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'topic',
        'content',
        'humanized_content',
        'image',
        'framework',
        'status',
    ];
}
