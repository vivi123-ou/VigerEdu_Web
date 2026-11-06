<?php

namespace App\Support;

use App\Models\AdminActivity;
use App\Models\Setting;
use Illuminate\Support\Str;

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
        'groups' => ['6 nhóm Holland', 'Giải thích phương pháp và 6 nhóm R, I, A, S, E, C (tên, mô tả sửa ở mục "Mô tả 6 nhóm Holland").'],
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
     * Nội dung mẫu khi cài mới.
     *
     * @return list<array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    public static function defaults(): array
    {
        return [
            ['id' => 'steps01', 'type' => 'steps', 'enabled' => true, 'data' => [
                'title' => 'Cách làm bài',
                'in_hero' => true,
                'items' => [
                    ['title' => 'Trả lời các câu hỏi', 'text' => 'Chọn mức đúng nhất với bạn. Không có câu trả lời đúng hay sai và không giới hạn thời gian.'],
                    ['title' => 'Điền thông tin nhận kết quả', 'text' => 'Một số thông tin cơ bản để gửi kết quả cho bạn và để chuyên viên tư vấn liên hệ khi cần.'],
                    ['title' => 'Xem kết quả và nhận tư vấn', 'text' => 'Xem ngay mã Holland và các ngành gợi ý. Chuyên viên tư vấn sẽ liên hệ để trao đổi thêm về ngành, trường và lộ trình du học.'],
                ],
                'note' => 'Phụ huynh có thể làm cùng con hoặc tự làm để hiểu thêm về con.',
            ]],
            ['id' => 'groups01', 'type' => 'groups', 'enabled' => true, 'data' => [
                'title' => 'Phương pháp Holland là gì?',
                'intro' => 'Phương pháp do nhà tâm lý học John Holland (Mỹ) xây dựng, được dùng phổ biến trong hướng nghiệp. Sở thích nghề nghiệp được chia thành 6 nhóm. Mỗi người thường có điểm ở nhiều nhóm; ba nhóm cao nhất tạo thành mã Holland, ví dụ AIS. Người làm công việc phù hợp với mã Holland của mình thường hứng thú và gắn bó với nghề lâu hơn.',
            ]],
            ['id' => 'faq01', 'type' => 'faq', 'enabled' => true, 'data' => [
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
