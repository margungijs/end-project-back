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
use Spatie\Tags\Tag;

class UserController extends Controller
{
    protected array $tags = [
        "Life", "Love", "Mental Health", "Relationships", "Work", "School", "Money",
        "Fitness", "Food", "Travel", "Fashion", "Beauty", "Art", "Music", "Gaming",
        "Books", "Movies", "Tech", "Business", "Sports", "Politics", "News", "Science",
        "Storytime", "Advice", "Question", "Confession", "Rant", "Meme", "Quote",
        "Photo", "Video", "Tutorial", "Thread", "Challenge", "Review", "Guide",
        "Funny", "Serious", "Wholesome", "Controversial", "Inspiring", "Relatable",
        "Random", "TMI", "Cringe", "Dark", "Feel Good", "Motivational", "Petty",
        "Dating", "Friendship", "Family", "Culture", "Language", "Fandom", "Aesthetic",
        "Spirituality", "Zodiac", "Identity", "LGBTQ+", "Gen Z", "Millennial", "Trad",
        "Morning", "Night", "Weekend", "Holiday", "Throwback", "Now", "2020s", "Trend",
        "Hot Take", "Life Update", "Viral", "AMA", "POV", "Unpopular Opinion",
        "Help", "Vent", "Share", "React", "Learn", "Debate", "Show Off", "Expose",
        "NSFW", "NSFL", "Just Saying", "Low Effort", "Deep", "Not Clickbait"
    ];

