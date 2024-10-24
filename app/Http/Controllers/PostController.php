<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public static function store(Request $request){
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'max:225'],
        ]);

        Post::create([
            'title' => $request->title,
            'answers' => $request->answers,
            'template' => $request->template,
            'user_id' => $request->user()->id,
            'views' => 0
        ]);

        return response()->noContent();
    }
}
