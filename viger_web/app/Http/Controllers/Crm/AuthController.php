<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('crm.dashboard');
        }

        return view('crm::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = 'crm|'.Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau '.RateLimiter::availableIn($key).' giây.',
            ]);
        }

        if (Auth::attempt([...$credentials, 'active' => true], $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            CrmAuditLog::record('login', 'Đăng nhập CRM.');

            return redirect()->intended(route('crm.dashboard'));
        }

        RateLimiter::hit($key, 60);
        CrmAuditLog::record('login_failed', 'Đăng nhập sai với tên đăng nhập "'.Str::limit($credentials['username'], 60).'".');

        return back()->withInput($request->only('username'))->withErrors([
            'username' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('crm.login');
    }
}
