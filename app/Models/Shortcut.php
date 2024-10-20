<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shortcut extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'route',
        'customisation',
        'user_id'
    ];

    protected $casts = [
        'customisation' => 'array'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'user_id'
    ];
}
