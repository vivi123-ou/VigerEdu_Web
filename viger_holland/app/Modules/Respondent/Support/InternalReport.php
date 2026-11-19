<?php

namespace App\Modules\Respondent\Support;

use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\Occupation;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\SessionQuestion;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Assessment\Support\CustomerReport;
use App\Modules\Assessment\Support\Hexagon;
use App\Modules\CrmSync\Models\CrmOutbox;
use App\Modules\CrmSync\Services\CrmConfig;
use App\Modules\Respondent\Models\FormField;
use App\Modules\Respondent\Services\ReportForm;
use App\Modules\Schools\Filament\Pages\SchoolsCatalog;
use Illuminate\Support\Carbon;

/**
 * Làm gì:   Dữ liệu báo cáo NỘI BỘ của một lượt làm bài (tư vấn viên / quản trị viên đọc trước khi tư vấn), theo mẫu
 *           admin-filament.html màn hình 3: thông tin khách, mã khách đang thấy / mã đầy đủ / mã thay thế, độ tin cậy,
 *           các ý "Đọc trước khi tư vấn" tự viết từ kết quả, điểm 6 nhóm, lục giác (so với lần trước), ngành gợi ý đủ cột,
 *           3 cặp trong mã, 36 câu đã trả lời, diễn biến, JSON cho AI (không có họ tên, liên lạc).
 * Ai gọi:   trang Filament InternalReportPage.
 * Dữ liệu:  test_sessions (result đã lưu), respondents, field_values, session_questions, responses, majors, occupations.
 *           Có dữ liệu cá nhân: chỉ hiện trên trang đã đăng nhập, không ghi vào log.
 */
