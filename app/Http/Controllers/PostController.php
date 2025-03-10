<?php

namespace App\Http\Controllers;

use App\Models\PostRating;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostLimit;

class PostController extends Controller
{
    public static function store(Request $request){
        $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:posts,title,NULL,id,user_id,' . $request->user()->id],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'max:225'],
        ]);

        $post = Post::create([
            'title' => $request->title,
            'answers' => $request->answers,
            'template' => $request->template,
            'user_id' => $request->user()->id,
            'views' => 0,
        ]);

        $postLimit = PostLimit::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if ($postLimit) {
            $postLimit->update([
                'posts' => array_merge($postLimit->posts ?? [], [$post->id])
            ]);
        }

        return response()->json([
            'status' => 201,
            'id' => $post->id,
        ], 201);
    }

    public static function postImage(Request $request){
        $request->validate([
            'image' => ['image', 'max:2048'],
        ]);

        $post = Post::where('id', $request->id)->first();

        if($post->image){
            unlink(storage_path('app/' . $post->image));
        }

        $post->update([
            'image' => $request->file('image')->store('images', 'public')
        ]);

        return response()->noContent();
    }

    public static function like(Request $request)
    {
        $rating = PostRating::where('post_id', $request->id)->first();

        if(!$rating){
            PostRating::create([
                'post_id' => $request->id,
                'user_id' => $request->user()->id
            ]);
        }else{
            $rating->delete();
        }

        return response()->noContent();
    }

    public function fetch(Request $request){
        $user = $request->user();

        // Fetch the posts with user details
        $posts = $user->posts()->with('user:id,name,image', 'templateUsed')->get();

        $postLimits = PostLimit::where('user_id', $user->id)->get();
        $postLimitsMap = [];
        foreach ($postLimits as $limit) {
            foreach ($limit->posts as $postId) {
                $postLimitsMap[$postId] = $limit->limit;
            }
        }

        $posts->each(function ($post) use ($postLimitsMap) {
            $post->limit = $postLimitsMap[$post->id] ?? null;  // Assign limit or null if none found
        });

        return response()->json([
            'status' => 200,
            'posts' => $posts,
            'limits' => $postLimits,
        ], 200);
    }
}
