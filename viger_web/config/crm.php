<?php

/*
|--------------------------------------------------------------------------
| Viger CRM — menu điều hướng
|--------------------------------------------------------------------------
| Nguồn dữ liệu DUY NHẤT cho sidebar và breadcrumb.
| - route: tên route; null => chức năng chưa làm (hiện mờ).
| - active: (tuỳ chọn) pattern route để tô sáng, VD 'crm.leads.*' cho cả trang con.
| - params: (tuỳ chọn) tham số route, VD ['source' => 'website'] (xem App\Support\CrmMenu).
| - permission: quyền CRM cần có để thấy mục (App\Support\CrmPermissions). Không có quyền => ẩn hẳn.
| - icon:  nội dung <path> của SVG 24x24 (stroke).
| Khi làm xong một chức năng: tạo route rồi điền tên route vào đây.
*/

return [
    /*
    | Kênh gửi khách vào API CRM (HOP_DONG_KET_NOI mục 1). Chỉ lưu BĂM SHA-256 của token:
    |   php -r "echo hash('sha256', 'token-cua-kenh');"
    | TẠM THỜI khai báo ở .env; giai đoạn C3 chuyển sang bảng `channels` quản lý trên admin.
    */
    'api_channels' => [
        [
            'code' => 'website',
            'enabled' => (bool) env('CRM_CHANNEL_WEBSITE_ENABLED', true),
            'token_hash' => env('CRM_CHANNEL_WEBSITE_TOKEN_HASH'),
            'scopes' => ['leads:write', 'events:write'],
        ],
        [
            // viger_chatbot gửi khách khi khách để lại SĐT / email trong khung chat
            'code' => 'chatbot',
            'enabled' => (bool) env('CRM_CHANNEL_CHATBOT_ENABLED', true),
            'token_hash' => env('CRM_CHANNEL_CHATBOT_TOKEN_HASH'),
            'scopes' => ['leads:write', 'events:write'],
        ],
        [
            // viger_holland gửi khách kèm kết quả trắc nghiệm (holland-result/3)
            // Thêm kênh mới ở CUỐI danh sách: test của các kênh khác tham chiếu theo vị trí.
            'code' => 'holland',
            'enabled' => (bool) env('CRM_CHANNEL_HOLLAND_ENABLED', true),
            'token_hash' => env('CRM_CHANNEL_HOLLAND_TOKEN_HASH'),
            'scopes' => ['leads:write', 'events:write'],
        ],
    ],

    /*
    | Địa chỉ Holland: nút "Gửi link trắc nghiệm" trên hồ sơ khách tạo link
    |   <holland_url>/?utm_source=crm&utm_medium=tu_van&utm_content=VIGER-LEAD-00001
    | Holland gửi lại utm trong gói lead/2 => CRM nối kết quả vào đúng hồ sơ.
    */
    'holland_url' => env('CRM_HOLLAND_URL', 'https://holland.viger.edu.vn'),

    'menu' => [
        [
            'label' => 'Tổng quan',
            'items' => [
                ['name' => 'Dashboard', 'permission' => 'dashboard.view', 'route' => 'crm.dashboard', 'icon' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>'],
            ],
        ],
        [
            'label' => 'Quản lý khách hàng',
            'items' => [
                ['name' => 'Khách hàng tiềm năng', 'permission' => 'leads.view', 'route' => 'crm.leads.index', 'active' => 'crm.leads.*', 'icon' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>'],
                ['name' => 'Danh sách khách hàng', 'permission' => 'leads.view', 'route' => 'crm.customers.index', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
                ['name' => 'Hồ sơ học viên', 'permission' => 'leads.view', 'route' => 'crm.students.index', 'icon' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>'],
                ['name' => 'Khách hàng đã chuyển đổi', 'permission' => 'leads.view', 'route' => 'crm.converted.index', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>'],
            ],
        ],
        [
            'label' => 'Quản lý tư vấn',
            'items' => [
                ['name' => 'Quy trình tư vấn', 'permission' => 'leads.view', 'route' => 'crm.pipeline.index', 'icon' => '<rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="12" rx="1"/><rect x="17" y="3" width="4" height="7" rx="1"/>'],
                ['name' => 'Lịch hẹn', 'permission' => 'leads.view', 'route' => 'crm.appointments.index', 'active' => 'crm.appointments.*', 'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
                ['name' => 'Lịch sử hoạt động', 'permission' => 'activities.view', 'route' => 'crm.activities.index', 'icon' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>'],
                ['name' => 'Ghi chú tư vấn', 'permission' => 'activities.view', 'route' => 'crm.notes.index', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>'],
            ],
        ],
        [
            'label' => 'Nguồn khách hàng',
            'items' => [
                ['name' => 'Website VIGER', 'permission' => 'sources.view', 'route' => 'crm.sources.show', 'params' => ['source' => 'website'], 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
                ['name' => 'Holland', 'permission' => 'sources.view', 'route' => 'crm.sources.show', 'params' => ['source' => 'holland'], 'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
                ['name' => 'AI Chatbot', 'permission' => 'sources.view', 'route' => 'crm.sources.show', 'params' => ['source' => 'chatbot'], 'icon' => '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M12 8V4M8 4h8M9 14h.01M15 14h.01"/>'],
                ['name' => 'Nguồn khác', 'permission' => 'sources.view', 'route' => 'crm.sources.show', 'params' => ['source' => 'other'], 'icon' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>'],
            ],
        ],
        [
            'label' => 'Báo cáo & thống kê',
            'items' => [
                ['name' => 'Tổng quan khách hàng', 'permission' => 'reports.view', 'route' => 'crm.reports.customers', 'icon' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>'],
                ['name' => 'Hiệu suất tư vấn viên', 'permission' => 'reports.view', 'route' => 'crm.reports.consultants', 'icon' => '<path d="M3 3v18h18"/><path d="M7 16V11M12 16V7M17 16v-3"/>'],
                ['name' => 'Báo cáo chuyển đổi', 'permission' => 'reports.view', 'route' => 'crm.reports.conversion', 'icon' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>'],
            ],
        ],
        [
            'label' => 'Hệ thống',
            'items' => [
                ['name' => 'Quản lý nhân viên', 'permission' => 'staff.manage', 'route' => 'crm.staff.index', 'active' => 'crm.staff.*', 'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>'],
                ['name' => 'Vai trò & phân quyền', 'permission' => 'roles.manage', 'route' => 'crm.roles.index', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>'],
                ['name' => 'Cấu hình CRM', 'permission' => 'settings.manage', 'route' => 'crm.settings.index', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>'],
                ['name' => 'Nhật ký hệ thống', 'permission' => 'settings.manage', 'route' => 'crm.audit.index', 'icon' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M8 7h8M8 11h6"/>'],
            ],
        ],
    ],
];
