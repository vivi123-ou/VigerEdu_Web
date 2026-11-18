<?php

namespace App\Modules\System\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Modules\System\Models\BlockedIp;
use App\Modules\System\Services\IpGuard;
use App\Support\AdminMenu;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Làm gì:   Trang "IP bị chặn": các địa chỉ IP đang bị chặn (tự chặn vì truy cập dồn dập / dò đường hacker, hoặc chặn tay),
 *           lý do, đến khi nào; "Bỏ chặn"; "Chặn IP" bằng tay (có thời hạn hoặc đến khi bỏ chặn).
 * Ai gọi:   Filament (/admin/ip-bi-chan). Quyền: system.manage (mặc định chỉ quản trị viên).
 * Dữ liệu:  blocked_ips qua IpGuard; mỗi lần chặn / bỏ chặn ghi "Lịch sử thao tác".
 */
class BlockedIps extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'ip-bi-chan';

    protected static string $menuKey = 'blocked-ips';

    protected string $view = 'filament.pages.simple-table';

    public static function canAccess(): bool
    {
        return AdminMenu::allows(auth()->user(), static::$menuKey);
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return static::menuItem()['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('block')->label('Chặn IP')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                ->authorize(fn () => static::canAccess())
                ->schema([
                    TextInput::make('ip')->label('Địa chỉ IP')->required()->ip()
                        ->rules([fn () => function (string $attribute, $value, $fail): void {
                            if ($value === request()->ip()) {
                                $fail('Đây là IP bạn đang dùng, chặn sẽ không vào được trang quản trị.');
                            }
                        }]),
                    TextInput::make('reason')->label('Lý do')->required()->maxLength(200),
                    TextInput::make('minutes')->label('Chặn trong (phút)')->integer()->minValue(0)->default(1440)
                        ->helperText('0 = chặn đến khi bấm "Bỏ chặn".'),
                ])
                ->action(function (array $data): void {
                    app(IpGuard::class)->block($data['ip'], $data['reason'], (int) $data['minutes'], null, 0, auth()->id());
                    Notification::make()->success()->title('Đã chặn IP '.$data['ip'])->send();
                }),
            static::helpAction(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BlockedIp::query()->active()->with('blocker'))
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Không có IP nào đang bị chặn')
            ->searchable()
            ->columns([
                TextColumn::make('ip')->label('Địa chỉ IP')->searchable()->copyable()->weight('medium'),
                TextColumn::make('reason')->label('Lý do')->wrap(),
                TextColumn::make('last_path')->label('Đường dẫn gần nhất')->placeholder('—')->limit(40),
                TextColumn::make('blocker.name')->label('Ai chặn')->default('Hệ thống tự chặn'),
                TextColumn::make('updated_at')->label('Từ lúc')->dateTime('H:i d/m/Y'),
                TextColumn::make('blocked_until')->label('Đến lúc')->dateTime('H:i d/m/Y')->placeholder('Đến khi bỏ chặn'),
            ])
            ->recordActions([
                Action::make('unblock')->label('Bỏ chặn')->icon(Heroicon::OutlinedLockOpen)
                    ->requiresConfirmation()->modalHeading(fn (BlockedIp $record) => 'Bỏ chặn IP '.$record->ip.'?')
                    ->authorize(fn () => static::canAccess())
                    ->action(function (BlockedIp $record): void {
                        app(IpGuard::class)->unblock($record);
                        Notification::make()->success()->title('Đã bỏ chặn IP '.$record->ip)->send();
                    }),
            ]);
    }
}
