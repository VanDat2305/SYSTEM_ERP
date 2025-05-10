<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Users\Models\User;

class PasswordResetController extends Controller
{
    // Gửi link reset password
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' =>  trans('validation.required', ['attribute' => trans('users::attr.users.email')]),
            'email.email' =>  trans('validation.email', ['attribute' => trans('users::attr.users.email')]),
            'email.exists' =>  trans('validation.exists', ['attribute' => trans('users::attr.users.email')]),
        ]);
        
        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['error' => __($status)], 422);
    }
    
    // Xác minh token
    public function verifyToken(Request $request, $token)
    {
        $email = $request->query('email');
        $rawToken = $request->token; // Token chưa mã hóa từ URL
        // Lấy record token từ database
        $tokenRecord = DB::table('password_reset_tokens')
                        ->where('email', $email)
                        ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => __('passwords.not_token')], 422);
        }

        $tokenCreatedAt = Carbon::parse($tokenRecord->created_at);
        $expirationTime = now()->subMinutes(config('auth.passwords.users.expired', 60)); // Mặc định 60 phút

        if ($tokenCreatedAt->lt($expirationTime)) {
            // Xóa token hết hạn
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();
                
            return response()->json(['error' => 'token_expired'], 422);
        }


        // So sánh token từ request với token đã hash trong database
        $tokenExists = Hash::check($rawToken, $tokenRecord->token);
        
        return $tokenExists
            ? response()->json(['success' => true,"valid"=> true])
            : response()->json(
                [
                    'success' => false,
                    'message' => __('passwords.invalid_token')
                ], 422);
    }
    
    // Đặt lại mật khẩu
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['error' => __($status)], 422);
    }
}