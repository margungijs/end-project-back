<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

class Post extends Model
{
    use HasFactory;
    use HasTags;

    protected $fillable = [
        'title',
        'answers',
        'template',
        'user_id',
        'views',
        'image',
        'privacy'
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function templateUsed(){
        return $this->belongsTo(Template::class, 'template', 'id');
    }
}
