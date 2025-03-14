<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'questions',
        'title',
        'description',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
