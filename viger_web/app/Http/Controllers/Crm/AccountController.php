<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Làm gì:   Trang "Đổi mật khẩu" của nhân viên CRM (cũng là trang bắt buộc khi dùng mật khẩu tạm).
 * Ai gọi:   menu tài khoản trên header; middleware CrmForcePasswordChange chuyển hướng tới đây.
 * Dữ liệu:  ghi users.password, users.must_change_password.
 */
class AccountController extends Controller
{
    public function editPassword(Request $request)
    {
        return view('crm::account.password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ], [
            'current_password' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('crm.dashboard')->with('success', 'Đã đổi mật khẩu.');
    }
}
