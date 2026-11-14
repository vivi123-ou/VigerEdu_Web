<?php

namespace App\Support;

/**
 * Các phần dùng chung của trang Chi tiết bài viết (/bai-viet/{slug}):
 * các key trong bảng settings (group "post_detail") cùng giá trị mặc định.
 * Nội dung từng bài viết vẫn quản trị ở mục Bài viết.
 */
class PostDetailPage extends SiteContent
{
    public const DEFAULTS = [
        // Thanh thông tin dưới tiêu đề
        'post_detail_author_name' => 'VIGER EDU',

        // Hộp tác giả cuối bài
        'post_detail_show_author_box' => '1',
        'post_detail_author_box_name' => 'VIGER.EDU.VN',
        'post_detail_author_box_bio' => 'Đồng hành cùng học sinh, sinh viên Việt Nam trên con đường chinh phục giáo dục Đức chất lượng cao, miễn học phí và mở rộng cơ hội phát triển sự nghiệp toàn cầu.',
        'post_detail_author_box_avatar' => null, // null => ảnh mặc định bên dưới

        // Bài viết liên quan
        'post_detail_show_related' => '1',
        'post_detail_related_title' => 'Bài Viết Liên Quan',
        'post_detail_related_count' => '5',
        'post_detail_related_placeholder' => null, // ảnh khi bài viết không có ảnh đại diện

        // Hộp kêu gọi tư vấn ở sidebar
        'post_detail_show_cta' => '1',
        'post_detail_cta_title' => 'Cần Tư Vấn Du Học Đức?',
        'post_detail_cta_description' => 'Nhận lộ trình du học cá nhân hóa hoàn toàn miễn phí từ các chuyên gia giáo dục Đức tại VIGER EDU.',
        'post_detail_cta_button_text' => 'Đăng ký tư vấn ngay',
        'post_detail_cta_button_url' => '/lien-he',
    ];

    public const TOGGLE_KEYS = ['post_detail_show_author_box', 'post_detail_show_related', 'post_detail_show_cta'];

    public const DEFAULT_AUTHOR_AVATAR = 'img/favicon.png';

    public const DEFAULT_RELATED_PLACEHOLDER = 'https://images.unsplash.com/photo-1599940824399-b87987ceb72a?auto=format&fit=crop&w=200&q=80';

    public static function enabled(string $key): bool
    {
        return (bool) (int) static::get($key);
    }

    /** Số bài liên quan hiển thị (1–10). */
    public static function relatedCount(): int
    {
        return max(1, min(10, (int) static::get('post_detail_related_count')));
    }
}