    public function fetch(Request $request, $id){
        $user = User::find($id);

        if(!$user){
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        $friendshipStatus = $request->user()->getFriendshipStatus($id);

        $posts = $user->posts()->with('templateUsed')->get();

        return response()->json([
            'user' => $user,
            'status' => $friendshipStatus,
            'posts' => $posts,
            'templates' => $user->templates,
        ], 200);
    }

    public static function feed(Request $request){
        $user = $request->user();
        $friends = $user->friendsAsUser->merge($user->friendsAsFriend);
        $items = [];

        foreach ($friends as $friend) {
            $posts = $friend->posts()
                ->where('privacy', 0)
                ->with(['user:id,name,image', 'templateUsed'])
                ->get();

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
            'name' => ['string', 'between:3,30', 'unique:users,name'],
            'limit' => ['integer', 'between:0,12'],
            'tts' => ['boolean'],
            'privacy' => ['integer', 'in:0,1']
        ]);

        $user = $request->user();

        if($request->name){
            $user->update([
                'name' => $request->name
            ]);
        }

        if ((int)$request->tts !== (int)$user->tts) {
            $user->update([
                'tts' => (int)$request->tts
            ]);
        }

        if((int)$request->privacy !== (int)$user->privacy){
            $user->update([
                'privacy' => (int)$request->privacy
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
        $friends = $user->friendsAsUser->merge($user->friendsAsFriend);
        $filter = $request->get('filter', 'all');

        $page = request()->get('page', 1);
        $perPage = 10;

        $likedPostIds = PostRating::where('user_id', $user->id)->pluck('post_id');
        $postTags = Post::withAnyTags((new UserController)->tags)
            ->whereIn('id', $likedPostIds)->get()->flatMap->tags->pluck('name');

        $likedTemplateIds = TemplateRating::where('user_id', $user->id)->pluck('template_id');
        $templateTags = Template::withAnyTags((new UserController)->tags)
            ->whereIn('id', $likedTemplateIds)->get()->flatMap->tags->pluck('name');

        $userPostCreatedTags = Post::withAnyTags((new UserController)->tags)
            ->where('user_id', $user->id)->get()->flatMap->tags->pluck('name');
        $userTemplateCreatedTags = Template::withAnyTags((new UserController)->tags)
            ->where('user_id', $user->id)->get()->flatMap->tags->pluck('name');

        $userCreatedTags = $userPostCreatedTags->merge($userTemplateCreatedTags)->unique()->values();

        $friendCreatedTags = self::getFriendPostTags($friends);
        $friendLikedTags = self::getFriendLikedTags($friends);
        $friendsTemplateTags = self::getFriendTemplateTags($friends);

        $allFriendsTags = collect($friendCreatedTags)
            ->merge($friendLikedTags)
            ->merge($friendsTemplateTags)
            ->unique()
            ->values();

        $userInterestTags = $postTags->merge($templateTags)->unique()->values();

        $posts = Post::where('user_id', '!=', $user->id)
            ->whereHas('user', fn($q) => $q->where('privacy', 0))
            ->where('privacy', 0)
            ->with(['user:id,name,image', 'templateUsed', 'tags'])
            ->orderByDesc('created_at')
            ->get();

        $templates = Template::where('user_id', '!=', $user->id)
            ->whereHas('user', fn($q) => $q->where('privacy', 0))
            ->with(['user:id,name,image', 'tags'])
            ->orderByDesc('created_at')
            ->get();

        $trendingTags = self::findTrendingTags();
        $templateTrendingTags = self::findTrendingTemplateTags();

        $scoredPosts = $posts->map(function ($post) use
        ($userInterestTags, $userCreatedTags, $friends, $allFriendsTags, $trendingTags, $likedPostIds) {
            $matchCount = $post->tags->pluck('name')->intersect($userInterestTags)->count();
            $tagScore = $matchCount * 0.5;

            $userTagMatchCount = $post->tags->pluck('name')->intersect($userCreatedTags)->count();
            $userTagScore = $userTagMatchCount * 0.4;

            $friendTagMatchCount = $post->tags->pluck('name')->intersect($allFriendsTags)->count();
            $friendTagScore = $friendTagMatchCount * 0.4;

            $trendingTags = $post->tags->sum(function ($tag) use ($trendingTags) {
                return $trendingTags[$tag->name] ?? 0;
            });
            $trendingTagScore = $trendingTags * 0.3;

            $views = $post->views;
            $viewScore = $views * 0.3;

            $likes = PostRating::where('post_id', $post->id)->get()->count();
            $likeScore = $likes * 0.4;

            $hoursAgo = $post->created_at->diffInHours(now());

            $decayRate = 0.001;
            $recencyScore = max(0, 1 - ($decayRate * $hoursAgo));
            $recencyScore = $recencyScore * 0.2;

            $postFriendLikes = self::friendPostLikes($post->id, $friends);
            $postFriendLikesScore = $postFriendLikes * 0.2;

            $lastViewHoursAgo = $post->updated_at->diffInHours(now());

            $lastLike = PostRating::where('post_id', $post->id)
                ->orderByDesc('created_at')
                ->first();

            $lastLikeHoursAgo = $lastLike
                ? $lastLike->created_at->diffInHours(now())
                : 9999;

            $activityDecayRate = 0.001;
            $viewActivityScore = max(0, 1 - ($activityDecayRate *
                        $lastViewHoursAgo)) * 0.1;
            $likeActivityScore = max(0, 1 - ($activityDecayRate *
                        $lastLikeHoursAgo)) * 0.1;

            $activityScore = $viewActivityScore + $likeActivityScore;

            $post->score = $tagScore + $viewScore + $likeScore + $recencyScore +
                $userTagScore + $postFriendLikesScore + $friendTagScore +
                $activityScore + $trendingTagScore;

            $post->liked = in_array($post->id, $likedPostIds->toArray());

            return $post;
        });

        $scoredTemplates = $templates->map(function ($template) use (
            $userInterestTags,
            $userCreatedTags,
            $friends,
            $allFriendsTags,
            $templateTrendingTags,
            $likedTemplateIds
        ) {
            $matchCount = $template->tags->pluck('name')->intersect($userInterestTags)->count();
            $tagScore = $matchCount * 0.5;

            $userTagMatchCount = $template->tags->pluck('name')->intersect($userCreatedTags)->count();
            $userTagScore = $userTagMatchCount * 0.4;

            $friendTagMatchCount = $template->tags->pluck('name')->intersect($allFriendsTags)->count();
            $friendTagScore = $friendTagMatchCount * 0.2;

            $views = $template->views;
            $viewScore = $views * 0.3;

            $likes = TemplateRating::where('template_id', $template->id)->get()->count();
            $likeScore = $likes * 0.4;

            $templateTagScore = $template->tags->sum(function ($tag) use ($templateTrendingTags) {
                return $templateTrendingTags[$tag->name] ?? 0;
            });
            $trendingTagScore = $templateTagScore * 0.1;

            $hoursAgo = $template->created_at->diffInHours(now());

            $decayRate = 0.001;
            $recencyScore = max(0, 1 - ($decayRate * $hoursAgo));
            $recencyScore = $recencyScore * 0.2;

            $templateFriendLikes = self::friendTemplateLikes($template->id, $friends);
            $templateFriendsLikesScore = $templateFriendLikes * 0.2;

            $lastViewHoursAgo = $template->updated_at->diffInHours(now());

            $lastLike = TemplateRating::where('template_id', $template->id)
                ->orderByDesc('created_at')
                ->first();

            $lastLikeHoursAgo = $lastLike
                ? $lastLike->created_at->diffInHours(now())
                : 9999;

            $activityDecayRate = 0.001;
            $viewActivityScore = max(0, 1 - ($activityDecayRate * $lastViewHoursAgo)) * 0.1;
            $likeActivityScore = max(0, 1 - ($activityDecayRate * $lastLikeHoursAgo)) * 0.1;

            $activityScore = $viewActivityScore + $likeActivityScore;

            $template->score = $tagScore + $viewScore + $likeScore + $recencyScore +
                $userTagScore + $friendTagScore + $templateFriendsLikesScore +
                $activityScore + $trendingTagScore;

            $template->liked = in_array($template->id, $likedTemplateIds->toArray());

            return $template;
        });


        $sortedPosts = $scoredPosts->sortByDesc('score')->values();
        $sortedTemplates = $scoredTemplates->sortByDesc('score')->values();

        if($filter == 'posts'){
            $sortedMedia = $sortedPosts;
        }else if($filter == 'templates'){
            $sortedMedia = $sortedTemplates;
        }else{
            $sortedMedia = $sortedPosts->merge($sortedTemplates);
        }

        $paginated = $sortedMedia->forPage($page, $perPage)->values();

        return response()->json([
            'items' => $paginated,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $sortedMedia->count(),
                'last_page' => ceil($sortedMedia->count() / $perPage),
            ]
        ], 200);

    }

    private static function getFriendPostTags($friends){
        $allTags = collect();

        foreach ($friends as $friend) {
            $posts = Post::where('user_id', $friend->id)
                ->where('privacy', 0)
                ->with(['user:id,name,image', 'templateUsed'])
                ->get();

            $postIds = $posts->pluck('id');

            $tags = Post::withAnyTags((new UserController)->tags)
                ->whereIn('id', $postIds)
                ->get()
                ->flatMap
                ->tags
                ->pluck('name');

            $allTags = $allTags->merge($tags);
        }

        return $allTags->unique()->values();
    }

    private static function getFriendTemplateTags($friends){
        $allTags = collect();

        foreach ($friends as $friend) {
            $posts = Template::where('user_id', $friend->id)
                ->with(['user:id,name,image'])
                ->get();

            $postIds = $posts->pluck('id');

            $tags = Template::withAnyTags((new UserController)->tags)
                ->whereIn('id', $postIds)
                ->get()
                ->flatMap
                ->tags
                ->pluck('name');

            $allTags = $allTags->merge($tags);
        }

        return $allTags->unique()->values();
    }

    private static function getFriendLikedTags($friends){
        $allTags = collect();

        foreach ($friends as $friend) {
            $likedPostIds = PostRating::where('user_id', $friend->id)->pluck('post_id');
            $postTags = Post::withAnyTags((new UserController)->tags)
                ->whereIn('id', $likedPostIds)
                ->get()
                ->flatMap
                ->tags
                ->pluck('name');

            $likedTemplateIds = TemplateRating::where('user_id', $friend->id)->pluck('template_id');
            $templateTags = Template::withAnyTags((new UserController)->tags)
                ->whereIn('id', $likedTemplateIds)
                ->get()
                ->flatMap
                ->tags
                ->pluck('name');

            $allTags = $allTags->merge($postTags)->merge($templateTags);
        }

        return $allTags->unique()->values();
    }

    private static function friendPostLikes($postId, $friends)
    {
        $friendIds = $friends->pluck('id');

        return PostRating::where('post_id', $postId)
            ->whereIn('user_id', $friendIds)
            ->get()
            ->count();
    }

    private static function friendTemplateLikes($templateId, $friends){
        $friendIds = $friends->pluck('id');

        return TemplateRating::where('template_id', $templateId)
            ->whereIn('user_id', $friendIds)
            ->get()
            ->count();
    }

    private static function findTrendingTags() {
        return Tag::get()->map(function ($tag) {
            $posts = Post::withAnyTags([$tag->name])
                ->where('created_at', '>=', now()->subDays(14))
                ->get();

            $score = $posts->sum(function ($post) {
                $hoursAgo = $post->created_at->diffInHours(now());
                $decayRate = 0.001;
                return max(0, 1 - ($decayRate * $hoursAgo));
            });

            $tag->trending_score = $score;

            return $tag;
        })->sortByDesc('trending_score')->take(10);
    }

    private static function findTrendingTemplateTags() {
        return Tag::get()->map(function ($tag) {
            $template = Template::withAnyTags([$tag->name])
                ->where('created_at', '>=', now()->subDays(14))
                ->get();

            $score = $template->sum(function ($post) {
                $hoursAgo = $post->created_at->diffInHours(now());
                $decayRate = 0.001;
                return max(0, 1 - ($decayRate * $hoursAgo));
            });

            $tag->trending_score = $score;

            return $tag;
        })->sortByDesc('trending_score')->take(10);
    }



    public static function collection(Request $request)
    {
        $user = $request->user();
        $items = [];

        $likedPostIds = PostRating::where('user_id', $user->id)
            ->pluck('post_id')
            ->toArray();

        $posts = Post::whereIn('id', $likedPostIds)
            ->with(['user:id,name,image', 'templateUsed', 'tags'])
            ->get()
            ->map(function ($post) {
                $post->liked = true;
                return $post;
            })->toArray();

        $likedTemplateIds = TemplateRating::where('user_id', $user->id)
            ->pluck('template_id')
            ->toArray();

        $templates = Template::whereIn('id', $likedTemplateIds)
            ->with('user:id,name,image', 'tags')
            ->get()
            ->map(function ($template) {
                $template->liked = true;
                return $template;
            })->toArray();

        $items = array_merge($items, $posts, $templates);

        return response()->json([
            'items' => [
                $items
            ]
        ], 200);
    }
}
