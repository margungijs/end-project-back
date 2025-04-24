<?php

namespace App\Http\Controllers;

use App\Models\PostRating;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostLimit;
use Illuminate\Support\Facades\DB;
use App\Models\Template;

class PostController extends Controller
{
    public static function store(Request $request) {
        $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:posts,title,NULL,id,user_id,' . $request->user()->id],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'between:1,225', ],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'privacy' => ['required', 'integer', 'in:0,1']
        ]);

        return DB::transaction(function () use ($request) {
//            $post = Post::create([
//                'title' => $request->title,
//                'answers' => $request->answers,
//                'template' => $request->template,
//                'user_id' => $request->user()->id,
//                'views' => 0,
//                'image' => null,
//                'privacy' => $request->privacy
//            ]);

//            if ($request->filled('tags')) {
//                $post->syncTags($request->input('tags'));
//            }

//            $postLimit = PostLimit::where('user_id', $request->user()->id)->latest()->first();
//            if ($postLimit) {
//                $postLimit->update([
//                    'posts' => array_merge($postLimit->posts ?? [], [$post->id])
//                ]);
//            }

            return response()->json([
                'status' => 201,
//                'id' => $post->id,
            ], 201);
        });
    }


    public static function postImage(Request $request) {
        $request->validate([
            'image' => ['required', 'image', 'max:2048'],
        ]);

        $post = Post::where('id', $request->id)->first();

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        return DB::transaction(function () use ($post, $request) {
            if ($post->image) {
                unlink(storage_path('app/public/' . $post->image));
            }

            $imagePath = $request->file('image')->store('images', 'public');

            if (!$imagePath) {
                $post->delete();
                return response()->json(['error' => 'Image upload failed, post deleted'], 422);
            }

            if ($post->image) {
                @unlink(storage_path('app/public/' . $post->image));
            }

            $post->update(['image' => $imagePath]);

            return response()->noContent();
        });
    }


    public static function like(Request $request)
    {
        $rating = PostRating::where('post_id', $request->id)->first();
        $post = Post::where('id', $request->id)->first();

        if(!$rating){
            PostRating::create([
                'post_id' => $request->id,
                'user_id' => $request->user()->id
            ]);

            self::likePostTemplate($post->template);
        }else{
            $rating->delete();
            self::likePostTemplate($post->template, false);
        }

        return response()->noContent();
    }

    private static function likePostTemplate(int $templateId, bool $isLike = true)
    {
        $template = Template::where('id', $templateId)->first();

        if ($template) {
            $isLike ? $template->increment('likes') : $template->decrement('likes');
        }
    }

    public static function view(Request $request)
    {
        $post = Post::find($request->id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        $user = $request->user();

        if ($user && $post->user_id == $user->id) {
            return response()->noContent();
        }

        $post->increment('views');

        $template = Template::find($post->template);

        if ($template) {
            $template->increment('views');
        }

        return response()->noContent();
    }


    public function fetch(Request $request)
    {
        $user = $request->user();

        // Fetch the posts with user details
        $posts = $user->posts()->with('user:id,name,image', 'templateUsed', 'tags')->get();

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
