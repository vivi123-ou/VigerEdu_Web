<?php

namespace App\Modules\Access\Filament\Resources\Users\Pages;

use App\Modules\Access\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Làm gì:   Thêm tài khoản mới.
 * Ai gọi:   UserResource (nút "Thêm tài khoản").
 * Dữ liệu:  bảng users.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [UserResource::helpAction()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
