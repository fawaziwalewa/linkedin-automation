<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'topic',
        'status',
        'preferred_framework'
    ];
}
