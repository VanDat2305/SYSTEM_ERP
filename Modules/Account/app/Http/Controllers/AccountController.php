<?php

namespace Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Account\Models\Account;
use Illuminate\Support\Str;
use App\Mail\SendTempAccountMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
        public function checkOrCreate(Request $request)
    {
        $validated = $request->validate([
            'erp_customer_id' => 'required|uuid',
            'email'           => 'required|email',
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:30',
            'type'            => 'required|string',
            'tax_code'        => 'nullable|string|max:50',
            'address'         => 'nullable|string|max:255',
        ]);

        // Ưu tiên tìm theo erp_customer_id
        $account = null;
        if (!empty($validated['erp_customer_id'])) {
            $account = Account::where('erp_customer_id', $validated['erp_customer_id'])->first();
        }
        // Nếu chưa có, thử tìm theo email
        if (!$account) {
            $account = Account::where('email', $validated['email'])->first();
        }

        $is_new = false;
        $plainPassword = null;

        if (!$account) {
            // Sinh mật khẩu tạm thời
            $plainPassword = Str::random(10);
            $account = Account::create([
                'id'               => Str::uuid(),
                'erp_customer_id'  => $validated['erp_customer_id'] ?? null,
                'code'             => strtoupper('AC' . rand(10000,99999)),
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'phone'            => $validated['phone'] ?? null,
                'tax_code'         => $validated['tax_code'] ?? null,
                'address'          => $validated['address'] ?? null,
                'type'             => $validated['type'],
                'is_active'        => true,
                'activated_at'     => now(),
                'password'         => Hash::make($plainPassword),
            ]);
            $is_new = true;

            // Gửi mail thông báo mật khẩu tạm thời cho khách
            Mail::to($account->email)->send(
                new SendTempAccountMail($account->name, $account->email, $plainPassword)
            );
        }

        return response()->json([
            'is_new'      => $is_new,
            'account_id'  => $account->id,
            'erp_customer_id' => $account->erp_customer_id,
            'email'       => $account->email,
            'note'        => $is_new ? 'Tài khoản mới đã được tạo và gửi mail mật khẩu tạm thời.' : 'Tài khoản đã tồn tại',
        ]);
    }
}
