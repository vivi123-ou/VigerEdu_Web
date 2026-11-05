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
 * Phần A tách 3 câu gộp 2 trường của bản gốc và thêm câu mới (đã thống nhất với Vân):
 *   A06 → A06 Họ tên phụ huynh + A16 SĐT phụ huynh
 *   A09 → A09 Tiếng Anh + A17 Tiếng Đức
 *   A11 → A11 Quốc gia + A18 Khu vực
 *   Mới: A19 Ngày sinh (extra birth_date, tự điền birth_year), A20 Trường đang học (current_school),
 *        A21 Tỉnh / Thành phố (province), A22 Email phụ huynh (extra parent_email)
 * Tên trường gửi CRM (lead_field) và giá trị lựa chọn theo docs/TU_DIEN_DU_LIEU_CHUNG.xlsx
 * và docs/lua_chon_chuan.json.
 */
class QuestionSeeder extends Seeder
{
    public const DEFAULT_SET_CODE = 'thpt';

    /**
     * Cấu hình Phần A (form "Nhận kết quả của bạn", hiện SAU khi làm xong câu hỏi).
     * Câu bắt buộc hiện ngay; câu không bắt buộc nằm trong phần "Thêm thông tin" (thu gọn); ô đồng ý ở cuối.
     * text = null nghĩa là lấy câu chữ từ questions.json.
     * options: tên danh sách trong lua_chon_chuan.json (hoặc 'provinces'), hoặc mảng [{value, label}].
     * lead: trường chuẩn của lead/1; extra: mã trường tùy biến gửi trong extra_fields.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PART_A = [
        'A02' => ['sort' => 10, 'type' => 'text', 'lead' => 'student_name', 'required' => true, 'text' => 'Họ và tên', 'placeholder' => 'Ví dụ: Nguyễn Văn An', 'help' => 'Nếu phụ huynh điền giúp, vui lòng ghi tên của con.'],
        'A19' => ['sort' => 20, 'type' => 'date', 'extra' => 'birth_date', 'required' => true, 'text' => 'Ngày sinh'],
        'A03' => ['sort' => 21, 'type' => 'number', 'lead' => 'birth_year', 'active' => false, 'note' => 'Tự điền từ Ngày sinh (A19), không hỏi khách.'],
        'A05' => ['sort' => 30, 'type' => 'email', 'lead' => 'email', 'required' => true, 'text' => 'Email', 'placeholder' => 'Ví dụ: ten@gmail.com', 'help' => 'Kết quả và tư vấn chi tiết sẽ được gửi vào email này.'],
        'A04' => ['sort' => 40, 'type' => 'phone', 'lead' => 'phone', 'required' => true, 'text' => 'Số điện thoại', 'placeholder' => 'Ví dụ: 0912 345 678'],
        'A01' => ['sort' => 50, 'type' => 'single', 'lead' => 'role', 'options' => 'role', 'required' => true, 'text' => 'Bạn là'],
        'A07' => ['sort' => 60, 'type' => 'single', 'lead' => 'current_level', 'options' => 'current_level', 'required' => true, 'text' => 'Hiện bạn (hoặc con) đang là'],
        'A20' => ['sort' => 70, 'type' => 'text', 'lead' => 'current_school', 'required' => true, 'text' => 'Trường đang học', 'placeholder' => 'Ví dụ: THPT Lê Hồng Phong', 'help' => 'Đã tốt nghiệp thì ghi trường gần nhất.'],
        'A21' => ['sort' => 80, 'type' => 'single', 'lead' => 'province', 'options' => 'provinces', 'required' => true, 'text' => 'Tỉnh / Thành phố'],
        'A06' => ['sort' => 90, 'type' => 'text', 'lead' => 'parent_name', 'text' => 'Họ tên phụ huynh / người giám hộ', 'show_if' => ['question' => 'A01', 'in' => ['HS']]],
        'A16' => ['sort' => 91, 'type' => 'phone', 'lead' => 'parent_phone', 'text' => 'Số điện thoại phụ huynh / người giám hộ', 'show_if' => ['question' => 'A01', 'in' => ['HS']], 'placeholder' => 'Ví dụ: 0987 654 321'],
        'A22' => ['sort' => 92, 'type' => 'email', 'extra' => 'parent_email', 'text' => 'Email phụ huynh / người giám hộ', 'show_if' => ['question' => 'A01', 'in' => ['HS']]],
        'A14' => ['sort' => 100, 'type' => 'text', 'lead' => 'interested_major', 'text' => 'Ngành bạn đang quan tâm', 'placeholder' => 'Ví dụ: Cơ khí, Y khoa… hoặc "Chưa biết"'],
        'A10' => ['sort' => 110, 'type' => 'single', 'lead' => 'desired_level', 'options' => 'desired_level', 'text' => 'Bậc học muốn theo'],
        'A09' => ['sort' => 120, 'type' => 'single', 'lead' => 'english_level', 'options' => 'english_level', 'text' => 'Trình độ tiếng Anh hiện tại'],
        'A17' => ['sort' => 121, 'type' => 'single', 'lead' => 'german_level', 'options' => 'german_level', 'text' => 'Trình độ tiếng Đức hiện tại'],
        'A08' => ['sort' => 130, 'type' => 'number', 'lead' => 'gpa', 'text' => 'Điểm trung bình gần nhất (thang 10)', 'placeholder' => 'Ví dụ: 8.2'],
        'A11' => ['sort' => 140, 'type' => 'single', 'lead' => 'country', 'options' => [['value' => 'Đức', 'label' => 'Đức']], 'text' => 'Quốc gia muốn đi học'],
        'A18' => ['sort' => 141, 'type' => 'single', 'lead' => 'region', 'options' => 'regions', 'text' => 'Bang / khu vực mong muốn', 'help' => 'Để trống nếu chưa rõ.'],
        'A12' => ['sort' => 150, 'type' => 'single', 'lead' => 'budget', 'options' => 'budget', 'text' => 'Ngân sách dự kiến mỗi năm'],
        'A13' => ['sort' => 160, 'type' => 'single', 'lead' => 'timing', 'options' => 'timing', 'text' => 'Dự định đi học khi nào'],
        'A15' => ['sort' => 200, 'type' => 'consent', 'lead' => 'consent', 'required' => true],
    ];

    /**
     * 34 tỉnh / thành phố sau sắp xếp đơn vị hành chính năm 2025 + "Ngoài Việt Nam" (admin sửa được).
     *
     * @var list<string>
     */
    private const PROVINCES = [
        'Hà Nội', 'TP. Hồ Chí Minh', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ', 'Huế',
        'An Giang', 'Bắc Ninh', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp',
        'Gia Lai', 'Hà Tĩnh', 'Hưng Yên', 'Khánh Hòa', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai',
        'Nghệ An', 'Ninh Bình', 'Phú Thọ', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sơn La', 'Tây Ninh',
        'Thái Nguyên', 'Thanh Hóa', 'Tuyên Quang', 'Vĩnh Long', 'Ngoài Việt Nam',
    ];

