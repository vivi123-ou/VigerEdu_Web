<?php

namespace App\Modules\Respondent\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\Respondent\Models\Respondent;
use App\Modules\Respondent\Services\ReportForm;
use App\Modules\Respondent\Support\InternalReport;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Làm gì:   Trang "Danh sách khách đã làm bài" (chỉ quản trị viên): mọi lượt đã nhập đúng mã email, mới nhất ở trên;
 *           lọc theo ngày, cặp mã, nguồn (UTM), độ tin cậy, trạng thái gửi CRM; tìm đúng SĐT / email / mã báo cáo (so bằng
 *           cột băm vì dữ liệu cá nhân lưu mã hóa); mở báo cáo nội bộ; "Tải ra Excel" theo đúng bộ lọc đang chọn
 *           (quyền data.export, mỗi lần tải ghi lịch sử: ai tải, bao nhiêu dòng).
 * Ai gọi:   Filament (/admin/danh-sach-khach). Quyền: customers.list (+ data.export để tải).
 * Dữ liệu:  test_sessions, respondents, field_values, crm_outbox; admin_activities.
 */
class CustomersList extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'danh-sach-khach';

    protected static string $menuKey = 'customers';

    protected string $view = 'filament.pages.simple-table';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('customers.list');
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
            static::helpAction(),
            Action::make('export')->label('Tải ra Excel')->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn () => auth()->user() instanceof User && auth()->user()->canExportData())
                ->requiresConfirmation()->modalHeading('Tải danh sách khách ra Excel?')
                ->modalDescription('File có họ tên, số điện thoại, email của khách theo bộ lọc đang chọn. Việc tải được ghi vào lịch sử thao tác. Không gửi file qua Zalo, Facebook.')
                ->action(fn () => $this->export()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TestSession::query()->whereNotNull('verified_at')->with('respondent'))
            ->defaultSort('verified_at', 'desc')
            ->searchable()
            ->searchPlaceholder('Đúng SĐT, email hoặc mã báo cáo')
            ->searchUsing(fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->columns([
                TextColumn::make('verified_at')->label('Để lại thông tin lúc')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('name')->label('Họ tên')->state(fn (TestSession $s) => $s->respondent?->full_name ?? '—')
                    ->description(fn (TestSession $s) => InternalReport::roleLabel($s->respondent?->role)),
                TextColumn::make('phone')->label('Điện thoại')
                    ->state(fn (TestSession $s) => collect([$s->respondent?->parent_phone ? $s->respondent->parent_phone.' (PH)' : null, $s->respondent?->phone])->filter()->implode(' · ') ?: '—'),
                TextColumn::make('code3')->label('Mã')->badge(),
                TextColumn::make('report_code')->label('Mã báo cáo')->state(fn (TestSession $s) => InternalReport::code($s)),
                TextColumn::make('reliability')->label('Bài làm')->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Tin cậy' => 'success', 'Cần xem lại' => 'warning', default => 'danger'
                    }),
                TextColumn::make('source')->label('Nguồn')->state(fn (TestSession $s) => $s->utm['utm_source'] ?? 'Trực tiếp'),
            ])
            ->filters([
                Filter::make('ngay')->label('Khoảng ngày')
                    ->schema([DatePicker::make('tu')->label('Từ ngày'), DatePicker::make('den')->label('Đến ngày')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['tu'] ?? null, fn (Builder $q, $d) => $q->whereDate('verified_at', '>=', $d))
                        ->when($data['den'] ?? null, fn (Builder $q, $d) => $q->whereDate('verified_at', '<=', $d))),
                SelectFilter::make('cap')->label('Cặp mã (2 chữ đầu)')
                    ->options(RiasecPair::query()->orderBy('pair')->get()->mapWithKeys(fn (RiasecPair $p) => [$p->pair => $p->pair.' – '.$p->name])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->where(fn (Builder $q) => $q->where('code2', $data['value'])->orWhere('code2', strrev((string) $data['value'])))
                        : $query),
                SelectFilter::make('nguon')->label('Nguồn')
                    ->options(fn () => TestSession::query()->whereNotNull('verified_at')->pluck('utm')->map(fn ($u) => $u['utm_source'] ?? null)->filter()->unique()->sort()->mapWithKeys(fn ($s) => [$s => $s])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->where('utm->utm_source', $data['value']) : $query),
                SelectFilter::make('reliability')->label('Bài làm')->options(['Tin cậy' => 'Tin cậy', 'Cần xem lại' => 'Cần xem lại', 'Chưa đủ tin cậy' => 'Chưa đủ tin cậy']),
            ])
            ->recordActions([
                Action::make('open')->label('Đọc báo cáo')->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (TestSession $s) => InternalReportPage::getUrl(['ma' => $s->uuid])),
            ]);
    }

    /**
     * Tìm đúng: email / SĐT (học sinh hoặc phụ huynh) so bằng cột băm, mã báo cáo 8 ký tự so đầu mã lượt làm bài.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        if (str_contains($search, '@')) {
            return $query->whereIn('respondent_id', Respondent::query()->where('email_hash', Respondent::hashOf(mb_strtolower($search)))->select('id'));
        }
        if (preg_match('/^[0-9a-fA-F]{8}$/', $search)) {
            return $query->where('uuid', 'like', strtolower($search).'%');
        }
        $hash = Respondent::hashOf(ReportForm::normalizePhone($search));

        return $query->whereIn('respondent_id', Respondent::query()->where('phone_hash', $hash)->orWhere('parent_phone_hash', $hash)->select('id'));
    }

    public function export(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User && static::canAccess() && $user->canExportData(), 404);

        $sessions = $this->getFilteredSortedTableQuery()->with('respondent')->get();
        $values = app(ReportForm::class);
        $crm = CrmOutbox::query()->where('schema', 'lead/2')->whereIn('test_session_id', $sessions->pluck('id'))->pluck('status', 'test_session_id');

        AdminActivity::record('export', 'respondents', null, 'Tải danh sách khách ra Excel – '.$sessions->count().' dòng');

        return response()->streamDownload(function () use ($sessions, $values, $crm): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Để lại thông tin lúc', 'Mã báo cáo', 'Họ tên', 'Ngày sinh', 'Đối tượng', 'Email', 'Điện thoại phụ huynh', 'Điện thoại học sinh',
                'Trường', 'Tỉnh / thành', 'Mã đầy đủ', 'Khách thấy', 'Điểm R', 'Điểm I', 'Điểm A', 'Điểm S', 'Điểm E', 'Điểm C', 'Bài làm', 'Nguồn', 'Gửi CRM', 'Mã khách CRM']));
            foreach ($sessions as $s) {
                $r = $s->respondent;
                $form = $values->values($s);
                $scores = $s->scores ?? [];
                $writer->addRow(Row::fromValues([
                    $s->verified_at?->format('Y-m-d H:i'), InternalReport::code($s), $r?->full_name, $r?->birth_date ?? $r?->birth_year,
                    InternalReport::roleLabel($r?->role), $r?->email, $r?->parent_phone, $r?->phone,
                    $form['truong'] ?? null, $form['tinh'] ?? null, $s->code3, $s->result['customer']['code'] ?? null,
                    ...array_map(fn (string $g) => isset($scores[$g]) ? round((float) $scores[$g], 2) : null, str_split('RIASEC')),
                    $s->reliability, $s->utm['utm_source'] ?? null, CrmOutbox::STATUS_LABELS[$crm[$s->id] ?? ''] ?? null, $r?->crm_customer_code,
                ]));
            }
            $writer->close();
        }, 'danh-sach-khach-'.now()->format('Y-m-d-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
