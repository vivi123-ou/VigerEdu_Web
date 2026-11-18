<?php

namespace App\Modules\Respondent\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Models\FieldValue;
use App\Modules\Respondent\Models\FormField;
use App\Modules\Respondent\Support\InternalReport;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Làm gì:   Trang "Khách cần liên lạc" (mẫu admin-filament.html màn hình 4): khách mới để lại thông tin, mới nhất ở trên;
 *           họ tên, điện thoại phụ huynh / học sinh (bấm để gọi, nút Zalo), email, đối tượng, năm sinh, tỉnh, lúc để lại.
 *           KHÔNG có kết quả bài làm, KHÔNG có nút tải. Chỉ khách trong số ngày ở cài đặt contacts_days (mặc định 7);
 *           trang không ghi ra giới hạn này (Vân chốt 17/11). Mỗi lần mở trang được ghi vào nhật ký.
 *           Họ tên, SĐT lưu mã hóa nên tìm kiếm làm sau khi giải mã (số khách trong vài ngày nhỏ).
 * Ai gọi:   Filament (/admin/khach-can-lien-lac). Quyền: contacts.view.
 * Dữ liệu:  test_sessions (đã nhập đúng mã), respondents, field_values (tỉnh); admin_activities.
 */
class ContactsToCall extends Page
{
    use UsesAdminMenu;

    private const PER_PAGE = 20;

    protected static ?string $slug = 'khach-can-lien-lac';

    protected static string $menuKey = 'contacts';

    protected string $view = 'filament.pages.contacts-to-call';

    public string $tab = 'tat-ca';

    public string $search = '';

    public string $role = '';

    public string $province = '';

    public int $pageNumber = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('contacts.view');
    }

    public function mount(): void
    {
        AdminActivity::record(AdminActivity::ACTION_VIEW, 'respondents', null, 'Mở danh sách khách cần liên lạc');
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

    public function updated(string $property): void
    {
        if ($property !== 'pageNumber') {
            $this->pageNumber = 1;
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['hom-nay', 'hom-qua', 'tat-ca'], true) ? $tab : 'tat-ca';
        $this->pageNumber = 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $all = $this->contacts();
        $counts = [
            'hom-nay' => $all->filter(fn (array $c) => $c['at']->isToday())->count(),
            'hom-qua' => $all->filter(fn (array $c) => $c['at']->isYesterday())->count(),
            'tat-ca' => $all->count(),
        ];

        $rows = $all
            ->filter(fn (array $c) => match ($this->tab) {
                'hom-nay' => $c['at']->isToday(),
                'hom-qua' => $c['at']->isYesterday(),
                default => true,
            })
            ->filter(fn (array $c) => $this->role === '' || $c['roleCode'] === $this->role)
            ->filter(fn (array $c) => $this->province === '' || $c['province'] === $this->province)
            ->filter(fn (array $c) => $this->matches($c))
            ->values();

        $pages = max(1, (int) ceil($rows->count() / self::PER_PAGE));
        $this->pageNumber = min(max(1, $this->pageNumber), $pages);

        return [
            'rows' => $rows->slice(($this->pageNumber - 1) * self::PER_PAGE, self::PER_PAGE)->values(),
            'total' => $rows->count(),
            'pages' => $pages,
            'counts' => $counts,
            'roles' => collect(FormField::query()->where('key', 'doi_tuong')->first()?->options ?? []),
            'provinces' => $all->pluck('province')->filter()->unique()->sort()->values(),
        ];
    }

    /**
     * Khách đã nhập đúng mã trong mấy ngày gần đây, mỗi khách một dòng (lần mới nhất).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function contacts(): Collection
    {
        $days = max(1, (int) setting('contacts_days', '7'));
        $sessions = TestSession::query()
            ->whereNotNull('verified_at')->whereNotNull('respondent_id')
            ->where('verified_at', '>=', now()->subDays($days)->startOfDay())
            ->with('respondent')->latest('verified_at')->get()
            ->unique('respondent_id');

        $provinces = FieldValue::query()->whereIn('test_session_id', $sessions->pluck('id'))->where('key', 'tinh')->get()
            ->mapWithKeys(fn (FieldValue $v) => [$v->test_session_id => (string) $v->value]);

        return $sessions->filter(fn (TestSession $s) => $s->respondent !== null)->map(fn (TestSession $s) => [
            'name' => $s->respondent->full_name,
            'parentPhone' => $s->respondent->parent_phone,
            'phone' => $s->respondent->phone,
            'email' => $s->respondent->email,
            'roleCode' => $s->respondent->role,
            'role' => InternalReport::roleLabel($s->respondent->role),
            'birthYear' => $s->respondent->birth_date ? substr($s->respondent->birth_date, 0, 4) : $s->respondent->birth_year,
            'province' => $provinces[$s->id] ?? null,
            'at' => Carbon::parse($s->verified_at),
        ])->values();
    }

    /**
     * Tìm theo tên (không phân biệt dấu, hoa thường) hoặc một phần số điện thoại.
     *
     * @param  array<string, mixed>  $contact
     */
    private function matches(array $contact): bool
    {
        $query = trim($this->search);
        if ($query === '') {
            return true;
        }

        $digits = preg_replace('/\D/', '', $query) ?? '';
        if (strlen($digits) >= 3) {
            foreach ([$contact['parentPhone'], $contact['phone']] as $phone) {
                if ($phone && str_contains((string) $phone, $digits)) {
                    return true;
                }
            }
        }

        return str_contains(Str::lower(Str::ascii((string) $contact['name'])), Str::lower(Str::ascii($query)));
    }

    public static function zaloUrl(?string $phone): ?string
    {
        return $phone ? 'https://zalo.me/'.preg_replace('/\D/', '', $phone) : null;
    }

    public static function when(Carbon $at): string
    {
        return $at->format('H:i').' '.match (true) {
            $at->isToday() => 'hôm nay',
            $at->isYesterday() => 'hôm qua',
            default => $at->format('d/m'),
        };
    }
}
