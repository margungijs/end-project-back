<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
//        if ($request->user()->hasVerifiedEmail()) {
//            return redirect()->intended(
//                config('app.frontend_url').'/dashboard?verified=1'
//            );
//        }
//
//        if ($request->user()->markEmailAsVerified()) {
//            event(new Verified($request->user()));
//        }
//
//        return redirect()->intended(
//            config('app.frontend_url').'/dashboard?verified=1'
//        );

        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid verification link.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url').'/auth'
            );
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return redirect()->intended(
            config('app.frontend_url').'/auth'
        );
    }
}
