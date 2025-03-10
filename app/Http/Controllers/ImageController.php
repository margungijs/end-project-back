<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function store(Request $request){
        $validation = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if($validation->fails()){
            return response()->json([
                'status' => 422,
                'message' => 'The payload is not formatted correctly',
                'errors' => $validation->errors()
            ], 201);
        }

        $user = $request->user();

        if ($user->image && file_exists(storage_path('app/public/' . $user->image))) {
            unlink(storage_path('app/public/' . $user->image));
        }

        $user->update([
            'image' => $request->file('image')->store('images', 'public')
        ]);

        return response()->noContent();
    }
}
