<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Users\Notifications\TwoFactorStatusChanged;

class TwoFactorAuthController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return response()->json(['message' => __("2fa.already_enabled")], 400);
        }

        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();

        // Lưu secret key tạm thời vào session
        Cache::put('2fa_temp_' . $user->id, $secretKey, now()->addMinutes(15));

        return response()->json([
            'secret' => $secretKey,
            'qr_code' => $this->generateQrCode($user->email, $secretKey)
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $cacheKey = '2fa_temp_' . $user->id;
        $secretKey = Cache::get($cacheKey);

        // Kiểm tra secret key tồn tại
        if (!$secretKey) {
            return response()->json(['message' => __("2fa.secret_key_missing_or_expired")], 422);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secretKey, $request->code);

        if (!$valid) {
            return response()->json(['message' => __("2fa.invalid_code")], 422);
        }

        // Mã hóa dữ liệu trước khi lưu
        $encryptedSecret = encrypt($secretKey);
        $recoveryCodes = $this->generateRecoveryCodes();
        $encryptedRecoveryCodes = encrypt(json_encode($recoveryCodes));

        // Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
        DB::transaction(function () use ($user, $encryptedSecret, $encryptedRecoveryCodes) {
            // Cập nhật thông tin user
            $user->update([
                'two_factor_enabled' => true,
            ]);

            // Tạo hoặc cập nhật bản ghi two factor codes
            $user->twoFactorCodes()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'two_factor_secret' => $encryptedSecret,
                    'two_factor_recovery_codes' => $encryptedRecoveryCodes,
                    'two_factor_confirmed_at' => now()
                ]
            );
        });

        // Xóa cache sau khi xử lý thành công
        Cache::forget($cacheKey);

        // Gửi thông báo
        $user->notify(new TwoFactorStatusChanged(
            enabled: true,
            ip: $request->ip(),
            time: now()->toDateTimeString()
        ));

        return response()->json([
            'recovery_codes' => $recoveryCodes,
            'message' => __("2fa.enabled_successfully")
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $user->update([
            'two_factor_enabled' => false,
        ]);

        $user->twoFactorCodes()->delete();

        $user->notify(new TwoFactorStatusChanged(
            false,
            $request->ip(),
            now()->toDateTimeString()
        ));

        return response()->json(['message' => __("2fa.disabled")]);
    }

    public function showQrCode(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json(['message' => __("2fa.not_enabled")], 400);
        }

        $secretKey = decrypt($user->two_factor_secret);

        return response()->json([
            'qr_code' => $this->generateQrCode($user->email, $secretKey)
        ]);
    }

    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json(['message' => __('2fa.not_enabled')], 400);
        }

        $codes = $user->twoFactorCodes()->first();

        return response()->json([
            'recovery_codes' => json_decode(decrypt($codes->two_factor_recovery_codes))
        ]);
    }

    private function generateQrCode($email, $secretKey)
    {
        $google2fa = new Google2FA();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $email,
            $secretKey
        );

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }

    private function generateRecoveryCodes()
    {
        return collect(range(1, 8))->map(function () {
            return strtoupper(bin2hex(random_bytes(5)));
        })->toArray();
    }
}
