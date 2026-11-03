<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            return redirect('/admin/posts');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'throttle' => "Bạn đã thử quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.",
            ]);
        }

        if (Auth::attempt([...$credentials, 'active' => true])) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            return redirect('/admin/posts');
        }

        RateLimiter::hit($key, 60);

        return redirect('/login?error=true');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login?logout=true');
    }
}
