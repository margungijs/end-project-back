<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Template extends Model
{
    use HasFactory, HasTags;

    protected $fillable = [
        'user_id',
        'questions',
        'title',
        'description',
        'likes',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
