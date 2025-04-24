<?php

namespace App\Http\Controllers;

use App\Models\PostRating;
use App\Models\TemplateRating;
use Illuminate\Http\Request;
use Spatie\Tags\Tag;
use App\Models\User;
use App\Models\Post;
use App\Models\Template;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['results' => []]);
        }

        $locale = app()->getLocale();

        $users = User::where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'image')
            ->limit(5)
            ->get();

        $posts = Post::where('title', 'LIKE', "%{$query}%")
            ->whereHas('user', fn($q) => $q->where('privacy', 0))
            ->where('privacy', 0)
            ->with('user:id,name,image')
            ->select('id', 'title', 'user_id', 'created_at')
            ->limit(5)
            ->get();

        $templates = Template::where('title', 'LIKE', "%{$query}%")
            ->whereHas('user', fn($q) => $q->where('privacy', 0))
            ->with('user:id,name,image')
            ->select('id', 'title', 'user_id', 'created_at')
            ->limit(5)
            ->get();

        $tags = Tag::where("name->{$locale}", 'LIKE', "%{$query}%")
            ->select('id', 'name')
            ->limit(5)
            ->get();

        $taggedPosts = Post::withAnyTags([$query])
            ->whereHas('user', fn($q) => $q->where('privacy', 0))
            ->where('privacy', 0)
            ->with('user:id,name,image')
            ->select('id', 'title', 'user_id', 'created_at')
            ->limit(5)
            ->get();

        $taggedTemplates = Template::withAnyTags([$query])
            ->whereHas('user', fn($q) => $q->where('privacy', 0))
            ->with('user:id,name,image')
            ->select('id', 'title', 'user_id', 'created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'results' => [
                'users' => $users,
                'posts' => $posts,
                'templates' => $templates,
                'tags' => $tags,
                'tagged_posts' => $taggedPosts,
                'tagged_templates' => $taggedTemplates,
            ]
        ]);
    }

    public static function searchSpecific(Request $request)
    {
        $query = $request->input('query');
        $sections = $request->input('sections', []);

        $likedPostIds = PostRating::where('user_id', $request->user()->id)->pluck('post_id');
        $likedTemplateIds = TemplateRating::where('user_id', $request->user()->id)->pluck('template_id');


        $results = [];

        if (in_array('users', $sections)) {
            $users = User::where('name', 'like', "%$query%")
                ->take(10)
                ->get();

            $usersWithFriendship = $users->map(function ($user) use ($request) {
                $user->friendship_status = $request->user()->getFriendshipStatus($user->id);
                return $user;
            });

            $results['users'] = $usersWithFriendship;
        }


        if (in_array('posts', $sections)) {
            $results['posts'] = Post::where('title', 'like', "%$query%")
                ->with(['user:id,name,image', 'templateUsed', 'tags'])
                ->take(10)
                ->get()
                ->map(function ($post) use ($likedPostIds) {
                    $post->liked = in_array($post->id, $likedPostIds->toArray());
                    return $post;
                });
        }

        if (in_array('templates', $sections)) {
            $results['templates'] = Template::where('title', 'like', "%$query%")
                ->with(['user:id,name,image', 'tags'])
                ->take(10)
                ->get()
                ->map(function ($template) use ($likedTemplateIds) {
                    $template->liked = in_array($template->id, $likedTemplateIds->toArray());
                    return $template;
                });
        }

//        if (in_array('tags', $sections)) {
//            $tags = Tag::where('name->en', 'like', "%$query%")
//                ->take(10)
//                ->get();
//            $results['tags'] = $tags;
//
//            if ($tags->count() > 0) {
//                $tagIds = $tags->pluck('id')->toArray();
//
//                if (in_array('tagged_posts', $sections)) {
//                    $results['tagged_posts'] = Post::withAnyTags($tagIds)
//                        ->with(['user:id,name,image', 'templateUsed', 'tags'])
//                        ->take(10)
//                        ->get();
//                }
//
//                if (in_array('tagged_templates', $sections)) {
//                    $results['tagged_templates'] = Template::withAnyTags($tagIds)
//                        ->with(['user:id,name,image', 'tags'])
//                        ->take(10)
//                        ->get();
//                }
//            }
//        }

        if (in_array('tagged_posts', $sections) && empty($results['tagged_posts'])) {
            $results['tagged_posts'] = Post::withAnyTags([$query])
                ->with(['user:id,name,image', 'templateUsed', 'tags'])
                ->take(10)
                ->get()
                ->map(function ($post) use ($likedPostIds) {
                    $post->liked = in_array($post->id, $likedPostIds->toArray());
                    return $post;
                });
        }

        if (in_array('tagged_templates', $sections) && empty($results['tagged_templates'])) {
            $results['tagged_templates'] = Template::withAnyTags([$query])
                ->with(['user:id,name,image', 'tags'])
                ->take(10)
                ->get()
                ->map(function ($template) use ($likedTemplateIds) {
                    $template->liked = in_array($template->id, $likedTemplateIds->toArray());
                    return $template;
                });
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

}
