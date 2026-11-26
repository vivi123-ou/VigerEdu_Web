<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    /** key => [nhãn, class badge] */
    public const STATUSES = [
        'new' => ['Mới', 'badge-blue'],
        'contacted' => ['Đã liên hệ', 'badge-sky'],
        'consulting' => ['Đang tư vấn', 'badge-amber'],
        'potential' => ['Tiềm năng', 'badge-violet'],
        'converted' => ['Đã chuyển đổi', 'badge-green'],
        'lost' => ['Không tiềm năng', 'badge-gray'],
    ];

    public const SOURCES = [
        'website' => 'Website VIGER',
        'holland' => 'Holland',
        'chatbot' => 'AI Chatbot',
        'other' => 'Nguồn khác',
    ];

    /** Bộ lọc "Ngày tạo" */
    public const DATE_RANGES = [
        'today' => 'Hôm nay',
        '7d' => '7 ngày qua',
        '30d' => '30 ngày qua',
        'this_month' => 'Tháng này',
    ];

    /** Nhóm Holland: chữ cái => [tên, mô tả] */
    public const HOLLAND_TYPES = [
        'R' => ['Kỹ thuật (Realistic)', 'Thích làm việc với máy móc, công cụ, hoạt động thực hành. Hợp: kỹ thuật cơ khí, điện, xây dựng, nông nghiệp.'],
        'I' => ['Nghiên cứu (Investigative)', 'Thích quan sát, phân tích, giải quyết vấn đề. Hợp: khoa học tự nhiên, y dược, công nghệ thông tin.'],
        'A' => ['Nghệ thuật (Artistic)', 'Sáng tạo, giàu trí tưởng tượng, thích tự do. Hợp: thiết kế, kiến trúc, truyền thông, âm nhạc.'],
        'S' => ['Xã hội (Social)', 'Thích giúp đỡ, giảng dạy, làm việc với con người. Hợp: giáo dục, điều dưỡng, tâm lý, công tác xã hội.'],
        'E' => ['Quản lý (Enterprising)', 'Năng động, thích thuyết phục và lãnh đạo. Hợp: quản trị kinh doanh, marketing, luật, kinh tế.'],
        'C' => ['Nghiệp vụ (Conventional)', 'Cẩn thận, ngăn nắp, thích công việc có quy trình. Hợp: kế toán, tài chính, hành chính, logistics.'],
    ];

    public const GERMAN_STATES = [
        'Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg', 'Bremen', 'Hamburg', 'Hessen',
        'Mecklenburg-Vorpommern', 'Niedersachsen', 'Nordrhein-Westfalen', 'Rheinland-Pfalz',
        'Saarland', 'Sachsen', 'Sachsen-Anhalt', 'Schleswig-Holstein', 'Thüringen', 'Chưa xác định',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'student_dob' => 'date',
            'gpa' => 'decimal:2',
            'holland_result' => 'array',
        ];
    }

    /**
     * Khai báo các phần thông tin trên trang chi tiết.
     * Trang chi tiết, form sửa và validate đều đọc từ đây — thêm trường: thêm cột + khai báo ở đây.
     *
     * type: text | email | tel | date | select | textarea | score (0–100) | gpa (0–10)
     * options: mảng value => nhãn, hoặc 'consultants' (danh sách nhân viên)
     */
    public static function sections(): array
    {
        $same = fn (array $list) => array_combine($list, $list);

        return [
            'general' => ['title' => 'Thông tin khách hàng', 'fields' => [
                'source' => ['label' => 'Nguồn', 'type' => 'select', 'options' => self::SOURCES, 'required' => true],
                'status' => ['label' => 'Trạng thái', 'type' => 'select', 'options' => array_map(fn ($s) => $s[0], self::STATUSES), 'required' => true],
                'assigned_to' => ['label' => 'Tư vấn viên', 'type' => 'select', 'options' => 'consultants'],
            ]],
            'parent' => ['no' => 'I', 'title' => 'Thông tin phụ huynh (liên hệ chính)', 'fields' => [
                'full_name' => ['label' => 'Họ và tên', 'type' => 'text', 'required' => true],
                'relationship' => ['label' => 'Mối quan hệ', 'type' => 'select', 'options' => $same(['Bố', 'Mẹ', 'Người giám hộ', 'Anh/Chị', 'Học sinh tự liên hệ', 'Khác'])],
                'phone' => ['label' => 'Số điện thoại', 'type' => 'tel', 'required' => true],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'occupation' => ['label' => 'Nghề nghiệp', 'type' => 'text'],
                'address' => ['label' => 'Địa chỉ', 'type' => 'text', 'full' => true],
            ]],
            'student' => ['no' => 'II', 'title' => 'Thông tin học sinh', 'fields' => [
                'student_name' => ['label' => 'Họ và tên', 'type' => 'text'],
                'student_dob' => ['label' => 'Ngày sinh', 'type' => 'date'],
                'student_gender' => ['label' => 'Giới tính', 'type' => 'select', 'options' => $same(['Nam', 'Nữ', 'Khác'])],
                'student_school' => ['label' => 'Trường đang học', 'type' => 'text'],
                'education_level' => ['label' => 'Trình độ học vấn', 'type' => 'select', 'options' => $same(['THCS', 'Đang học THPT', 'Tốt nghiệp THPT', 'Cao đẳng', 'Đại học', 'Thạc sĩ', 'Khác'])],
                'gpa' => ['label' => 'GPA', 'type' => 'gpa', 'hint' => 'Thang 10'],
                'german_level' => ['label' => 'Tiếng Đức', 'type' => 'select', 'options' => $same(['Chưa học', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'])],
                'english_level' => ['label' => 'Tiếng Anh', 'type' => 'text', 'hint' => 'VD: IELTS 6.5'],
                'desired_major' => ['label' => 'Ngành học mong muốn', 'type' => 'text'],
                'desired_state' => ['label' => 'Bang mong muốn', 'type' => 'select', 'options' => $same(self::GERMAN_STATES)],
            ]],
            'consult' => ['no' => 'III', 'title' => 'Nhu cầu tư vấn du học', 'fields' => [
                'program_interest' => ['label' => 'Chương trình quan tâm', 'type' => 'select', 'options' => $same(['Dự bị đại học (Studienkolleg)', 'Đại học', 'Thạc sĩ', 'Du học nghề (Ausbildung)', 'Khoá tiếng Đức', 'Khác'])],
                'budget' => ['label' => 'Ngân sách dự kiến', 'type' => 'text', 'hint' => 'VD: 300–400 triệu/năm'],
                'expected_time' => ['label' => 'Thời gian dự kiến', 'type' => 'text', 'hint' => 'VD: Kỳ mùa đông 2027'],
                'profile_status' => ['label' => 'Tình trạng hồ sơ', 'type' => 'select', 'options' => $same(['Chưa có hồ sơ', 'Đang chuẩn bị', 'Đã nộp hồ sơ', 'Đã có thư mời', 'Đã có visa'])],
                'note' => ['label' => 'Ghi chú khi tiếp nhận', 'type' => 'textarea', 'full' => true],
            ]],
            'holland' => ['no' => 'IV', 'title' => 'Thông tin Holland', 'fields' => collect(self::HOLLAND_TYPES)
                ->mapWithKeys(fn ($t, $l) => ['holland_'.strtolower($l) => ['label' => "Điểm {$l}", 'type' => 'score', 'hint' => $t[0]]])
                ->all(),
            ],
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest()->latest('id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->orderBy('scheduled_at');
    }

    /** Tìm khách theo mã VIGER-LEAD-00001 (customer_code các kênh gửi lại, utm_content của link Holland). */
    public static function findByCode(?string $code): ?self
    {
        return $code && preg_match('/^VIGER-LEAD-(\d+)$/', trim($code), $m) ? static::find((int) $m[1]) : null;
    }

    /** Link trắc nghiệm Holland gắn mã khách: kết quả gửi về CRM tự nối vào đúng hồ sơ này. */
    public function hollandInviteUrl(): string
    {
        return rtrim((string) config('crm.holland_url'), '/').'/?'.http_build_query([
            'utm_source' => 'crm', 'utm_medium' => 'tu_van', 'utm_content' => $this->inviteRef(),
        ]);
    }

    /** Mã trong link mời: VIGER-LEAD-00001.<chữ ký 10 ký tự> — chống đoán mã để gắn kết quả vào hồ sơ người khác. */
    public function inviteRef(): string
    {
        return $this->code().'.'.substr(hash_hmac('sha256', $this->code(), (string) config('app.key')), 0, 10);
    }

    /** Khách theo mã trong link mời; chữ ký sai => null (rơi về khớp SĐT / email như thường). */
    public static function findByInviteRef(?string $ref): ?self
    {
        [$code, $sig] = array_pad(explode('.', (string) $ref, 2), 2, '');
        $lead = static::findByCode($code);

        return $lead && hash_equals(substr($lead->inviteRef(), -10), $sig) ? $lead : null;
    }

    /** Mã khách hàng hiển thị: VIGER-LEAD-00001 */
    public function code(): string
    {
        return 'VIGER-LEAD-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUSES[$this->status][1] ?? 'badge-gray';
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** Điểm Holland dạng ['R' => 80, ...] (bỏ qua nhóm chưa có điểm) */
    public function hollandScores(): array
    {
        return collect(self::HOLLAND_TYPES)
            ->map(fn ($t, $l) => $this->{'holland_'.strtolower($l)})
            ->reject(fn ($v) => $v === null)
            ->all();
    }

    /** Mã Holland = 3 nhóm điểm cao nhất, VD "ISR". Null nếu chưa có điểm. */
    public function hollandCode(): ?string
    {
        // Mã do hệ thống Holland tính (code_full) được ưu tiên hơn mã tự tính từ điểm
        if ($this->holland_code) {
            return $this->holland_code;
        }

        $scores = $this->hollandScores();

        if (! $scores) {
            return null;
        }

        arsort($scores);

        return implode('', array_slice(array_keys($scores), 0, 3));
    }

    /**
     * Gắn kết quả Holland nhận từ hệ thống Holland (holland-result/3, điểm trung bình 1–5).
     * Lưu nguyên gói + mã 3 chữ; quy đổi điểm sang thang 0–100 (1 → 0, 5 → 100) cho các cột holland_r…c
     * để thanh điểm và bộ lọc hiện có dùng chung. Số hiển thị lấy từ gói gốc (x/5).
     */
    public function applyHollandResult(array $result): void
    {
        $this->holland_result = $result;
        $this->holland_code = isset($result['code_full']) ? mb_substr((string) $result['code_full'], 0, 3) : null;

        foreach (array_keys(self::HOLLAND_TYPES) as $l) {
            $avg = $result['scores'][$l] ?? null;
            $this->{'holland_'.strtolower($l)} = is_numeric($avg)
                ? (int) round(max(0, min(100, ((float) $avg - 1) / 4 * 100)))
                : null;
        }
    }

    /** Điểm trung bình gốc (1–5) của một nhóm nếu kết quả đến từ hệ thống Holland. */
    public function hollandAverage(string $letter): ?float
    {
        $v = $this->holland_result['scores'][$letter] ?? null;

        return is_numeric($v) ? (float) $v : null;
    }

    /** Giá trị hiển thị của một trường theo khai báo trong sections(). */
    public function displayValue(string $field, array $def): ?string
    {
        $value = $this->{$field};

        if ($value === null || $value === '') {
            return null;
        }

        return match (true) {
            ($def['options'] ?? null) === 'consultants' => $this->assignee?->displayName(),
            is_array($def['options'] ?? null) => $def['options'][$value] ?? $value,
            $def['type'] === 'date' => $value->format('d/m/Y'),
            $def['type'] === 'gpa' => rtrim(rtrim((string) $value, '0'), '.'),
            default => (string) $value,
        };
    }

    /**
     * Đổi trạng thái và ghi lịch sử "Đổi trạng thái: A → B." (trang Đã chuyển đổi đọc dòng này để lấy ngày chốt).
     * Trả về false nếu trạng thái không đổi.
     */
    public function moveToStatus(string $status): bool
    {
        $old = $this->status;

        if ($old === $status) {
            return false;
        }

        $this->update(['status' => $status]);
        $this->log('Đổi trạng thái: '.(self::STATUSES[$old][0] ?? $old).' → '.$this->statusLabel().'.');

        return true;
    }

    /** Ghi một dòng vào lịch sử tư vấn. */
    public function log(string $content, string $type = 'system', ?int $userId = null): LeadActivity
    {
        return $this->activities()->create([
            'type' => $type,
            'content' => $content,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    protected static function booted(): void
    {
        static::created(function (Lead $lead) {
            $lead->log('CRM tiếp nhận khách hàng từ nguồn '.$lead->sourceLabel().'.');

            if ($lead->assignee) {
                $lead->log('Phân công tư vấn viên: '.$lead->assignee->displayName().'.');
            }
        });
    }

    /**
     * Phạm vi dữ liệu theo vai trò (đặc tả: lọc ở truy vấn, không lọc ở view):
     *   all  — mọi khách
     *   team — khách giao cho thành viên nhóm phụ trách + khách chưa phân công (để giao việc)
     *   own  — chỉ khách được giao cho chính mình
     * Không có quyền leads.view (VD Marketing) => không thấy khách nào.
     * $aggregate = true: dùng cho số liệu tổng hợp (báo cáo, nguồn) — bỏ qua yêu cầu leads.view, vẫn theo phạm vi.
     */
    public function scopeVisibleTo(Builder $query, User $user, bool $aggregate = false): Builder
    {
        if (! $aggregate && ! $user->crmCan('leads.view')) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->crmScope()) {
            'all' => $query,
            'team' => $query->where(fn (Builder $q) => $q
                ->whereNull('assigned_to')
                ->orWhereIn('assigned_to', $user->teamMemberIds())),
            default => $query->where('assigned_to', $user->id),
        };
    }

    /**
     * Áp dụng bộ lọc từ query string: q, status, source, assigned_to ("none" = chưa gán), date.
     */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['q'] ?? null, function (Builder $q, string $term) {
                $q->where(fn (Builder $w) => $w
                    ->where('full_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('student_name', 'like', "%{$term}%"));
            })
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['source'] ?? null, fn (Builder $q, $v) => $q->where('source', $v))
            ->when($f['assigned_to'] ?? null, fn (Builder $q, $v) => $v === 'none'
                ? $q->whereNull('assigned_to')
                : $q->where('assigned_to', $v))
            ->when($f['date'] ?? null, fn (Builder $q, $v) => match ($v) {
                'today' => $q->whereDate('created_at', today()),
                '7d' => $q->where('created_at', '>=', now()->subDays(7)),
                '30d' => $q->where('created_at', '>=', now()->subDays(30)),
                'this_month' => $q->where('created_at', '>=', now()->startOfMonth()),
                default => $q,
            });
    }
}
