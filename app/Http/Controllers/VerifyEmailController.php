<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid verification link.']);
        }
        
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(config('app.frontend_url'));
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return redirect()->intended(config('app.frontend_url'));
    }
}
