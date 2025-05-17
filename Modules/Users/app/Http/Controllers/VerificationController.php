<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Models\User;
use Modules\Users\Notifications\VerifyEmailNotification;

class VerificationController extends Controller
{
   public function verify(Request $request, $id, $hash)
    {
        $user = User::find($id);
    
        if (! $user) {
            return response()->json([
                'message' => __('users::verify.messages.user_not_found'),
            ], 404);
        }
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => trans('users::verify.messages.url_invalid')], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => trans('users::verify.messages.email_already_verified')]);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => trans('users::verify.messages.verification_success')]);
    }
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => __('users::verify.messages.user_not_found'),
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('users::verify.messages.email_already_verified'),
            ], 400);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json([
            'message' => __('users::verify.messages.verification_email_resent'),
        ]);
    }
}