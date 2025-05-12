<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Users\Models\UserTwoFactorCode;
use PragmaRX\Google2FA\Google2FA;

class OtpChallengeController extends Controller
{
    public function store(Request $request)
    {
        if (RateLimiter::tooManyAttempts('two-factor', 5)) {
            $seconds = RateLimiter::availableIn('two-factor');
            return response()->json([
                'message' => __('2fa.too_many_attempts', ['seconds' => $seconds]),
            ], 429);
        }
        $request->validate([
            'code' => 'required_without:recovery_code|string|nullable',
            'recovery_code' => 'required_without:code|string|nullable',
        ], [
            'code.required_without' => __('2fa.code_required_without'),
            'code.string' => __('2fa.code_string'),
            'recovery_code.string' => __('2fa.recovery_code_string'),
            'recovery_code.required_without' => __('2fa.recovery_code_required_without'),
        ]);
        
        $user = Auth::guard('sanctum')->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('api-token')->plainTextToken;
        // Lấy thông tin 2FA từ bảng riêng
        $twoFactor = UserTwoFactorCode::where('user_id', $user->id)->first();

        if (!$twoFactor) {
            return response()->json(['message' => __("2fa.not_set_up") ], 422);//'2FA not set up for this user'
        }
                    
        if ($request->has('code')) {
            $google2fa = new Google2FA();
            $secretKey = decrypt($twoFactor->two_factor_secret);
            
            $valid = $google2fa->verifyKey($secretKey, $request->code);
            
            if (!$valid) {
                
                return response()->json(['message' => __("2fa.invalid_code")], 422);
            }
            

            RateLimiter::clear('two-factor');
            
            return response()->json([
                'message' => '2FA verification successful',
                'token' => $token,
            ]);
        }
        
        if ($request->has('recovery_code')) {
            $codes = $user->twoFactorCodes()->first();
            $recoveryCodes = json_decode(decrypt($codes->two_factor_recovery_codes));
            
            if (!in_array($request->recovery_code, $recoveryCodes)) {
                return response()->json(['message' => __("2fa.invalid_reconvery_code")], 422);
            }
            // Xóa recovery code đã sử dụng
            $updatedCodes = array_diff($recoveryCodes, [$request->recovery_code]);
            $updatedCodes = array_values($updatedCodes);

            $codes->update([
                'two_factor_recovery_codes' => encrypt(json_encode($updatedCodes))
            ]);
            
            
            return response()->json([
                'message' => __("2fa.recovery_accepted"),
                'token' => $token,
            ]);
        }
        
        return response()->json(['message' => __("2fa.recovery_required")], 422);
    }
}