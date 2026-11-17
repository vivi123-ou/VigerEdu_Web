<?php

namespace App\Modules\Access\Filament\Resources\Users\Pages;

use App\Modules\Access\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Làm gì:   Sửa tài khoản, đặt lại mật khẩu.
 * Ai gọi:   UserResource (nút "Sửa").
 * Dữ liệu:  bảng users.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [UserResource::helpAction(), DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
