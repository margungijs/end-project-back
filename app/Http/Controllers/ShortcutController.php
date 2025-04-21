<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nette\Schema\ValidationException;
use App\Models\Shortcut;

class ShortcutController extends Controller
{
    public static function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:50', 'unique:shortcuts,name,NULL,id,user_id,' . $request->user()->id],
                'route' => ['required', 'string', 'max:100', 'unique:shortcuts,route,NULL,id,user_id,' . $request->user()->id],
                'icon' => ['required', 'numeric', 'between:1,30'],
                'color' => ['required', 'string', 'max:50'],
                'hover_color' => ['required', 'string', 'max:50']
            ]);

            Shortcut::create([
                'name' => $request->name,
                'route' => $request->route,
                'user_id' => $request->user()->id,
                'customisation' => [
                    'icon' => $request->icon,
                    'color' => $request->color,
                    'hover_color' => $request->hover_color
                ]
            ]);

            return response()->noContent();
        }catch (ValidationException $e){
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public static function delete(Request $request)
    {
        Shortcut::where('id', $request->id)->delete();

        return response()->noContent();
    }
}
