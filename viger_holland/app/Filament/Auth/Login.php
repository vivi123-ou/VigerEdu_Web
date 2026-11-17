<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Làm gì:   Trang đăng nhập quản trị: nhập tên đăng nhập hoặc email. Sai 5 lần / phút thì tạm khóa (Filament có sẵn),
 *           tài khoản bị khóa hoặc vai trò bị tắt không đăng nhập được (User::canAccessPanel).
 * Ai gọi:   AdminPanelProvider (đường dẫn /admin/dang-nhap).
 * Dữ liệu:  bảng users (ghi last_login_at). Không ghi tên đăng nhập hay mật khẩu ra log.
 */
class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Đăng nhập quản trị';
    }

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        Filament::auth()->user()?->forceFill(['last_login_at' => now()])->saveQuietly();

        return $response;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Tên đăng nhập hoặc email')
            ->required()
            ->maxLength(255)
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim((string) $data['login']);

        return [
            filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => $login,
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.',
        ]);
    }
}
