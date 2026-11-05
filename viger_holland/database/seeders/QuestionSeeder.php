<?php

namespace Database\Seeders;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Nạp câu hỏi mẫu từ database/seeders/data/holland/questions.json.
 *
 * Idempotent: chỉ TẠO câu chưa có (khớp theo mã câu, kể cả câu đã vào thùng rác),
 * không ghi đè câu admin đã sửa. Chạy lại bao nhiêu lần cũng không trùng.
 *
 * Phần A tách 3 câu gộp 2 trường của bản gốc (đã thống nhất với Vân):
 *   A06 → A06 Họ tên phụ huynh + A16 SĐT phụ huynh
 *   A09 → A09 Tiếng Anh + A17 Tiếng Đức
 *   A11 → A11 Quốc gia + A18 Khu vực
 * Tên trường gửi CRM (lead_field) và giá trị lựa chọn theo docs/TU_DIEN_DU_LIEU_CHUNG.xlsx
 * và docs/lua_chon_chuan.json.
 */
class QuestionSeeder extends Seeder
{
    public const DEFAULT_SET_CODE = 'thpt';

    /**
     * Cấu hình Phần A. text = null nghĩa là lấy câu chữ từ questions.json.
     * options: tên danh sách trong lua_chon_chuan.json, hoặc mảng [{value, label}].
     *
     * @var array<string, array<string, mixed>>
     */
    private const PART_A = [
        'A01' => ['sort' => 10, 'type' => 'single', 'lead' => 'role', 'options' => 'role', 'required' => true],
        'A02' => ['sort' => 20, 'type' => 'text', 'lead' => 'student_name', 'placeholder' => 'Ví dụ: Nguyễn Văn An'],
        'A03' => ['sort' => 30, 'type' => 'number', 'lead' => 'birth_year', 'placeholder' => 'Ví dụ: 2009', 'help' => 'Chỉ cần ghi năm, 4 chữ số.'],
        'A04' => ['sort' => 40, 'type' => 'phone', 'lead' => 'phone', 'placeholder' => 'Ví dụ: 0912 345 678', 'help' => 'Cần có số điện thoại hoặc email để nhận kết quả và được tư vấn.'],
        'A05' => ['sort' => 50, 'type' => 'email', 'lead' => 'email', 'placeholder' => 'Ví dụ: ten@gmail.com', 'help' => 'Kết quả bài test sẽ được gửi vào email này.'],
        'A06' => ['sort' => 60, 'type' => 'text', 'lead' => 'parent_name', 'text' => 'Họ tên phụ huynh', 'show_if' => ['question' => 'A01', 'in' => ['HS']], 'help' => 'Chỉ hỏi khi học sinh tự làm bài.'],
        'A16' => ['sort' => 61, 'type' => 'phone', 'lead' => 'parent_phone', 'text' => 'Số điện thoại phụ huynh', 'show_if' => ['question' => 'A01', 'in' => ['HS']], 'placeholder' => 'Ví dụ: 0987 654 321'],
        'A07' => ['sort' => 70, 'type' => 'single', 'lead' => 'current_level', 'options' => 'current_level'],
        'A08' => ['sort' => 80, 'type' => 'number', 'lead' => 'gpa', 'placeholder' => 'Ví dụ: 8.2', 'help' => 'Điểm trung bình thang 10, 1 chữ số thập phân.'],
        'A09' => ['sort' => 90, 'type' => 'single', 'lead' => 'english_level', 'options' => 'english_level', 'text' => 'Trình độ tiếng Anh hiện tại?'],
        'A17' => ['sort' => 91, 'type' => 'single', 'lead' => 'german_level', 'options' => 'german_level', 'text' => 'Trình độ tiếng Đức hiện tại?'],
        'A10' => ['sort' => 100, 'type' => 'single', 'lead' => 'desired_level', 'options' => 'desired_level'],
        'A11' => ['sort' => 110, 'type' => 'single', 'lead' => 'country', 'options' => [['value' => 'Đức', 'label' => 'Đức']], 'text' => 'Quốc gia muốn đi học?', 'help' => 'Quản trị viên có thể thêm quốc gia khác.'],
        'A18' => ['sort' => 111, 'type' => 'text', 'lead' => 'region', 'text' => 'Khu vực / thành phố mong muốn?', 'placeholder' => 'Ví dụ: Bayern, Berlin… (để trống nếu chưa rõ)'],
        'A12' => ['sort' => 120, 'type' => 'single', 'lead' => 'budget', 'options' => 'budget'],
        'A13' => ['sort' => 130, 'type' => 'single', 'lead' => 'timing', 'options' => 'timing'],
        'A14' => ['sort' => 140, 'type' => 'text', 'lead' => 'interested_major', 'placeholder' => 'Ví dụ: Cơ khí, Y khoa… hoặc ghi "Chưa biết"'],
        'A15' => ['sort' => 150, 'type' => 'consent', 'lead' => 'consent', 'required' => true],
    ];

