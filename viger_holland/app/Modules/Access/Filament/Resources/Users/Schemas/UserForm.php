<?php

namespace App\Modules\Access\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Modules\Access\Filament\Resources\Users\UserResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Password;

/**
 * Làm gì:   Form thêm / sửa tài khoản. Không cho tự đổi vai trò hay tự khóa tài khoản đang đăng nhập.
 * Ai gọi:   UserResource::form().
 * Dữ liệu:  bảng users (mật khẩu được băm, không bao giờ hiện lại).
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record !== null && $record->is(auth()->user());

        return $schema->columns(1)->components([
            Section::make('Thông tin tài khoản')->columns(2)->components([
                TextInput::make('name')->label('Họ tên')->required()->maxLength(100),
                TextInput::make('username')->label('Tên đăng nhập')
                    ->required()->maxLength(50)->regex('/^[A-Za-z0-9._-]+$/')->unique(ignoreRecord: true)
                    ->validationMessages(['regex' => 'Tên đăng nhập chỉ gồm chữ không dấu, số, dấu chấm, gạch ngang, gạch dưới.'])
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Dùng để đăng nhập, chỉ gồm chữ không dấu, số, dấu chấm, gạch ngang, gạch dưới. Ví dụ: lan.tuvan'),
                TextInput::make('email')->label('Email')->email()->maxLength(255)->unique(ignoreRecord: true)
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Không bắt buộc. Có email thì đăng nhập bằng email cũng được.'),
                Select::make('role')->label('Vai trò')
                    ->options(fn () => UserResource::roleOptions())
                    ->required()
                    ->disabled($isSelf)
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Vai trò quyết định người này được làm việc gì. Xem và đổi ở trang "Phân quyền".'),
                Toggle::make('is_active')->label('Đang dùng (bỏ tick = khóa)')->default(true)->inline(false)
                    ->disabled($isSelf)
                    ->helperText('Tắt thì người này không đăng nhập được nữa; đang đăng nhập sẽ bị đăng xuất.'),
            ]),

            Section::make(fn (string $operation) => $operation === 'create' ? 'Mật khẩu' : 'Đặt lại mật khẩu')
                ->description(fn (string $operation) => $operation === 'create' ? null : 'Để trống nếu không đổi mật khẩu.')
                ->columns(2)
                ->components([
                    TextInput::make('password')->label('Mật khẩu')
                        ->password()->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(Password::min(10)->letters()->numbers())
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->same('password_confirmation')
                        ->helperText('Ít nhất 10 ký tự, có cả chữ và số.'),
                    TextInput::make('password_confirmation')->label('Nhập lại mật khẩu')
                        ->password()->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(false),
                ]),
        ]);
    }
}
