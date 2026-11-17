<?php

namespace App\Modules\Access\Filament\Resources\Users\Tables;

use App\Modules\Access\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Làm gì:   Bảng danh sách tài khoản: tên, tên đăng nhập, vai trò, trạng thái, lần đăng nhập cuối.
 * Ai gọi:   UserResource::table().
 * Dữ liệu:  bảng users, roles.
 */
class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roleModel'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable()->sortable(),
                TextColumn::make('username')->label('Tên đăng nhập')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('roleModel.name')->label('Vai trò')->badge()->default(fn ($record) => $record->role),
                IconColumn::make('is_active')->label('Đang hoạt động')->boolean(),
                TextColumn::make('last_login_at')->label('Đăng nhập lần cuối')->dateTime('H:i d/m/Y')->placeholder('Chưa đăng nhập')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Vai trò')->options(fn () => Role::query()->orderBy('sort_order')->pluck('name', 'code')),
                TernaryFilter::make('is_active')->label('Đang hoạt động'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription('Nên tắt "Đang hoạt động" thay vì xóa. Xóa rồi thì không khôi phục được.'),
            ]);
    }
}
