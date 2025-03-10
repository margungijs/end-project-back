<?php

namespace App\Http\Controllers;

use App\Models\PostLimit;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\PostRating;
use App\Models\TemplateRating;
use App\Models\Post;
use App\Models\Template;

class UserController extends Controller
{
    public function fetch(Request $request, $id){
        $user = User::find($id);

        if(!$user){
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        $friendshipStatus = $request->user()->getFriendshipStatus($id);

        return response()->json([
            'user' => $user,
            'status' => $friendshipStatus,
            'posts' => $user->posts,
            'templates' => $user->templates,
        ], 200);
    }

    public static function feed(Request $request){
        $user = $request->user();
        $friends = $user->friendsAsUser->merge($user->friendsAsFriend);
        $items = [];

        foreach ($friends as $friend) {
            $posts = $friend->posts()->with(['user:id,name,image', 'templateUsed'])->get();

            $postIds = $posts->pluck('id');

            $likedPostIds = PostRating::where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray();

            $posts = $posts->map(function ($post) use ($likedPostIds) {
                $post->liked = in_array($post->id, $likedPostIds);
                return $post;
            })->toArray();

            $templates = $friend->templates()->with('user:id,name,image')->get();

            $templateIds = $templates->pluck('id');

            $likedTemplateIds = TemplateRating::where('user_id', $user->id)
                ->whereIn('template_id', $templateIds)
                ->pluck('template_id')
                ->toArray();

            $templates = $templates->map(function ($template) use ($likedTemplateIds) {
                $template->liked = in_array($template->id, $likedTemplateIds);
                return $template;
            })->toArray();

            $items = array_merge($items, $posts, $templates);
        }

        usort($items, function($a, $b){
            return $b['created_at'] <=> $a['created_at'];
        });

        return response()->json([
            'items' => $items
        ], 200);
    }

    public static function edit(Request $request){
        $request->validate([
            'name' => ['string', 'max:50', 'unique:users,name'],
            'limit' => ['integer', 'between:0,12'],
        ]);

        $user = $request->user();

        if($request->name){
            $user->update([
                'name' => $request->name
            ]);
        }

        if($request->limit !== null){
            $existing = PostLimit::where('user_id', $request->user()->id)
                ->where('limit', $request->limit)
                ->first();

            if($existing){
                DB::table('post_limits')
                    ->where('id', $existing->id)
                    ->update(['created_at' => now()]);
            }else{
                PostLimit::create([
                    'user_id' => $user->id,
                    'limit' => $request->limit,
                    'posts' => []
                ]);
            }
        }

        return response()->noContent();
    }

    public static function explore(Request $request)
    {
        $user = $request->user();
        $items = [];

        $posts = Post::where('user_id', '!=', $user->id)
            ->with(['user:id,name,image', 'templateUsed'])
            ->get();

        $postIds = $posts->pluck('id');
        $likedPostCounts = PostRating::whereIn('post_id', $postIds)
            ->select('post_id', DB::raw('count(*) as total_likes'))
            ->groupBy('post_id')
            ->pluck('total_likes', 'post_id');

        $userLikedPostIds = PostRating::where('user_id', $user->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->toArray();

        $posts = $posts->map(function ($post) use ($likedPostCounts, $userLikedPostIds) {
            $post->total_likes = $likedPostCounts->get($post->id, 0);
            $post->liked = in_array($post->id, $userLikedPostIds);
            return $post;
        })->toArray();

        $templates = Template::where('user_id', '!=', $user->id)
            ->with('user:id,name,image')
            ->get();

        $templateIds = $templates->pluck('id');
        $likedTemplateCounts = TemplateRating::whereIn('template_id', $templateIds)
            ->select('template_id', DB::raw('count(*) as total_likes'))
            ->groupBy('template_id')
            ->pluck('total_likes', 'template_id');

        $userLikedTemplateIds = TemplateRating::where('user_id', $user->id)
            ->whereIn('template_id', $templateIds)
            ->pluck('template_id')
            ->toArray();

        $templates = $templates->map(function ($template) use ($likedTemplateCounts, $userLikedTemplateIds) {
            $template->total_likes = $likedTemplateCounts->get($template->id, 0);
            $template->liked = in_array($template->id, $userLikedTemplateIds);
            return $template;
        })->toArray();

        $items = array_merge($items, $posts, $templates);

        usort($items, function ($a, $b) {
            return $b['total_likes'] <=> $a['total_likes'];
        });

        return response()->json([
            'items' => $items
        ], 200);
    }

    public static function collection(Request $request)
    {
        $user = $request->user();
        $items = [];

        $likedPostIds = PostRating::where('user_id', $user->id)
            ->pluck('post_id')
            ->toArray();

        $posts = Post::whereIn('id', $likedPostIds)
            ->with(['user:id,name,image', 'templateUsed'])
            ->get()
            ->map(function ($post) {
                $post->liked = true;
                return $post;
            })->toArray();

        $likedTemplateIds = TemplateRating::where('user_id', $user->id)
            ->pluck('template_id')
            ->toArray();

        $templates = Template::whereIn('id', $likedTemplateIds)
            ->with('user:id,name,image')
            ->get()
            ->map(function ($template) {
                $template->liked = true;
                return $template;
            })->toArray();

        $items = array_merge($items, $posts, $templates);

        return response()->json([
            'items' => $items
        ], 200);
    }

}
