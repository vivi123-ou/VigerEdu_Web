<?php

namespace App\Support;

/**
 * Banner đầu trang (hero) của các trang con trong menu "Giáo dục tại Đức":
 * các key trong bảng settings (group "education") cùng giá trị mặc định.
 * Key có dạng "education_{trang}_{trường}", vd. education_overview_heading.
 */
class EducationPages extends CategoryHeroPages
{
    public const PREFIX = 'education';

    public const PAGES = [
        'overview' => [
            'label' => 'Tổng quan',
            'url' => '/giao-duc-tai-duc/tong-quan',
            'image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1600&q=80',
        ],
        'education_system' => [
            'label' => 'Hệ thống giáo dục',
            'url' => '/giao-duc-tai-duc/he-thong-giao-duc',
            'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1600&q=80',
        ],
        'research_applied' => [
            'label' => 'Nghiên cứu và Ứng dụng',
            'url' => '/giao-duc-tai-duc/nghien-cuu-va-ung-dung',
            'image' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1600&q=80',
        ],
        'costs_opportunities' => [
            'label' => 'Chi phí và Cơ hội',
            'url' => '/giao-duc-tai-duc/chi-phi-va-co-hoi',
            'image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1600&q=80',
        ],
        'career_opportunities' => [
            'label' => 'Cơ hội nghề nghiệp',
            'url' => '/giao-duc-tai-duc/co-hoi-nghe-nghiep',
            'image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1600&q=80',
        ],
    ];

    public const DEFAULTS = [
        // Tổng quan
        'education_overview_page_title' => 'Tổng quan Giáo dục Đức',
        'education_overview_pretitle' => 'Bài Viết Chuyên Đề',
        'education_overview_heading' => 'TỔNG QUAN GIÁO DỤC ĐỨC',
        'education_overview_description' => 'Tập hợp các bài phân tích, chia sẻ kinh nghiệm và kiến thức chuyên sâu về hệ thống giáo dục, văn hóa, và cuộc sống du học sinh tại Đức.',
        'education_overview_image' => null, // null => ảnh mặc định trong PAGES

        // Hệ thống giáo dục
        'education_education_system_page_title' => 'Hệ thống giáo dục',
        'education_education_system_pretitle' => 'Mô hình học thuật thế giới',
        'education_education_system_heading' => 'HỆ THỐNG GIÁO DỤC ĐỨC',
        'education_education_system_description' => 'Khám phá cấu trúc của một trong những hệ thống giáo dục minh bạch, linh hoạt và chất lượng nhất thế giới, được phân luồng rõ ràng để phù hợp với mọi định hướng phát triển của sinh viên.',
        'education_education_system_image' => null,

        // Nghiên cứu và Ứng dụng
        'education_research_applied_page_title' => 'Nghiên cứu và Ứng dụng',
        'education_research_applied_pretitle' => 'Khoa học & Thực tiễn',
        'education_research_applied_heading' => 'NGHIÊN CỨU VÀ ỨNG DỤNG',
        'education_research_applied_description' => 'Hệ thống các trường đại học nghiên cứu (Universität) và ứng dụng (Fachhochschule) hàng đầu tại Đức, môi trường kết nối chặt chẽ giữa học thuật và các tập đoàn công nghiệp.',
        'education_research_applied_image' => null,

        // Chi phí và Cơ hội
        'education_costs_opportunities_page_title' => 'Chi phí và Cơ hội',
        'education_costs_opportunities_pretitle' => 'Đầu tư thông minh cho tương lai',
        'education_costs_opportunities_heading' => 'CHI PHÍ & CƠ HỘI',
        'education_costs_opportunities_description' => 'Du học Đức được đánh giá là một trong những lựa chọn tối ưu nhất về mặt tài chính. Bạn sẽ được tiếp cận nền giáo dục ưu tú với mức học phí bằng 0 và vô vàn cơ hội hỗ trợ chi phí sinh hoạt.',
        'education_costs_opportunities_image' => null,

        // Cơ hội nghề nghiệp
        'education_career_opportunities_page_title' => 'Cơ hội nghề nghiệp',
        'education_career_opportunities_pretitle' => 'Mở rộng tương lai tại Châu Âu',
        'education_career_opportunities_heading' => 'CƠ HỘI NGHỀ NGHIỆP TẠI ĐỨC',
        'education_career_opportunities_description' => 'Với nền kinh tế lớn nhất Châu Âu đang khát nhân lực chất lượng cao, sinh viên quốc tế tốt nghiệp tại Đức luôn được săn đón với vô vàn đặc quyền về định cư và việc làm.',
        'education_career_opportunities_image' => null,
    ];

    /** Bản tiếng Anh mặc định (admin sửa ở Giáo dục tại Đức > Sửa bản English). */
    public const TRANSLATIONS = [
        'en' => [
            'education_overview_page_title' => 'German Education Overview',
            'education_overview_pretitle' => 'In-Depth Articles',
            'education_overview_heading' => 'GERMAN EDUCATION OVERVIEW',
            'education_overview_description' => 'A collection of analyses, shared experiences and in-depth knowledge about the education system, culture and student life in Germany.',

            'education_education_system_page_title' => 'Education System',
            'education_education_system_pretitle' => 'A world-class academic model',
            'education_education_system_heading' => 'THE GERMAN EDUCATION SYSTEM',
            'education_education_system_description' => 'Discover the structure of one of the most transparent, flexible and high-quality education systems in the world, with clear pathways to suit every student\'s goals.',

            'education_research_applied_page_title' => 'Research & Applied Sciences',
            'education_research_applied_pretitle' => 'Science & Practice',
            'education_research_applied_heading' => 'RESEARCH & APPLIED SCIENCES',
            'education_research_applied_description' => 'Germany\'s leading research universities (Universität) and universities of applied sciences (Fachhochschule) — an environment that closely connects academia with industry.',

            'education_costs_opportunities_page_title' => 'Costs & Opportunities',
            'education_costs_opportunities_pretitle' => 'A smart investment in your future',
            'education_costs_opportunities_heading' => 'COSTS & OPPORTUNITIES',
            'education_costs_opportunities_description' => 'Studying in Germany is considered one of the most cost-effective options. You gain access to excellent education with zero tuition fees and plenty of support with living costs.',

            'education_career_opportunities_page_title' => 'Career Opportunities',
            'education_career_opportunities_pretitle' => 'Expand your future in Europe',
            'education_career_opportunities_heading' => 'CAREER OPPORTUNITIES IN GERMANY',
            'education_career_opportunities_description' => 'With Europe\'s largest economy in need of highly skilled talent, international graduates from German universities are in high demand, with many residence and employment benefits.',
        ],
    ];
}
