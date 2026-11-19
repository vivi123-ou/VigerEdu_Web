<?php

namespace App\Modules\CrmSync\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\CrmSync\Services\CrmConfig;
use App\Modules\CrmSync\Services\CrmSync;
use App\Modules\CrmSync\Services\LeadPayload;
use App\Modules\Respondent\Support\InternalReport;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Làm gì:   Trang "Gửi khách sang CRM": bật / tắt gửi, địa chỉ CRM, token (lưu mã hóa, chỉ hiện 4 ký tự cuối; .env được ưu tiên),
 *           lịch gửi lại, mẫu link hồ sơ CRM; nút "Kiểm tra kết nối" (GET /api/v1/ping); bảng hàng đợi với số điện thoại
 *           đã che, trạng thái, lỗi gần nhất, nút "Gửi lại" từng dòng và "Gửi lại tất cả bản lỗi".
 *           Bật lại kết nối thì các gói "Đang tắt kết nối" tự chuyển sang "Chờ gửi".
 * Ai gọi:   Filament (/admin/gui-khach-sang-crm). Quyền: crm.manage (mặc định chỉ quản trị viên).
 * Dữ liệu:  settings (crm_*), crm_outbox; ghi nhật ký khi đổi cài đặt.
 */
class CrmConnection extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'gui-khach-sang-crm';

    protected static string $menuKey = 'crm';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('crm.manage');
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

    public function mount(): void
    {
        $this->form->fill([
            'crm_enabled' => (string) setting('crm_enabled', '0') === '1',
            'crm_url' => (string) setting('crm_url', ''),
            'crm_token' => '',
            'crm_retry_minutes' => (string) setting('crm_retry_minutes', '1,5,30,120,360'),
            'crm_customer_url' => (string) setting('crm_customer_url', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $config = app(CrmConfig::class);
        $savedToken = $config->token();

        return $schema->statePath('data')->components([
            Section::make('Kết nối')->columns(2)->components([
                Toggle::make('crm_enabled')->label('Gửi khách sang CRM')->columnSpanFull()
                    ->helperText('Tắt: khách vẫn làm bài, xem báo cáo bình thường; khách mới nằm chờ trong hàng đợi, bật lại thì tự gửi.'),
                TextInput::make('crm_url')->label('Địa chỉ CRM')->url()->maxLength(255)
                    ->placeholder('https://crm.viger.edu.vn')
                    ->disabled($config->urlFromEnv())
                    ->helperText($config->urlFromEnv() ? 'Đang dùng địa chỉ trong file .env của máy chủ.' : 'Địa chỉ trang CRM, không có dấu / ở cuối.'),
                TextInput::make('crm_token')->label('Mã kết nối (token)')->password()->revealable()->maxLength(255)
                    ->disabled($config->tokenFromEnv())
                    ->placeholder($savedToken !== '' ? 'Đã lưu, kết thúc bằng …'.substr($savedToken, -4) : 'Dán token kênh holland do CRM cấp')
                    ->helperText($config->tokenFromEnv() ? 'Đang dùng token trong file .env của máy chủ.' : 'Để trống nếu không đổi. Token lưu mã hóa, không hiện lại đầy đủ.'),
                TextInput::make('crm_retry_minutes')->label('Gửi lại sau (phút)')->required()->regex('/^\s*\d+(\s*,\s*\d+)*\s*$/')
                    ->helperText('Các mốc cách nhau dấu phẩy. Hết các mốc mà vẫn lỗi thì gói chuyển sang "Lỗi" để bạn bấm "Gửi lại".'),
                TextInput::make('crm_customer_url')->label('Link mở hồ sơ khách trong CRM')->maxLength(255)
                    ->placeholder('https://crm.viger.edu.vn/khach/{ma}')
                    ->helperText('Có {ma} thì báo cáo nội bộ hiện nút "Mở hồ sơ trong CRM". Để trống thì không hiện.'),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Lưu')->submit('save'),
                        Action::make('ping')->label('Kiểm tra kết nối')->color('gray')->icon(Heroicon::OutlinedSignal)->action('ping'),
                    ]),
                ]),
            Section::make('Hàng đợi gửi sang CRM')
                ->description('Mỗi khách nhập đúng mã email là một dòng "Khách mới"; hành vi sau đó (mở lại báo cáo, bấm Gọi / Zalo) là dòng "Hành vi". Gửi lỗi thì tự gửi lại theo lịch.')
                ->components([EmbeddedTable::make()]),
        ]);
    }

    public function save(CrmSync $sync): void
    {
        abort_unless(static::canAccess(), 404);
        $state = $this->form->getState();
        $config = app(CrmConfig::class);
        $wasEnabled = $config->enabled();

        $changes = [];
        foreach (['crm_url', 'crm_retry_minutes', 'crm_customer_url'] as $key) {
            if (array_key_exists($key, $state) && (string) $state[$key] !== (string) setting($key, '')) {
                Setting::put($key, trim((string) $state[$key]), 'crm');
                $changes[] = $key;
            }
        }
        if (filled($state['crm_token'] ?? null)) {
            $config->saveToken(trim((string) $state['crm_token']));
            $changes[] = 'crm_token';
        }
        $enabled = (bool) ($state['crm_enabled'] ?? false);
        if ($enabled !== $wasEnabled) {
            Setting::put('crm_enabled', $enabled ? '1' : '0', 'crm');
            $changes[] = 'crm_enabled';
        }
        Setting::flushCache();

        if ($changes !== []) {
            AdminActivity::record(AdminActivity::ACTION_UPDATE, 'settings.crm', null, 'Sửa kết nối CRM ('.implode(', ', $changes).')');
        }

        $resumed = $enabled && ! $wasEnabled && $config->ready() ? $sync->resumeDisabled() : 0;
        $this->form->fill(['crm_token' => ''] + $this->form->getState());

        Notification::make()->success()->title($changes ? 'Đã lưu' : 'Không có thay đổi nào')
            ->body($resumed ? "Đã đưa {$resumed} khách đang chờ vào hàng gửi." : ($enabled && ! $config->ready() ? 'Đã bật nhưng còn thiếu địa chỉ hoặc token, khách mới vẫn nằm chờ.' : null))
            ->send();
        $this->resetTable();
    }

    public function ping(): void
    {
        abort_unless(static::canAccess(), 404);
        $config = app(CrmConfig::class);

        if ($config->url() === '' || $config->token() === '') {
            Notification::make()->warning()->title('Chưa đủ thông tin')->body('Nhập địa chỉ CRM và token rồi bấm "Lưu" trước.')->send();

            return;
        }

        try {
            $response = Http::withHeaders(['X-Api-Token' => $config->token()])->acceptJson()->timeout(5)->get($config->url().'/api/v1/ping');
        } catch (Throwable) {
            Notification::make()->danger()->title('Không kết nối được')->body('Không mở được địa chỉ này. Kiểm tra lại địa chỉ CRM hoặc mạng của máy chủ.')->send();

            return;
        }

        match (true) {
            $response->successful() && $response->json('ok') === true => Notification::make()->success()->title('Kết nối tốt')
                ->body('CRM nhận ra kênh: '.($response->json('channel') ?: 'holland').'.')->send(),
            in_array($response->status(), [401, 403], true) => Notification::make()->danger()->title('Sai token')
                ->body('CRM không nhận token này. Xin token mới cho kênh holland ở CRM rồi dán lại.')->send(),
            default => Notification::make()->danger()->title('CRM trả lỗi')->body('Mã lỗi '.$response->status().'. Thử lại sau ít phút.')->send(),
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CrmOutbox::query()->with('testSession'))
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('created_at')->label('Lúc')->dateTime('H:i d/m/Y'),
                TextColumn::make('schema')->label('Loại')
                    ->formatStateUsing(fn (string $state) => $state === 'lead/2' ? 'Khách mới' : 'Hành vi'),
                TextColumn::make('report_code')->label('Mã báo cáo')
                    ->state(fn (CrmOutbox $record) => $record->testSession ? InternalReport::code($record->testSession) : '—'),
                TextColumn::make('phone')->label('Điện thoại')
                    ->state(fn (CrmOutbox $record) => ($phone = Arr::get($record->payload, 'guardian.phone') ?? Arr::get($record->payload, 'contact.phone')) ? LeadPayload::maskPhone((string) $phone) : '—'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => CrmOutbox::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        CrmOutbox::SENT => 'success', CrmOutbox::FAILED => 'danger', CrmOutbox::DISABLED => 'gray', default => 'warning',
                    }),
                TextColumn::make('tries')->label('Số lần thử'),
                TextColumn::make('next_try_at')->label('Gửi lại lúc')->dateTime('H:i d/m')->placeholder('—'),
                TextColumn::make('last_error')->label('Lỗi gần nhất')->wrap()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(CrmOutbox::STATUS_LABELS),
            ])
            ->headerActions([
                Action::make('retryFailed')->label('Gửi lại tất cả bản lỗi')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                    ->requiresConfirmation()->modalHeading('Gửi lại tất cả bản lỗi?')
                    ->authorize(fn () => static::canAccess())
                    ->action(function (CrmSync $sync): void {
                        $items = CrmOutbox::query()->where('status', CrmOutbox::FAILED)->get();
                        $items->each(fn (CrmOutbox $item) => $sync->retry($item));
                        Notification::make()->success()->title('Đã đưa '.$items->count().' bản vào hàng gửi lại')->send();
                    }),
            ])
            ->recordActions([
                Action::make('retry')->label('Gửi lại')->icon(Heroicon::OutlinedArrowPath)
                    ->hidden(fn (CrmOutbox $record) => $record->status === CrmOutbox::SENT)
                    ->authorize(fn () => static::canAccess())
                    ->action(function (CrmOutbox $record, CrmSync $sync): void {
                        $sync->retry($record);
                        Notification::make()->success()->title($record->fresh()->status === CrmOutbox::DISABLED
                            ? 'Kết nối đang tắt: bản này sẽ tự gửi khi bật lại'
                            : 'Đã đưa vào hàng gửi')->send();
                    }),
            ]);
    }
}