    public function run(): void
    {
        $source = collect($this->readJson(config('holland.seed_path').'/questions.json'))->keyBy('code');
        $choices = $this->readJson(base_path('docs/lua_chon_chuan.json'));

        $this->seedPartA($source->all(), $choices);
        $this->seedPartB($source->where('part', Question::PART_HOLLAND)->values()->all());
    }

    /**
     * @param  array<string, array<string, mixed>>  $source
     * @param  array<string, list<array<string, mixed>>>  $choices
     */
    private function seedPartA(array $source, array $choices): void
    {
        foreach (self::PART_A as $code => $spec) {
            $original = $source[$code] ?? null;
            $text = $spec['text'] ?? $original['text'] ?? null;

            if ($text === null) {
                throw new RuntimeException("Thiếu câu chữ cho câu {$code} trong questions.json");
            }

            Question::withTrashed()->firstOrCreate(['code' => $code], [
                'part' => Question::PART_INFO,
                'text' => $text,
                'help_text' => $spec['help'] ?? null,
                'placeholder' => $spec['placeholder'] ?? null,
                'answer_type' => $spec['type'],
                'options' => $this->resolveOptions($spec['options'] ?? null, $choices, $code),
                'is_required' => $spec['required'] ?? false,
                'show_if' => $spec['show_if'] ?? null,
                'lead_field' => $spec['lead'],
                'sort_order' => $spec['sort'],
                'is_active' => (bool) ($original['is_active'] ?? true),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function seedPartB(array $rows): void
    {
        $set = QuestionSet::withTrashed()->firstOrCreate(['code' => self::DEFAULT_SET_CODE], [
            'name' => 'THPT – 54 câu',
            'description' => 'Bộ câu mặc định cho học sinh THPT: 6 nhóm × 9 câu, trả lời Đúng / Không.',
            'answer_type' => AnswerType::YesNo,
            'scale' => AnswerType::YesNo->defaultScale(),
            'per_page' => 9,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ($rows as $row) {
            if (! in_array($row['riasec'] ?? null, ['R', 'I', 'A', 'S', 'E', 'C'], true)) {
                throw new RuntimeException("Câu {$row['code']} thiếu nhóm RIASEC hợp lệ");
            }

            Question::withTrashed()->firstOrCreate(['code' => $row['code']], [
                'part' => Question::PART_HOLLAND,
                'question_set_id' => $set->id,
                'text' => $row['text'],
                'form' => $row['form'] ?? null,
                'answer_type' => AnswerType::YesNo,
                'riasec' => $row['riasec'],
                'max_score' => $row['max_score'] ?? 1,
                'is_required' => true,
                'sort_order' => (int) $row['sort_order'],
                'is_active' => (bool) ($row['is_active'] ?? true),
                'note' => $row['note'] ?? null,
            ]);
        }
    }

    /**
     * @param  string|list<array<string, mixed>>|null  $options
     * @param  array<string, list<array<string, mixed>>>  $choices
     * @return list<array{value: string, label: string}>|null
     */
    private function resolveOptions(string|array|null $options, array $choices, string $code): ?array
    {
        if ($options === null) {
            return null;
        }

        if (is_string($options)) {
            if (! isset($choices[$options])) {
                throw new RuntimeException("Câu {$code}: không có danh sách lựa chọn '{$options}' trong lua_chon_chuan.json");
            }
            $options = $choices[$options];
        }

        return array_map(fn (array $option): array => [
            'value' => (string) $option['value'],
            'label' => (string) $option['label'],
        ], $options);
    }

    /**
     * @return array<mixed>
     */
    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Không tìm thấy file dữ liệu: {$path}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
