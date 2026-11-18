<?php

namespace App\Support;

/**
 * Banner đầu trang (hero) của các trang con trong menu "Tư vấn":
 * các key trong bảng settings (group "consult") cùng giá trị mặc định.
 * Key có dạng "consult_{trang}_{trường}", vd. consult_bachelor_heading.
 * (Trang Quy trình tư vấn có nội dung riêng ở ConsultProcessPage.)
 */
class ConsultPages extends CategoryHeroPages
{
    public const PREFIX = 'consult';

    public const PAGES = [
        'bachelor' => [
            'label' => 'Du học Đại học',
            'url' => '/tu-van/du-hoc-dai-hoc',
            'image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=80',
        ],
        'master' => [
            'label' => 'Du học Cao học',
            'url' => '/tu-van/du-hoc-cao-hoc',
            'image' => 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?auto=format&fit=crop&w=1600&q=80',
        ],
    ];

    public const DEFAULTS = [
        // Du học Đại học
        'consult_bachelor_page_title' => 'Tư vấn Du học Đại học',
        'consult_bachelor_pretitle' => 'Lộ Trình Cử Nhân',
        'consult_bachelor_heading' => 'DU HỌC ĐẠI HỌC TẠI ĐỨC',
        'consult_bachelor_description' => 'Tư vấn điều kiện tốt nghiệp THPT, chứng chỉ ngoại ngữ, thẩm tra APS, thi TestAS và xin giấy báo nhập học (Zulassung) vào các trường đại học hàng đầu nước Đức.',
        'consult_bachelor_image' => null, // null => ảnh mặc định trong PAGES

        // Du học Cao học
        'consult_master_page_title' => 'Tư vấn Du học Cao học',
        'consult_master_pretitle' => 'Bậc Thạc Sĩ & Tiến Sĩ',
        'consult_master_heading' => 'DU HỌC CAO HỌC TẠI ĐỨC',
        'consult_master_description' => 'Chương trình đào tạo Thạc sĩ và Tiến sĩ quốc tế bằng tiếng Đức và tiếng Anh, cơ hội học bổng và tham gia các viện nghiên cứu lớn của châu Âu.',
        'consult_master_image' => null,
    ];

    /** Bản tiếng Anh mặc định (admin sửa ở Tư vấn > Sửa bản English). */
    public const TRANSLATIONS = [
        'en' => [
            'consult_bachelor_page_title' => 'Bachelor Study Consulting',
            'consult_bachelor_pretitle' => 'Bachelor Pathway',
            'consult_bachelor_heading' => 'BACHELOR STUDIES IN GERMANY',
            'consult_bachelor_description' => 'Guidance on high school graduation requirements, language certificates, APS verification, the TestAS exam and obtaining an admission letter (Zulassung) from Germany\'s top universities.',

            'consult_master_page_title' => 'Master Study Consulting',
            'consult_master_pretitle' => 'Master\'s & Doctoral Level',
            'consult_master_heading' => 'GRADUATE STUDIES IN GERMANY',
            'consult_master_description' => 'International Master\'s and doctoral programs taught in German and English, with scholarship opportunities and access to Europe\'s major research institutes.',
        ],
    ];
}
