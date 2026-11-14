<?php

namespace App\Support;

/**
 * Các banner mặc định của slider trang chủ (song ngữ).
 * Dùng cho BannerSeeder, migration bổ sung bản tiếng Anh và slider khi chưa có banner trong admin.
 */
class DefaultBanners
{
    /** Các cột chữ đa ngôn ngữ của Banner. */
    public const TEXT_FIELDS = ['alt', 'eyebrow', 'title', 'desc', 'button_primary_label', 'button_secondary_label'];

    /**
     * @var list<array{image: string, button_primary_url: string|null, button_secondary_url: string|null, vi: array<string, string|null>, en: array<string, string|null>}>
     */
    public const SLIDES = [
        [
            'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1920&q=80',
            'button_primary_url' => '/giao-duc-tai-duc',
            'button_secondary_url' => '/lien-he',
            'vi' => [
                'alt' => 'Khuôn viên đại học tại Đức',
                'eyebrow' => 'Du học Đức',
                'title' => 'Miễn học phí – Chất lượng quốc tế',
                'desc' => 'Khám phá hệ thống giáo dục Đức: học tại các trường công lập uy tín với mức học phí bằng 0 dành cho sinh viên quốc tế.',
                'button_primary_label' => 'Khám phá giáo dục Đức',
                'button_secondary_label' => 'Nhận tư vấn miễn phí',
            ],
            'en' => [
                'alt' => 'University campus in Germany',
                'eyebrow' => 'Study in Germany',
                'title' => 'Tuition-free – World-class quality',
                'desc' => 'Discover the German education system: study at reputable public universities with zero tuition fees for international students.',
                'button_primary_label' => 'Explore German education',
                'button_secondary_label' => 'Get free advice',
            ],
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80',
            'button_primary_url' => '/tu-van/quy-trinh-tu-van',
            'button_secondary_url' => '/ve-chung-toi',
            'vi' => [
                'alt' => 'Sinh viên trong khuôn viên trường',
                'eyebrow' => 'Lộ trình cá nhân hóa',
                'title' => 'Từ chọn ngành đến ngày nhập học tại Đức',
                'desc' => 'Đội ngũ chuyên gia từng học tập và làm việc tại Đức đồng hành cùng bạn trong từng bước của hành trình.',
                'button_primary_label' => 'Xem quy trình tư vấn',
                'button_secondary_label' => 'Về chúng tôi',
            ],
            'en' => [
                'alt' => 'Students on campus',
                'eyebrow' => 'Personalized roadmap',
                'title' => 'From choosing a major to enrolling in Germany',
                'desc' => 'Our experts, who have studied and worked in Germany, guide you through every step of the journey.',
                'button_primary_label' => 'See our consulting process',
                'button_secondary_label' => 'About us',
            ],
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?auto=format&fit=crop&w=1920&q=80',
            'button_primary_url' => '/giao-duc-tai-duc/co-hoi-nghe-nghiep',
            'button_secondary_url' => null,
            'vi' => [
                'alt' => 'Thư viện trường đại học',
                'eyebrow' => 'Cơ hội nghề nghiệp',
                'title' => 'Học tập hôm nay, làm việc tại Châu Âu ngày mai',
                'desc' => 'Sinh viên quốc tế được ở lại đến 18 tháng sau tốt nghiệp để tìm việc tại nền kinh tế lớn nhất Châu Âu.',
                'button_primary_label' => 'Cơ hội nghề nghiệp',
                'button_secondary_label' => null,
            ],
            'en' => [
                'alt' => 'University library',
                'eyebrow' => 'Career opportunities',
                'title' => 'Study today, work in Europe tomorrow',
                'desc' => 'International students can stay up to 18 months after graduation to find a job in Europe\'s largest economy.',
                'button_primary_label' => 'Career opportunities',
                'button_secondary_label' => null,
            ],
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1920&q=80',
            'button_primary_url' => '/tu-van',
            'button_secondary_url' => '/lien-he',
            'vi' => [
                'alt' => 'Nhóm sinh viên cùng học tập',
                'eyebrow' => 'Đồng hành trọn gói',
                'title' => 'Hồ sơ, visa, tài chính, nhà ở – chúng tôi lo cùng bạn',
                'desc' => 'Hỗ trợ dịch thuật, APS, tài khoản phong tỏa, luyện phỏng vấn visa và đón tại sân bay Đức.',
                'button_primary_label' => 'Dịch vụ tư vấn',
                'button_secondary_label' => 'Đặt lịch tư vấn',
            ],
            'en' => [
                'alt' => 'Group of students studying together',
                'eyebrow' => 'All-in-one support',
                'title' => 'Applications, visa, finances, housing – we handle it with you',
                'desc' => 'Support with translations, APS, blocked accounts, visa interview practice and airport pick-up in Germany.',
                'button_primary_label' => 'Consulting services',
                'button_secondary_label' => 'Book a consultation',
            ],
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1599940824399-b87987ceb72a?auto=format&fit=crop&w=1920&q=80',
            'button_primary_url' => '/blog',
            'button_secondary_url' => null,
            'vi' => [
                'alt' => 'Sinh viên quốc tế tại Đức',
                'eyebrow' => 'Thành công đã được chứng minh',
                'title' => 'Hơn 500 học viên đã nhập học tại Đức',
                'desc' => 'Tỷ lệ xin visa thành công 95% cùng hơn 10 năm kinh nghiệm đồng hành với học sinh, sinh viên Việt Nam.',
                'button_primary_label' => 'Đọc kinh nghiệm từ blog',
                'button_secondary_label' => null,
            ],
            'en' => [
                'alt' => 'International students in Germany',
                'eyebrow' => 'Proven success',
                'title' => 'More than 500 students enrolled in Germany',
                'desc' => 'A 95% visa success rate and over 10 years of experience supporting Vietnamese students.',
                'button_primary_label' => 'Read experiences on our blog',
                'button_secondary_label' => null,
            ],
        ],
    ];

    /**
     * Dữ liệu để lưu vào model Banner: cột chữ dạng ['vi' => ..., 'en' => ...].
     *
     * @return list<array<string, mixed>>
     */
    public static function forModel(): array
    {
        return array_map(function (array $slide, int $index) {
            $data = [
                'image' => $slide['image'],
                'button_primary_url' => $slide['button_primary_url'],
                'button_secondary_url' => $slide['button_secondary_url'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ];

            foreach (self::TEXT_FIELDS as $field) {
                $data[$field] = array_filter(['vi' => $slide['vi'][$field], 'en' => $slide['en'][$field]], 'filled') ?: null;
            }

            return $data;
        }, self::SLIDES, array_keys(self::SLIDES));
    }

    /**
     * Slide cho partials.hero-slider theo ngôn ngữ (dùng khi chưa có banner trong admin).
     *
     * @return list<array<string, mixed>>
     */
    public static function forSlider(?string $locale = null): array
    {
        $locale = in_array($locale ??= app()->getLocale(), ['vi', 'en'], true) ? $locale : 'vi';

        return array_map(function (array $slide) use ($locale) {
            $text = $slide[$locale];
            $buttons = [[$text['button_primary_label'], $slide['button_primary_url']]];

            if (filled($text['button_secondary_label']) && filled($slide['button_secondary_url'])) {
                $buttons[] = [$text['button_secondary_label'], $slide['button_secondary_url'], 'ghost'];
            }

            return [
                'image' => $slide['image'],
                'alt' => $text['alt'],
                'eyebrow' => $text['eyebrow'],
                'title' => $text['title'],
                'desc' => $text['desc'],
                'buttons' => $buttons,
            ];
        }, self::SLIDES);
    }
}
