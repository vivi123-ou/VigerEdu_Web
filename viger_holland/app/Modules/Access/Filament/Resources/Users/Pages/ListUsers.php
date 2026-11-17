<?php

namespace App\Modules\Access\Filament\Resources\Users\Pages;

use App\Modules\Access\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Làm gì:   Trang danh sách tài khoản nhân sự, có câu mô tả ngắn và nút "?" hướng dẫn.
 * Ai gọi:   UserResource.
 * Dữ liệu:  bảng users.
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return UserResource::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UserResource::menuItem()['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            UserResource::helpAction(),
            CreateAction::make()->label('Thêm tài khoản'),
        ];
    }
}
