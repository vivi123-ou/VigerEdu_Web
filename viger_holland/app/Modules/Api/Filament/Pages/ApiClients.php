<?php

namespace App\Modules\Api\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Api\Models\ApiClient;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Làm gì:   Trang "Kết nối API (chatbot)": cấp mã kết nối (token) cho ứng dụng đọc kết quả Holland (chatbot của Ý, CRM);
 *           token chỉ hiện MỘT lần lúc cấp (bảng chỉ giữ băm và 4 ký tự cuối); xem lần dùng gần nhất; thu hồi / bật lại.
 *           Mỗi lần cấp, thu hồi ghi lịch sử. Tài liệu API: docs/API_HOLLAND.md.
 * Ai gọi:   Filament (/admin/ket-noi-api). Quyền: api.manage (mặc định chỉ quản trị viên).
 * Dữ liệu:  api_clients; admin_activities.
 */
class ApiClients extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'ket-noi-api';

    protected static string $menuKey = 'api';

    protected string $view = 'filament.pages.simple-table';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('api.manage');
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
        return [static::helpAction()];
    }

    public function table(Table $table): Table
    {
        $authorize = fn () => static::canAccess();

        return $table
            ->query(ApiClient::query())
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Chưa cấp mã kết nối nào')
            ->emptyStateDescription('Bấm "Cấp mã kết nối" khi bạn làm chatbot cần đọc kết quả Holland.')
            ->columns([
                TextColumn::make('name')->label('Ứng dụng'),
                TextColumn::make('token_last4')->label('Mã kết thúc bằng')->formatStateUsing(fn (string $state) => '…'.$state),
                TextColumn::make('scopes')->label('Được làm')->badge()->formatStateUsing(fn (string $state) => ApiClient::SCOPES[$state] ?? $state)->wrap(),
                IconColumn::make('is_active')->label('Đang dùng')->boolean(),
                TextColumn::make('last_used_at')->label('Dùng lần cuối')->since()->placeholder('Chưa dùng'),
                TextColumn::make('created_at')->label('Cấp lúc')->dateTime('H:i d/m/Y'),
            ])
            ->headerActions([
                Action::make('issue')->label('Cấp mã kết nối')->icon(Heroicon::OutlinedKey)->authorize($authorize)
                    ->modalHeading('Cấp mã kết nối cho một ứng dụng')
                    ->schema([
                        TextInput::make('name')->label('Tên ứng dụng')->required()->maxLength(100)->placeholder('Chatbot tư vấn'),
                        CheckboxList::make('scopes')->label('Được làm')->options(ApiClient::SCOPES)->default(array_keys(ApiClient::SCOPES))->required(),
                    ])
                    ->action(function (array $data): void {
                        [$client, $token] = ApiClient::issue((string) $data['name'], $data['scopes'], auth()->id());
                        AdminActivity::record(AdminActivity::ACTION_CREATE, 'api_clients', $client->id, 'Cấp mã kết nối API cho "'.$client->name.'"');
                        Notification::make()->success()->persistent()
                            ->title('Đã cấp mã cho "'.$client->name.'"')
                            ->body('Sao chép và gửi riêng cho người làm ứng dụng. Mã chỉ hiện lần này: '.$token)
                            ->actions([Action::make('copy')->label('Sao chép mã')->alpineClickHandler('navigator.clipboard.writeText('.json_encode($token).')')])
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')->label('Thu hồi')->icon(Heroicon::OutlinedNoSymbol)->color('danger')->authorize($authorize)
                    ->visible(fn (ApiClient $record) => $record->is_active)
                    ->requiresConfirmation()->modalHeading(fn (ApiClient $record) => 'Thu hồi mã của "'.$record->name.'"?')
                    ->modalDescription('Ứng dụng này sẽ không đọc được kết quả nữa cho tới khi bật lại hoặc cấp mã mới.')
                    ->action(function (ApiClient $record): void {
                        $record->update(['is_active' => false]);
                        AdminActivity::record(AdminActivity::ACTION_UPDATE, 'api_clients', $record->id, 'Thu hồi mã kết nối API của "'.$record->name.'"');
                    }),
                Action::make('enable')->label('Bật lại')->icon(Heroicon::OutlinedCheck)->authorize($authorize)
                    ->visible(fn (ApiClient $record) => ! $record->is_active)
                    ->action(function (ApiClient $record): void {
                        $record->update(['is_active' => true]);
                        AdminActivity::record(AdminActivity::ACTION_UPDATE, 'api_clients', $record->id, 'Bật lại mã kết nối API của "'.$record->name.'"');
                    }),
            ]);
    }
}
