<?php

namespace App\Modules\Access\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Modules\Access\Models\Role;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Làm gì:   Bảng tài khoản nhân sự: tên, tên đăng nhập, vai trò, tình trạng, lần vào cuối; nút Sửa, Khóa / Mở khóa.
 *           Người nghỉ việc thì khóa chứ không xóa, để lịch sử vẫn biết ai đã làm gì.
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
            ->searchPlaceholder('Tìm tên, tên đăng nhập…')
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('username')->label('Tên đăng nhập')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('roleModel.name')->label('Vai trò')->badge()->default(fn (User $record) => $record->role)
                    ->color(fn (User $record) => match ($record->role) {
                        User::ROLE_ADMIN => 'primary', User::ROLE_COUNSELOR => 'info', default => 'gray'
                    }),
                TextColumn::make('is_active')->label('Tình trạng')->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Đang dùng' : 'Đã khóa')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                TextColumn::make('last_login_at')->label('Vào lần cuối')->dateTime('H:i d/m/Y')->placeholder('Chưa vào lần nào')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Vai trò')->options(fn () => Role::query()->orderBy('sort_order')->pluck('name', 'code')),
                TernaryFilter::make('is_active')->label('Tình trạng')->trueLabel('Đang dùng')->falseLabel('Đã khóa'),
            ])
            ->recordActions([
                EditAction::make()->label('Sửa'),
                Action::make('toggle_active')
                    ->label(fn (User $record) => $record->is_active ? 'Khóa' : 'Mở khóa')
                    ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                    ->color(fn (User $record) => $record->is_active ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => ($record->is_active ? 'Khóa' : 'Mở khóa').' tài khoản '.$record->name.'?')
                    ->modalDescription(fn (User $record) => $record->is_active
                        ? 'Người này sẽ không đăng nhập được nữa; nếu đang đăng nhập sẽ bị đăng xuất. Mở khóa lại được bất cứ lúc nào.'
                        : 'Người này đăng nhập lại được với mật khẩu cũ.')
                    ->visible(fn (User $record) => ! $record->is(auth()->user()) && (auth()->user()?->can('update', $record) ?? false))
                    ->authorize(fn (User $record) => ! $record->is(auth()->user()) && (auth()->user()?->can('update', $record) ?? false))
                    ->action(function (User $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()->success()->title($record->is_active ? 'Đã mở khóa '.$record->name : 'Đã khóa '.$record->name)->send();
                    }),
            ]);
    }
}
