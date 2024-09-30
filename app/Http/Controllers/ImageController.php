<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

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

        $name = User::find($request->user()->name);

        $image = $request->file('image');

        $imageName = '' . $name . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        $image->storeAs('public/images', $imageName);

        $user = User::find($request->user()->id);

        $user->image = $imageName;

        return response()->json([
            'status' => 201,
            'message' => 'Image uploaded successfully',
            'image' => $imageName
        ], 201);
    }
}
