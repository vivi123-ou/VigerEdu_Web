<?php

namespace App\Support;

use App\Models\AdminActivity;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Các khối nội dung của trang giới thiệu, lưu trong settings.intro_blocks (JSON):
 * [{id, type, enabled, data: {...}}]. Thứ tự trong danh sách = thứ tự trên trang.
 */
final class IntroBlocks
{
    public const SETTING_KEY = 'intro_blocks';

    public const SUBJECT = AdminActivity::SUBJECT_SETTINGS.'.intro-blocks';

    /**
     * Loại khối => [tên, mô tả].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const TYPES = [
        'steps' => ['Cách làm bài', 'Các bước làm bài, đánh số 1, 2, 3…'],
        'groups' => ['6 nhóm Holland', 'Giải thích phương pháp và 6 nhóm R, I, A, S, E, C (tên, mô tả bấm sửa trực tiếp trên trang).'],
        'text_image' => ['Chữ và ảnh', 'Một đoạn nội dung có định dạng, kèm ảnh bên trái hoặc phải và nút bấm (không bắt buộc).'],
        'faq' => ['Câu hỏi thường gặp', 'Danh sách câu hỏi, bấm vào để xem câu trả lời.'],
        'testimonials' => ['Cảm nhận phụ huynh / học sinh', 'Lời nhận xét thật kèm tên và vai trò người nói.'],
    ];

    /**
     * @return list<array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    public static function all(): array
    {
        $blocks = json_decode((string) setting(self::SETTING_KEY, '[]'), true);

        return is_array($blocks)
            ? array_values(array_filter($blocks, fn ($block) => is_array($block) && isset($block['id'], self::TYPES[$block['type'] ?? '']))
            ) : [];
    }

    /**
     * Khối đang bật (cho trang khách).
     *
     * @return list<array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    public static function enabled(): array
    {
        return array_values(array_filter(self::all(), fn (array $block) => $block['enabled'] ?? false));
    }

    /**
     * @return array{id: string, type: string, enabled: bool, data: array<string, mixed>}|null
     */
    public static function find(string $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    public static function newId(): string
    {
        return Str::lower(Str::random(8));
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type][0] ?? $type;
    }

