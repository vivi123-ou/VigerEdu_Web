<?php

namespace App\Modules\Access\Filament\Resources\Roles\Tables;

use App\Models\User;
use App\Modules\Access\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Làm gì:   Bảng vai trò: tên, mã, số quyền, số tài khoản, vai trò lõi hay không.
 * Ai gọi:   RoleResource::table().
 * Dữ liệu:  bảng roles, role_permission, users.
 */
class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('permissions'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Vai trò')->description(fn (Role $record) => $record->description)->wrap(),
                TextColumn::make('code')->label('Mã')->badge()->color('gray'),
                TextColumn::make('permissions_count')->label('Số quyền')
                    ->formatStateUsing(fn (Role $record, int $state) => $record->code === User::ROLE_ADMIN ? 'Tất cả' : (string) $state),
                TextColumn::make('users_count')->label('Số tài khoản')
                    ->state(fn (Role $record) => User::query()->where('role', $record->code)->count()),
                IconColumn::make('is_system')->label('Vai trò lõi')->boolean(),
                IconColumn::make('is_active')->label('Đang dùng')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
