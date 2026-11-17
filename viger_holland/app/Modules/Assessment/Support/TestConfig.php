<?php

namespace App\Modules\Assessment\Support;

use App\Models\User;
use App\Modules\Assessment\Models\TestConfigVersion;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Làm gì:   Mọi con số của bài test: rút đề, trang làm bài, chấm điểm, gợi ý ngành, cảnh báo chất lượng. Lưu theo phiên bản.
 * Ai gọi:   trang Cài đặt bài test; QuestionPicker, ScoringService, ProfileAnalyzer, MajorMatcher (H2); TestConfigSeeder.
 * Dữ liệu:  bảng test_config_versions (dòng mới nhất là bản đang dùng). Mặc định theo tài liệu của Thầy và quyết định 11–14/11/2026.
 */
final class TestConfig
{
    /**
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        // Rút đề
        'items_per_group' => 6,
        'items_per_page' => 6,
        'min_distinct_facets' => 4,
        'max_generation_attempts' => 100,
        'avoid_repeat' => true,
        'use_anchors' => false,
        'tier_weight_a' => 5,
        'tier_weight_b' => 3,
        'tier_weight_c' => 1,
        // Thang ngôi sao (1 → 5 sao)
        'likert_labels' => ['Hoàn toàn không giống tôi', 'Không giống lắm', 'Phân vân', 'Khá giống tôi', 'Hoàn toàn giống tôi'],
        // Chấm điểm và đọc mã
        'interest_midpoint' => 3.0,
        'third_letter_threshold' => 4.2,
        'near_tie_gap' => 0.25,
        'bands' => [
            ['max' => 1.8, 'label' => 'Rất ít quan tâm'],
            ['max' => 2.6, 'label' => 'Ít quan tâm'],
            ['max' => 3.4, 'label' => 'Quan tâm trung bình'],
            ['max' => 4.2, 'label' => 'Quan tâm'],
            ['max' => 5.0, 'label' => 'Rất quan tâm'],
        ],
        // Gợi ý ngành
        'interest_mark' => 3.4,
        'c_labels' => [
            ['min' => 15, 'label' => 'Liên quan nhiều'],
            ['min' => 11, 'label' => 'Liên quan'],
            ['min' => 7, 'label' => 'Có thể khám phá'],
        ],
        'max_majors_public' => 5,
        // Chỉ số nền tảng học tập (chỉ tư vấn viên xem)
        'academic_labels' => [
            ['min' => 8.0, 'label' => 'Thuận lợi'],
            ['min' => 6.5, 'label' => 'Khá'],
            ['min' => 0.0, 'label' => 'Cần bổ sung'],
        ],
        // Cảnh báo chất lượng bài làm
        'one_option_share' => 0.9,
        'longstring' => 10,
        'fast_avg_seconds' => 2.0,
        'rushed_run' => 5,
        'rushed_seconds' => 1.0,
    ];

    /**
     * Các ô trên trang "Cài đặt bài test": [nhóm => [khóa => [nhãn, giải thích, kiểu, đơn vị]]].
     * Kiểu: int, decimal, bool, labels5 (5 nhãn thang), bands, c_labels, academic_labels.
     *
     * @var array<string, array<string, array{0: string, 1: string, 2: string, 3?: string}>>
     */
    public const FIELDS = [
        'Rút đề' => [
            'items_per_group' => ['Số câu mỗi nhóm', 'Mỗi lượt làm bài rút bấy nhiêu câu cho mỗi nhóm R, I, A, S, E, C. 6 câu → bài 36 câu.', 'int', 'câu'],
            'min_distinct_facets' => ['Số chủ đề khác nhau tối thiểu mỗi nhóm', 'Các câu rút ra của một nhóm phải thuộc ít nhất bấy nhiêu chủ đề hoạt động khác nhau (ví dụ nhóm R: lắp ráp, sửa chữa, vận hành…), để đề không bị lệch về một dạng hoạt động.', 'int', 'chủ đề'],
            'max_generation_attempts' => ['Số lần thử tối đa khi rút đề', 'Nếu rút mãi không đạt điều kiện chủ đề, sau bấy nhiêu lần phần mềm nới điều kiện và ghi cảnh báo cho quản trị viên.', 'int', 'lần'],
            'avoid_repeat' => ['Hạn chế lặp câu khi làm lại', 'Người làm lại trên cùng máy sẽ ưu tiên gặp câu khác lần trước (nhận ra bằng cookie, không dùng số điện thoại).', 'bool'],
            'use_anchors' => ['Dùng câu neo', 'Bật thì câu được đánh dấu "câu neo" luôn có trong mọi đề. Chỉ bật khi Thầy đã chọn câu neo cho nghiên cứu so sánh đề.', 'bool'],
            'tier_weight_a' => ['Trọng số mặc định câu loại A (lõi)', 'Câu mới thêm hoặc nhập Excel thuộc loại A nhận trọng số này. Trọng số cao thì hay được rút vào đề hơn. Không ảnh hưởng điểm của học sinh.', 'decimal'],
            'tier_weight_b' => ['Trọng số mặc định câu loại B (chuẩn)', 'Như trên, cho câu loại B.', 'decimal'],
            'tier_weight_c' => ['Trọng số mặc định câu loại C (mở rộng)', 'Như trên, cho câu loại C.', 'decimal'],
        ],
        'Trang làm bài' => [
            'items_per_page' => ['Số câu mỗi trang', 'Mỗi trang làm bài hiện bấy nhiêu câu; khách trả lời đủ mới bấm Tiếp tục được. 6 câu → 6 trang cho bài 36 câu.', 'int', 'câu'],
            'likert_labels' => ['Chữ của 5 mức sao', 'Chữ hiện cạnh hàng sao sau khi khách chọn và trong phần chú thích, từ 1 sao đến 5 sao. 3 sao là trung lập.', 'labels5'],
        ],
        'Chấm điểm và đọc mã Holland' => [
            'interest_midpoint' => ['Mốc "có quan tâm"', 'Điểm trung bình của nhóm phải TRÊN mốc này mới tính là nhóm nổi bật. 3 = điểm giữa thang (6 câu → trên 18/30). Ba nhóm đầu đều trên mốc: hồ sơ 3 nhóm; chỉ một nhóm hoặc không nhóm nào trên mốc: gắn cờ "Cần tư vấn riêng".', 'decimal', 'điểm'],
            'third_letter_threshold' => ['Ngưỡng hiện chữ thứ 3 cho khách', 'Khách thường thấy mã 2 chữ. Chỉ khi nhóm thứ 3 có điểm trung bình TRÊN ngưỡng này (và đủ số câu) thì khách mới thấy mã 3 chữ. 4,2 với 6 câu = từ 26/30 trở lên. Tư vấn viên luôn thấy đủ 3 chữ.', 'decimal', 'điểm'],
            'near_tie_gap' => ['Mức "sát điểm"', 'Hai nhóm liền nhau trong bảng xếp hạng cách nhau không quá bấy nhiêu điểm thì báo "sát điểm" và gợi ý mã thay thế cho tư vấn viên.', 'decimal', 'điểm'],
            'bands' => ['Nhãn mức độ quan tâm của từng nhóm', 'Điểm trung bình của nhóm đến mức nào thì hiện nhãn nào (đọc từ trên xuống, dòng cuối phải là 5).', 'bands'],
        ],
        'Gợi ý ngành' => [
            'interest_mark' => ['Mốc xét ngành ngoài cặp', 'Ngoài các ngành nền của cặp mã, một ngành khác chỉ được gợi ý khi nhóm đứng đầu mã ngành đó có điểm của học sinh TRÊN mốc này.', 'decimal', 'điểm'],
            'c_labels' => ['Nhãn mức liên quan theo chỉ số C', 'Chỉ số C (0–18) đo độ khớp giữa mã học sinh và mã ngành. Khách chỉ thấy nhãn chữ, không thấy số. Ngành dưới mốc thấp nhất không được gợi ý.', 'c_labels'],
            'max_majors_public' => ['Số ngành hiện cho khách', 'Báo cáo khách hiện tối đa bấy nhiêu ngành. Tư vấn viên xem được toàn bộ danh sách.', 'int', 'ngành'],
        ],
        'Nền tảng học tập (chỉ tư vấn viên xem)' => [
            'academic_labels' => ['Nhãn chỉ số nền tảng học tập', 'Chỉ số (thang 10) tính từ điểm học bạ và điểm môn theo tỉ trọng của từng cụm ngành. Đây là cơ sở tham khảo, không loại học sinh khỏi ngành nào.', 'academic_labels'],
        ],
        'Cảnh báo chất lượng bài làm' => [
            'one_option_share' => ['Tỉ lệ chọn cùng một mức', 'Nếu khách chọn cùng một mức cho từ tỉ lệ này số câu trở lên (0,9 = 90%), báo "trả lời một kiểu".', 'decimal'],
            'longstring' => ['Số câu liên tiếp cùng một mức', 'Chọn cùng một mức liên tiếp từ bấy nhiêu câu trở lên thì báo cho tư vấn viên.', 'int', 'câu'],
            'fast_avg_seconds' => ['Thời gian trung bình mỗi câu quá nhanh', 'Trung bình mỗi câu dưới bấy nhiêu giây thì báo "làm quá nhanh".', 'decimal', 'giây'],
            'rushed_run' => ['Chuỗi câu trả lời vội', 'Có từ bấy nhiêu câu liên tiếp trả lời dưới ngưỡng "trả lời vội" thì báo.', 'int', 'câu'],
            'rushed_seconds' => ['Ngưỡng "trả lời vội"', 'Một câu trả lời trong chưa tới bấy nhiêu giây được tính là vội.', 'decimal', 'giây'],
        ],
    ];