    /**
     * Kiểm tra + chuẩn hóa nội dung một khối theo loại (bỏ dòng trống, lọc HTML, tự thêm https:// cho link nút).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function normalize(string $type, array $input): array
    {
        $input['items'] = array_values((array) ($input['items'] ?? []));
        $input['button_url'] = LinkNormalizer::normalize($input['button_url'] ?? null);
        $title = 'Khối "'.(trim((string) ($input['title'] ?? '')) ?: self::typeLabel($type)).'"';

        $rules = ['title' => ['nullable', 'string', 'max:150']] + match ($type) {
            'steps' => ['items' => ['array', 'max:8'], 'items.*.title' => ['nullable', 'string', 'max:150'], 'items.*.text' => ['nullable', 'string', 'max:500'], 'note' => ['nullable', 'string', 'max:300'], 'in_hero' => ['nullable', 'boolean']],
            'groups' => ['intro' => ['nullable', 'string', 'max:1500']],
            'text_image' => ['body' => ['nullable', 'string', 'max:20000'], 'image' => ['nullable', 'string', 'max:255', 'regex:#^((img|storage)/[A-Za-z0-9_\-./]+|https://\S+)$#'],
                'image_alt' => ['nullable', 'string', 'max:150'], 'image_side' => ['nullable', Rule::in(['left', 'right'])],
                'button_text' => ['nullable', 'string', 'max:60'], 'button_url' => ['nullable', 'required_with:button_text', 'url:http,https', 'max:255']],
            'faq' => ['items' => ['array', 'max:30'], 'items.*.question' => ['nullable', 'string', 'max:300'], 'items.*.answer' => ['nullable', 'string', 'max:5000']],
            'testimonials' => ['items' => ['array', 'max:20'], 'items.*.quote' => ['nullable', 'string', 'max:800'], 'items.*.name' => ['nullable', 'string', 'max:100'], 'items.*.role' => ['nullable', 'string', 'max:100']],
            default => [],
        };

        $data = Validator::make($input, $rules, [
            'button_url.url' => $title.': địa chỉ nút chưa đúng. Ví dụ: viger.edu.vn/du-hoc-duc',
            'button_url.required_with' => $title.': có chữ trên nút thì cần nhập địa chỉ nút.',
            'image.regex' => $title.': ảnh chưa đúng, vui lòng chọn lại.',
            'max' => $title.': nội dung quá dài.',
        ])->validate();

        $data['title'] = trim((string) ($data['title'] ?? ''));
        $rows = fn (string $first) => collect($data['items'] ?? [])->filter(fn ($row) => filled($row[$first] ?? null))->values();

        return match ($type) {
            'steps' => ['title' => $data['title'], 'in_hero' => (bool) ($data['in_hero'] ?? false), 'note' => trim((string) ($data['note'] ?? '')),
                'items' => $rows('title')->map(fn ($row) => ['title' => trim($row['title']), 'text' => trim((string) ($row['text'] ?? ''))])->all()],
            'groups' => ['title' => $data['title'], 'intro' => trim((string) ($data['intro'] ?? ''))],
            'text_image' => ['title' => $data['title'], 'body' => HtmlSanitizer::clean($data['body'] ?? ''), 'image' => $data['image'] ?? '',
                'image_alt' => trim((string) ($data['image_alt'] ?? '')), 'image_side' => $data['image_side'] ?? 'right',
                'button_text' => trim((string) ($data['button_text'] ?? '')), 'button_url' => trim((string) ($data['button_url'] ?? ''))],
            'faq' => ['title' => $data['title'],
                'items' => $rows('question')->map(fn ($row) => ['question' => trim($row['question']), 'answer' => HtmlSanitizer::clean($row['answer'] ?? '')])->all()],
            'testimonials' => ['title' => $data['title'],
                'items' => $rows('quote')->map(fn ($row) => ['quote' => trim($row['quote']), 'name' => trim((string) ($row['name'] ?? '')), 'role' => trim((string) ($row['role'] ?? ''))])->all()],
            default => ['title' => $data['title']],
        };
    }

    /**
     * Lưu danh sách khối và ghi nhật ký (có "Quay lại bản này").
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function save(array $blocks, string $description): void
    {
        $before = Setting::query()->where('key', self::SETTING_KEY)->value('value');
        $after = json_encode(array_values($blocks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($before === $after) {
            return;
        }

        Setting::put(self::SETTING_KEY, $after, 'intro');
        AdminActivity::record(AdminActivity::ACTION_UPDATE, self::SUBJECT, null, $description,
            [self::SETTING_KEY => $before], [self::SETTING_KEY => $after]);
    }

    /**
     * Nội dung mẫu khi cài mới. Mặc định tắt cả 3 khối: trang bắt đầu đã chốt (H3) chỉ có phần mở đầu; admin bật lại được.
     *
     * @return list<array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    public static function defaults(): array
    {
        return [
            ['id' => 'steps01', 'type' => 'steps', 'enabled' => false, 'data' => [
                'title' => 'Cách làm bài',
                'in_hero' => true,
                'items' => [
                    ['title' => 'Trả lời các câu hỏi', 'text' => 'Chọn mức đúng nhất với bạn. Không có câu trả lời đúng hay sai và không giới hạn thời gian.'],
                    ['title' => 'Điền thông tin nhận kết quả', 'text' => 'Một số thông tin cơ bản để gửi kết quả cho bạn và để chuyên viên tư vấn liên hệ khi cần.'],
                    ['title' => 'Xem kết quả và nhận tư vấn', 'text' => 'Xem ngay mã Holland và các ngành gợi ý. Chuyên viên tư vấn sẽ liên hệ để trao đổi thêm về ngành, trường và lộ trình du học.'],
                ],
                'note' => 'Phụ huynh có thể làm cùng con hoặc tự làm để hiểu thêm về con.',
            ]],
            ['id' => 'groups01', 'type' => 'groups', 'enabled' => false, 'data' => [
                'title' => 'Phương pháp Holland là gì?',
                'intro' => 'Phương pháp do nhà tâm lý học John Holland (Mỹ) xây dựng, được dùng phổ biến trong hướng nghiệp. Sở thích nghề nghiệp được chia thành 6 nhóm. Mỗi người thường có điểm ở nhiều nhóm; ba nhóm cao nhất tạo thành mã Holland, ví dụ AIS. Người làm công việc phù hợp với mã Holland của mình thường hứng thú và gắn bó với nghề lâu hơn.',
            ]],
            ['id' => 'faq01', 'type' => 'faq', 'enabled' => false, 'data' => [
                'title' => 'Câu hỏi thường gặp',
                'items' => [
                    ['question' => 'Làm bài trắc nghiệm có mất phí không?', 'answer' => '<p>Không. Bài trắc nghiệm và kết quả hoàn toàn miễn phí.</p>'],
                    ['question' => 'Kết quả có cho biết chắc chắn tôi nên học ngành nào không?', 'answer' => '<p>Không. Kết quả cho biết nhóm sở thích nổi bật và gợi ý những ngành nên tìm hiểu. Khi chọn ngành, bạn nên cân nhắc thêm năng lực học tập, điều kiện gia đình và ý kiến của chuyên viên tư vấn.</p>'],
                    ['question' => 'Phụ huynh có thể làm bài thay cho con không?', 'answer' => '<p>Kết quả chính xác nhất khi học sinh tự trả lời. Phụ huynh có thể ngồi cùng con, hoặc tự làm để hiểu thêm về sở thích của con.</p>'],
                    ['question' => 'Thông tin tôi điền được dùng để làm gì?', 'answer' => '<p>Thông tin chỉ được dùng để gửi kết quả và để chuyên viên tư vấn liên hệ trao đổi về du học. Chúng tôi không chia sẻ thông tin cho bên thứ ba.</p>'],
                ],
            ]],
        ];
    }
}
