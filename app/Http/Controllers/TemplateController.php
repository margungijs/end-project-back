<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\TemplateRating;

class TemplateController extends Controller
{
    public static function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:50', 'unique:templates,title,NULL,id,user_id,' . $request->user()->id],
            'description' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array'],
            'questions.*' => ['required', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $template = Template::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'questions' => $request->questions
        ]);

        if ($request->filled('tags')) {
            $template->syncTags($request->input('tags'));
        }

        return response()->noContent();
    }

    public static function like(Request $request)
    {
        $rating = TemplateRating::where('template_id', $request->id)->first();

        if(!$rating){
            TemplateRating::create([
                'template_id' => $request->id,
                'user_id' => $request->user()->id
            ]);
        }else{
            $rating->delete();
        }

        return response()->noContent();
    }

    public static function view(Request $request)
    {
        $template = Template::find($request->id);

        $user = $request->user();

        if ($user && $template->user_id == $user->id) {
            return response()->noContent();
        }

        Template::where('id', $request->id)
            ->update([
                'views' => Template::where('id', $request->id)->first()->views + 1
            ]);

        return response()->noContent();
    }

    public static function delete(Request $request)
    {
        Template::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->noContent();
    }

    public static function fetch(Request $request)
    {
        $user = $request->user();

        $ratedTemplateIds = TemplateRating::where('user_id', $user->id)->pluck('template_id')->toArray();

        $templates = Template::whereIn('id', $ratedTemplateIds)
            ->orWhere('id', 1)
            ->orWhere('user_id', $user->id)
            ->distinct()
            ->get();

        return response()->json([
            'status' => 201,
            'templates' => $templates,
        ], 201);
    }
}