final class InternalReport
{
    /** Tên dễ hiểu của các cờ chất lượng bài làm (ScoringEngine::quality). */
    private const FLAGS = [
        'mot_muc_chiem' => 'Chọn gần như một mức cho mọi câu',
        'chuoi_giong_nhau' => 'Nhiều câu liên tiếp chọn cùng mức',
        'qua_nhanh' => 'Làm quá nhanh',
        'doan_bam_voi' => 'Có đoạn bấm vội',
        'nhom_thieu_du_lieu' => 'Nhóm thiếu câu trả lời',
        'nhieu_khong_biet' => 'Bỏ trống nhiều câu',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function make(TestSession $session): array
    {
        $result = $session->result;
        $groups = $result['groups'];
        $order = $result['order'];
        $types = RiasecType::query()->pluck('name', 'code');
        $name = fn (string $g): string => (string) ($types[$g] ?? $g);
        $settings = app(AssessmentService::class)->settingsFor($session);

        $respondent = $session->respondent;
        $attempts = $respondent
            ? $respondent->testSessions()->whereNotNull('verified_at')->orderBy('verified_at')->get(['id', 'uuid', 'verified_at', 'scores', 'code3', 'result'])
            : collect([$session]);
        $attemptNumber = $attempts->search(fn (TestSession $s) => $s->id === $session->id);
        $previous = $attemptNumber ? $attempts[$attemptNumber - 1] : null;

        return [
            'session' => $session,
            'reportCode' => self::code($session),
            'customer' => self::customer($session),
            'attempt' => ($attemptNumber === false ? 0 : $attemptNumber) + 1,
            'attemptCount' => $attempts->count(),
            'duration' => $session->started_at && $session->answered_at ? $session->started_at->diff($session->answered_at) : null,
            'customerCode' => $result['customer']['code'],
            'code3' => $result['code3'],
            'alternates' => $result['alternates'] ?? [],
            'reliability' => $result['quality']['reliability'] ?? null,
            'flags' => collect($result['quality']['flags'] ?? [])->map(fn (array $f) => [
                'label' => self::FLAGS[$f['code']] ?? $f['code'], 'detail' => $f['detail'], 'heavy' => $f['level'] === 'nang',
            ])->all(),
            'summary' => self::summary($result, $settings, $name),
            'scores' => collect($order)->map(fn (string $g, int $i) => [
                'code' => $g, 'name' => $name($g), 'mean' => $groups[$g]['mean'], 'band' => $groups[$g]['band'],
                'width' => (int) round($groups[$g]['mean'] / 5 * 100), 'rank' => $i + 1,
                'previous' => $previous?->result['groups'][$g]['mean'] ?? null,
            ])->all(),
            'relations' => collect($result['relations'])->map(fn (array $r) => [
                'pair' => $r['pair'], 'names' => $name($r['pair'][0]).' – '.$name($r['pair'][1]), 'relation' => $r['relation'],
            ])->values()->all(),
            'consistency' => $result['consistency'] ?? null,
            'hexagon' => Hexagon::make($groups, str_split($result['code3']), $name),
            'previous' => $previous ? [
                'date' => $previous->verified_at, 'code3' => $previous->code3,
                'polygon' => Hexagon::polygon($previous->result['groups']),
            ] : null,
            'majors' => self::majors($result, (int) $settings['max_majors_public']),
            'answers' => self::answers($session, array_values($settings['likert_labels'])),
            'timeline' => self::timeline($session),
            'aiJson' => self::aiJson($session),
            'crmUrl' => app(CrmConfig::class)->customerUrl($session->respondent?->crm_customer_code),
        ];
    }

    /**
     * Mã báo cáo: 8 ký tự đầu của mã lượt làm bài, viết hoa (khách đọc cho tư vấn viên để tra cứu).
     */
    public static function code(TestSession $session): string
    {
        return strtoupper(substr(str_replace('-', '', $session->uuid), 0, 8));
    }

    /**
     * Thông tin khách: hồ sơ (họ tên, email, SĐT, SĐT phụ huynh, ngày sinh) + các ô khác khách điền (trường, tỉnh…).
     *
     * @return array<string, mixed>
     */
    private static function customer(TestSession $session): array
    {
        $respondent = $session->respondent;
        $fields = FormField::withTrashed()->get()->keyBy('key');
        $values = app(ReportForm::class)->values($session);
        $label = function (string $key, ?string $value) use ($fields): ?string {
            $option = collect($fields[$key]->options ?? [])->firstWhere('value', $value);

            return $option['label'] ?? $value;
        };

        $extra = collect($values)
            ->except(['ho_ten', 'email', 'so_dien_thoai', 'sdt_phu_huynh', 'ngay_sinh', 'nam_sinh', 'doi_tuong'])
            ->mapWithKeys(fn (string $value, string $key) => [($fields[$key]->label ?? $key) => $label($key, $value)])
            ->all();

        $birth = $respondent?->birth_date ?: ($respondent?->birth_year ? (string) $respondent->birth_year : null);

        return [
            'name' => $respondent?->full_name ?: ($values['ho_ten'] ?? '—'),
            'email' => $respondent?->email,
            'phone' => $respondent?->phone,
            'parentPhone' => $respondent?->parent_phone,
            'birth' => $birth && strlen($birth) === 10 ? Carbon::parse($birth)->format('d/m/Y') : $birth,
            'role' => $label('doi_tuong', $respondent?->role ?? ($values['doi_tuong'] ?? null)),
            'extra' => $extra,
        ];
    }

    /**
     * Các ý "Đọc trước khi tư vấn", viết từ kết quả đã lưu.
     *
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $settings
     * @return list<array{0: string, 1: string}>
     */
    private static function summary(array $result, array $settings, callable $name): array
    {
        $groups = $result['groups'];
        [$g1, $g2, $g3] = $result['order'];
        $score = fn (string $g): string => $name($g).' ('.self::number($groups[$g]['mean']).')';
        $lines = [['Nổi bật', $score($g1).' và '.$score($g2).'.']];

        if (strlen((string) $result['customer']['code']) === 2) {
            $lines[] = ['Vì sao khách chỉ thấy 2 chữ', match (true) {
                ! empty($result['third_detail']) => 'nhóm thứ 3 là '.$score($g3).', đủ điểm nhưng trang khách đang đặt luôn hiện mã 2 chữ.',
                default => 'nhóm thứ 3 là '.$score($g3).', chưa qua mức '.self::number((float) $settings['third_letter_threshold']).'.',
            }];
        }

        if (! empty($result['alternates'])) {
            $lines[] = ['Nên hỏi thêm', 'có nhóm sát điểm nhau, mã thay thế: '.implode(', ', $result['alternates']).'.'];
        }

        $first = $result['relations']['1-2'];
        $lines[] = ['Kết hợp '.$first['pair'][0].' – '.$first['pair'][1], mb_strtolower($first['relation']).' trên lục giác, độ nhất quán '.mb_strtolower((string) $result['consistency']).'.'];

        if (! empty($result['profile']['needs_counseling'])) {
            $lines[] = ['Cần tư vấn kỹ', 'không quá 1 nhóm trên mức quan tâm trung bình, sở thích chưa rõ nét.'];
        }

        $flags = collect($result['quality']['flags'] ?? [])->map(fn (array $f) => (self::FLAGS[$f['code']] ?? $f['code']).' ('.$f['detail'].')');
        $lines[] = ['Bài làm', $flags->isEmpty() ? 'không thấy dấu hiệu làm vội hay chọn một kiểu.' : $flags->implode('; ').'.'];

        return $lines;
    }

    /**
     * Toàn bộ ngành của lượt làm bài (bản nội bộ), đánh dấu ngành khách thấy.
     *
     * @param  array<string, mixed>  $result
     * @return list<array<string, mixed>>
     */
    private static function majors(array $result, int $publicLimit): array
    {
        $shown = array_column($result['customer']['majors'] ?? [], 'id');
        $models = Major::query()->whereIn('code', array_column($result['majors'] ?? [], 'id'))->with(['occupations', 'programs' => fn ($q) => $q->with('school:id,name,short_name,city')->orderByDesc('is_verified')])->get()->keyBy('code');

        return array_map(function (array $row) use ($shown, $models): array {
            $major = $models[$row['id']] ?? null;

            return [
                'rank' => $row['rank'] ?? null,
                'name_vi' => $row['name_vi'], 'name_de' => $row['name_de'] ?? null,
                'code' => $row['code'] ?? null, 'c' => $row['c'] ?? null,
                'label' => ($row['source'] ?? null) === 'pair' ? 'Ngành nền của cặp '.($row['pair'] ?? '') : $row['label'],
                'shown' => in_array($row['id'], $shown, true),
                'routes' => $row['routes'] ?? null,
                'verified' => (bool) ($row['verified'] ?? false),
                'occupations' => $major ? $major->occupations->take(3)->map(fn (Occupation $o) => CustomerReport::occupationName($o))->implode(', ') : '',
                'schools' => $major ? $major->programs->unique('school_id')->take(5)
                    ->map(fn ($p) => ($p->school->short_name ?: $p->school->name).($p->school->city ? ' ('.$p->school->city.')' : ''))->values()->all() : [],
                'schools_total' => $major ? $major->programs->unique('school_id')->count() : 0,
                'schools_url' => $major ? SchoolsCatalog::getUrl(['the' => 'chuong-trinh', 'nganh' => $major->id]) : null,
            ];
        }, $result['majors'] ?? []);
    }

    /**
     * 36 câu theo thứ tự khách làm: mã, nhóm, chủ đề, nội dung lúc phát đề, mức khách chọn, số giây.
     *
     * @param  list<string>  $labels  5 nhãn sao
     * @return list<array<string, mixed>>
     */
    private static function answers(TestSession $session, array $labels): array
    {
        $responses = $session->responses()->get()->keyBy('question_id');

        return $session->sessionQuestions()->with('question.facet')->orderBy('display_order')->get()
            ->map(function (SessionQuestion $item) use ($responses, $labels): array {
                $response = $responses[$item->question_id] ?? null;

                return [
                    'order' => $item->display_order,
                    'code' => $item->question?->code,
                    'group' => $item->group,
                    'facet' => $item->question?->facet?->name,
                    'text' => $item->text_snapshot,
                    'value' => $response?->value,
                    'label' => $response ? ($labels[$response->value - 1] ?? null) : null,
                    'seconds' => $response?->ms !== null ? round($response->ms / 1000, 1) : null,
                ];
            })->all();
    }

    /**
     * @return list<array{0: Carbon, 1: string}>
     */
    private static function timeline(TestSession $session): array
    {
        $source = $session->utm['utm_source'] ?? null;

        $lead = CrmOutbox::query()->where('test_session_id', $session->id)->where('schema', 'lead/2')->latest('id')->first();
        $crm = match ($lead?->status) {
            CrmOutbox::SENT => [$lead->sent_at, 'Đã gửi sang CRM'.(filled($session->respondent?->crm_customer_code) ? ' (mã khách '.$session->respondent->crm_customer_code.')' : '')],
            CrmOutbox::FAILED => [$lead->updated_at, 'Gửi sang CRM bị lỗi, quản trị viên xem ở "Gửi khách sang CRM"'],
            CrmOutbox::PENDING, CrmOutbox::DISABLED => [$lead->created_at, 'Đang chờ gửi sang CRM'],
            default => [null, ''],
        };

        return collect([
            $crm,
            [$session->report_viewed_at, 'Khách mở báo cáo đầy đủ'],
            [$session->verified_at, 'Khách điền thông tin và nhập đúng mã gửi qua email'],
            [$session->answered_at, 'Làm xong bài'.($source ? ' (đến từ '.$source.')' : '')],
            [$session->started_at, 'Bắt đầu làm bài'],
        ])->filter(fn (array $row) => $row[0] !== null)->values()->all();
    }

    /**
     * JSON cho AI / chatbot: kết quả và câu trả lời, KHÔNG có họ tên, email, số điện thoại.
     */
    private static function aiJson(TestSession $session): string
    {
        $result = $session->result;
        $payload = [
            'schema' => $result['schema'] ?? 'holland-result/3',
            'report_code' => self::code($session),
            'completed_at' => $session->answered_at?->toIso8601String(),
            'code2' => $result['code2'], 'code3' => $result['code3'], 'customer_code' => $result['customer']['code'],
            'alternates' => $result['alternates'] ?? [],
            'scores' => collect($result['groups'])->map(fn (array $g) => $g['mean'])->all(),
            'relations' => $result['relations'], 'consistency' => $result['consistency'] ?? null,
            'profile' => $result['profile'] ?? null, 'quality' => $result['quality'] ?? null,
            'majors' => collect($result['majors'] ?? [])->map(fn (array $m) => array_intersect_key($m, array_flip(['id', 'name_vi', 'name_de', 'code', 'c', 'label', 'source', 'verified'])))->all(),
            'answers' => $session->responses()->with('question')->get()->map(fn ($r) => ['code' => $r->question?->code, 'value' => $r->value, 'ms' => $r->ms])->all(),
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Nhãn đối tượng (HS → Học sinh / sinh viên) theo lựa chọn của ô "Thuộc đối tượng" trong form.
     */
    public static function roleLabel(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }
        $options = FormField::withTrashed()->where('key', 'doi_tuong')->value('options') ?? [];
        $options = is_string($options) ? (json_decode($options, true) ?: []) : $options;

        return collect($options)->firstWhere('value', $role)['label'] ?? $role;
    }

    public static function number(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