    public function run(): void
    {
        $source = collect($this->readJson(config('holland.seed_path').'/questions.json'))->keyBy('code');
        $choices = $this->readJson(base_path('docs/lua_chon_chuan.json'));
        $choices['provinces'] = array_map(fn (string $name) => ['value' => $name, 'label' => $name], self::PROVINCES);
        $choices['regions'] = $this->regionChoices();

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
                'lead_field' => $spec['lead'] ?? null,
                'extra_field_key' => $spec['extra'] ?? null,
                'sort_order' => $spec['sort'],
                'is_active' => $spec['active'] ?? (bool) ($original['is_active'] ?? true),
                'note' => $spec['note'] ?? null,
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
     * Khu vực từ regions.json (dữ liệu thật do Ý thu thập, chốt 05/11/2026): chỉ khu vực đang bật.
     * Lưu và gửi CRM bằng TÊN bang (ví dụ "Hessen") cho nhân viên dễ đọc; mã bang (HE…) nằm trong regions.json.
     *
     * @return list<array{value: string, label: string}>
     */
    private function regionChoices(): array
    {
        $regions = $this->readJson(config('holland.seed_path').'/regions.json');

        return array_values(array_map(
            fn (array $region) => ['value' => (string) $region['name'], 'label' => (string) $region['name']],
            array_filter($regions, fn (array $region) => (bool) ($region['is_active'] ?? true)),
        ));
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