    /**
     * Cài đặt đang dùng (bản mới nhất, thiếu khóa nào lấy mặc định).
     *
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        return array_replace(self::DEFAULTS, self::currentVersion()?->settings ?? []);
    }

    public static function get(string $key): mixed
    {
        return self::current()[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function currentVersion(): ?TestConfigVersion
    {
        try {
            if (! Schema::hasTable('test_config_versions')) {
                return null;
            }

            return TestConfigVersion::query()->latest('id')->first();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Lưu thành phiên bản mới (chỉ khi khác bản đang dùng).
     *
     * @param  array<string, mixed>  $settings
     */
    public static function save(array $settings, ?User $user, string $note): ?TestConfigVersion
    {
        $settings = array_replace(self::DEFAULTS, array_intersect_key($settings, self::DEFAULTS));

        if ($settings == self::current() && self::currentVersion() !== null) {
            return null;
        }

        return TestConfigVersion::create(['settings' => $settings, 'note' => $note, 'user_id' => $user?->id]);
    }

    /**
     * Quy tắc kiểm tra dữ liệu form (thông báo tiếng Việt ở TestSettingsRequest).
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'items_per_group' => ['required', 'integer', 'min:2', 'max:20'],
            'items_per_page' => ['required', 'integer', 'min:1', 'max:36'],
            'min_distinct_facets' => ['required', 'integer', 'min:1', 'lte:items_per_group'],
            'max_generation_attempts' => ['required', 'integer', 'min:1', 'max:1000'],
            'avoid_repeat' => ['boolean'],
            'use_anchors' => ['boolean'],
            'tier_weight_a' => ['required', 'numeric', 'min:0', 'max:100'],
            'tier_weight_b' => ['required', 'numeric', 'min:0', 'max:100'],
            'tier_weight_c' => ['required', 'numeric', 'min:0', 'max:100'],
            'likert_labels' => ['required', 'array', 'size:5'],
            'likert_labels.*' => ['required', 'string', 'max:60'],
            'interest_midpoint' => ['required', 'numeric', 'min:1', 'max:5'],
            'third_letter_threshold' => ['required', 'numeric', 'min:1', 'max:5'],
            'near_tie_gap' => ['required', 'numeric', 'min:0', 'max:2'],
            'bands' => ['required', 'array', 'min:2', 'max:7'],
            'bands.*.max' => ['required', 'numeric', 'min:1', 'max:5'],
            'bands.*.label' => ['required', 'string', 'max:60'],
            'interest_mark' => ['required', 'numeric', 'min:1', 'max:5'],
            'c_labels' => ['required', 'array', 'min:1', 'max:5'],
            'c_labels.*.min' => ['required', 'integer', 'min:0', 'max:18'],
            'c_labels.*.label' => ['required', 'string', 'max:60'],
            'max_majors_public' => ['required', 'integer', 'min:1', 'max:20'],
            'academic_labels' => ['required', 'array', 'min:1', 'max:5'],
            'academic_labels.*.min' => ['required', 'numeric', 'min:0', 'max:10'],
            'academic_labels.*.label' => ['required', 'string', 'max:60'],
            'one_option_share' => ['required', 'numeric', 'min:0.5', 'max:1'],
            'longstring' => ['required', 'integer', 'min:2', 'max:100'],
            'fast_avg_seconds' => ['required', 'numeric', 'min:0', 'max:60'],
            'rushed_run' => ['required', 'integer', 'min:2', 'max:100'],
            'rushed_seconds' => ['required', 'numeric', 'min:0', 'max:60'],
        ];
    }

    /**
     * Chuẩn hóa dữ liệu form: số về đúng kiểu, công tắc về true/false, bảng nhãn sắp đúng thứ tự.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $settings = [];

        foreach (self::FIELDS as $fields) {
            foreach ($fields as $key => [, , $type]) {
                $value = $input[$key] ?? null;
                $settings[$key] = match ($type) {
                    'int' => (int) $value,
                    'decimal' => round((float) $value, 4),
                    'bool' => (bool) $value,
                    'labels5' => array_map(fn ($label) => trim((string) $label), array_values((array) $value)),
                    'bands' => collect($value)->map(fn ($row) => ['max' => round((float) $row['max'], 4), 'label' => trim((string) $row['label'])])->sortBy('max')->values()->all(),
                    'c_labels' => collect($value)->map(fn ($row) => ['min' => (int) $row['min'], 'label' => trim((string) $row['label'])])->sortByDesc('min')->values()->all(),
                    'academic_labels' => collect($value)->map(fn ($row) => ['min' => round((float) $row['min'], 2), 'label' => trim((string) $row['label'])])->sortByDesc('min')->values()->all(),
                };
            }
        }

        return $settings;
    }

    /**
     * Kiểm tra dữ liệu trước khi lưu: quy tắc của từng ô, cộng các ràng buộc giữa nhiều dòng
     * (bảng nhãn mức độ quan tâm phải kết thúc ở 5, mốc không trùng). Thông báo tiếng Việt, dùng tên ô.
     *
     * @param  array<string, mixed>  $input
     */
    public static function validator(array $input): ValidatorContract
    {
        $attributes = [];
        foreach (self::FIELDS as $fields) {
            foreach ($fields as $key => [$label]) {
                $attributes[$key] = '"'.$label.'"';
            }
        }

        $validator = Validator::make($input, self::rules(), [], $attributes + [
            'likert_labels.*' => 'chữ của mức sao',
            'bands.*.max' => 'mốc điểm',
            'c_labels.*.min' => 'mốc chỉ số C',
            'academic_labels.*.min' => 'mốc điểm',
            'bands.*.label' => 'nhãn', 'c_labels.*.label' => 'nhãn', 'academic_labels.*.label' => 'nhãn',
        ]);

        return $validator->after(function (ValidatorContract $validator) use ($input): void {
            $bands = collect((array) ($input['bands'] ?? []))->pluck('max')->map(fn ($value) => (float) $value);
            if ($bands->isNotEmpty() && abs($bands->max() - 5.0) > 0.0001) {
                $validator->errors()->add('bands', 'Dòng cuối của bảng nhãn mức độ quan tâm phải có mốc 5 (điểm cao nhất).');
            }
            if ($bands->count() !== $bands->unique()->count()) {
                $validator->errors()->add('bands', 'Các mốc trong bảng nhãn mức độ quan tâm không được trùng nhau.');
            }

            foreach (['c_labels' => 'chỉ số C', 'academic_labels' => 'nền tảng học tập'] as $table => $name) {
                $mins = collect((array) ($input[$table] ?? []))->pluck('min')->map(fn ($value) => (float) $value);
                if ($mins->count() !== $mins->unique()->count()) {
                    $validator->errors()->add($table, "Các mốc trong bảng nhãn {$name} không được trùng nhau.");
                }
            }
        });
    }

    /**
     * Mô tả ngắn các ô đã đổi, hiện trong lịch sử phiên bản ("Đổi Số câu mỗi trang: 6 → 4").
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function describeChanges(array $before, array $after): string
    {
        $format = fn (mixed $value): string => match (true) {
            is_bool($value) => $value ? 'bật' : 'tắt',
            is_float($value) => rtrim(rtrim(number_format($value, 4, ',', ''), '0'), ','),
            default => (string) $value,
        };

        $changed = collect($after)
            ->filter(fn ($value, $key) => ($before[$key] ?? null) != $value)
            ->map(fn ($value, string $key) => is_scalar($value) && is_scalar($before[$key] ?? null)
                ? self::label($key).': '.$format($before[$key]).' → '.$format($value)
                : self::label($key));

        return $changed->isEmpty() ? 'Lưu cài đặt' : 'Đổi '.$changed->implode('; ');
    }

    /**
     * Nhãn ngắn của một khóa, dùng trong lịch sử ("đổi Ngưỡng hiện chữ thứ 3: 4,2 → 4").
     */
    public static function label(string $key): string
    {
        foreach (self::FIELDS as $fields) {
            if (isset($fields[$key])) {
                return $fields[$key][0];
            }
        }

        return $key;
    }
}
