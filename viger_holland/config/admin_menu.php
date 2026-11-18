<?php

use App\Filament\Pages\ActivityLog;
use App\Filament\Pages\Dashboard;
use App\Modules\Access\Filament\Resources\Roles\RoleResource;
use App\Modules\Access\Filament\Resources\Users\UserResource;
use App\Modules\Api\Filament\Pages\ApiClients;
use App\Modules\Assessment\Filament\Pages\HollandCodes;
use App\Modules\Assessment\Filament\Pages\MajorsCatalog;
use App\Modules\Assessment\Filament\Pages\TestSettings;
use App\Modules\Assessment\Filament\Pages\TestStats;
use App\Modules\Assessment\Filament\Resources\Questions\QuestionResource;
use App\Modules\Content\Filament\Pages\Appearance;
use App\Modules\Content\Filament\Pages\EmbedBox;
use App\Modules\Content\Filament\Pages\IntroPage;
use App\Modules\Content\Filament\Pages\MediaLibrary;
use App\Modules\Content\Filament\Pages\ReportTexts;
use App\Modules\Content\Filament\Pages\SeoSettings;
use App\Modules\CrmSync\Filament\Pages\CrmConnection;
use App\Modules\Respondent\Filament\Pages\ContactsToCall;
use App\Modules\Respondent\Filament\Pages\CustomersList;
use App\Modules\Respondent\Filament\Pages\FormFields;
use App\Modules\Respondent\Filament\Pages\LookupReports;
use App\Modules\Schools\Filament\Pages\SchoolsCatalog;

/*
|--------------------------------------------------------------------------
| Menu trang quản trị – một chỗ duy nhất để đặt tên, nhóm, quyền, hướng dẫn
|--------------------------------------------------------------------------
|
| Đây là chữ của giao diện quản trị (không phải nội dung khách thấy). Tên mục nói thẳng việc người dùng làm được.
| Mỗi mục:
|   'group'      nhóm trên menu (thứ tự nhóm ở 'groups');
|   'label'      tên trên menu và tiêu đề trang;
|   'icon'       biểu tượng Heroicons;
|   'permission' mã quyền cần có (app/Modules/Access/Permissions.php); null = ai đăng nhập được cũng thấy;
|   'summary'    một câu: trang này để làm gì (hiện dưới tiêu đề, trên ô lối tắt ở trang chủ);
|   'help'       nội dung nút "?": 'intro' + các bước [việc muốn làm, cách làm] + 'note';
|   'stage'      giai đoạn sẽ làm → hiện nhãn "Sắp có", bấm vào mở trang "Đang làm";
|   'page'       trang Filament của mục (resource / page);
|   'route' / 'params'  (dự phòng) trang không phải Filament;
|   'shortcut'   true = hiện thành ô lối tắt ở Trang chủ quản trị (nếu người đó có quyền).
| Trang của tư vấn viên, nhân viên không ghi ra họ bị giới hạn gì (Vân chốt 17/11).
|
*/

return [
    'groups' => ['Khách hàng', 'Bài trắc nghiệm', 'Trang khách xem', 'Quản lý hệ thống'],

    'items' => [
        'home' => [
            'page' => Dashboard::class,
            'label' => 'Trang chủ quản trị', 'icon' => 'heroicon-o-home', 'permission' => null,
            'summary' => 'Bấm một ô bên dưới để bắt đầu việc cần làm.',
            'help' => [
                'intro' => 'Đây là trang đầu tiên sau khi đăng nhập. Menu bên trái và các ô bên dưới chỉ hiện những việc bạn được làm.',
                'steps' => [
                    ['Muốn làm một việc', 'bấm ô tương ứng ở giữa trang, hoặc bấm tên mục ở menu bên trái.'],
                    ['Không biết một trang dùng để làm gì', 'bấm nút "?" màu vàng ở góc trên trang đó.'],
                    ['Mục có nhãn "Sắp có"', 'là phần đang được làm, bấm vào sẽ thấy giải thích.'],
                ],
            ],
        ],

        // ---- Khách hàng ----
        'lookup' => [
            'group' => 'Khách hàng', 'label' => 'Tra cứu báo cáo khách', 'icon' => 'heroicon-o-magnifying-glass',
            'permission' => 'reports.lookup', 'shortcut' => true,
            'page' => LookupReports::class,
            'summary' => 'Gõ số điện thoại, email hoặc mã báo cáo để đọc kết quả của một khách.',
            'help' => [
                'intro' => 'Dùng trước khi tư vấn: tìm đúng khách rồi đọc báo cáo đầy đủ (6 điểm, mã 3 chữ, ngành gợi ý, các câu khách đã trả lời).',
                'steps' => [
                    ['Tìm khách', 'gõ đúng số điện thoại (của học sinh hoặc phụ huynh), email hoặc mã báo cáo 8 ký tự (nhân viên thấy mã ở trang Khách cần liên lạc), rồi bấm "Tìm".'],
                    ['Đọc báo cáo', 'bấm "Đọc báo cáo đầy đủ" ở dòng của khách.'],
                    ['Khách làm bài nhiều lần', 'mỗi lần là một dòng, lần mới nhất ở trên. Báo cáo có phần so sánh với lần trước.'],
                    ['Đọc nhanh', 'phần "Đọc trước khi tư vấn" tóm tắt điều cần biết: mã khách đang thấy, mã đầy đủ, điểm cần hỏi thêm.'],
                ],
            ],
        ],
        'contacts' => [
            'group' => 'Khách hàng', 'label' => 'Khách cần liên lạc', 'icon' => 'heroicon-o-identification',
            'permission' => 'contacts.view', 'shortcut' => true,
            'page' => ContactsToCall::class,
            'summary' => 'Khách mới để lại thông tin, mới nhất ở trên.',
            'help' => [
                'intro' => 'Danh sách khách mới để lại thông tin để bạn liên lạc mời tư vấn.',
                'steps' => [
                    ['Gọi điện', 'bấm vào số điện thoại (trên điện thoại sẽ gọi luôn).'],
                    ['Nhắn Zalo', 'bấm nút "Zalo" ở cuối dòng.'],
                    ['Tìm một khách', 'gõ tên hoặc số điện thoại vào ô tìm kiếm.'],
                ],
            ],
        ],
        'customers' => [
            'group' => 'Khách hàng', 'label' => 'Danh sách khách đã làm bài', 'icon' => 'heroicon-o-document-text',
            'permission' => 'customers.list',
            'page' => CustomersList::class,
            'summary' => 'Mọi khách đã làm bài, lọc theo ngày, mã Holland, nguồn quảng cáo.',
            'help' => [
                'intro' => 'Toàn bộ khách đã làm bài, kèm kết quả. Bấm vào một khách để đọc báo cáo đầy đủ.',
                'steps' => [
                    ['Lọc khách', 'chọn khoảng ngày, mã Holland hoặc nguồn ở thanh lọc.'],
                    ['Tìm một khách', 'gõ đúng số điện thoại, email hoặc mã báo cáo vào ô tìm kiếm.'],
                    ['Đọc báo cáo của khách', 'bấm "Đọc báo cáo" ở cuối dòng.'],
                    ['Tải ra Excel', 'chọn bộ lọc rồi bấm "Tải ra Excel". Việc tải được ghi vào lịch sử thao tác.'],
                ],
            ],
        ],
        'stats' => [
            'group' => 'Khách hàng', 'label' => 'Số liệu làm bài', 'icon' => 'heroicon-o-chart-bar',
            'permission' => 'stats.view', 'shortcut' => true,
            'page' => TestStats::class,
            'summary' => 'Bao nhiêu người làm bài, đến từ đâu, bỏ ngang ở bước nào.',
            'help' => [
                'intro' => 'Con số tổng của tất cả khách, không có tên khách. Dùng để biết quảng cáo nào hiệu quả, trang nào làm khách bỏ ngang.',
                'steps' => [
                    ['Đổi khoảng thời gian', 'bấm 7 ngày, 30 ngày, Tháng này hoặc Tất cả.'],
                    ['Xem số của một ngày', 'rê chuột lên cột ở biểu đồ "Lượt làm bài theo ngày".'],
                    ['Biết quảng cáo nào hiệu quả', 'xem "Khách đến từ đâu": số theo nguồn gắn trong link quảng cáo (utm_source).'],
                ],
            ],
        ],

        // ---- Bài trắc nghiệm ----
        'questions' => [
            'group' => 'Bài trắc nghiệm', 'label' => 'Câu hỏi trắc nghiệm', 'icon' => 'heroicon-o-queue-list',
            'permission' => 'questions.manage', 'shortcut' => true,
            'page' => QuestionResource::class,
            'summary' => 'Kho 300 câu. Mỗi người làm bài được bốc ngẫu nhiên 36 câu đang dùng.',
            'help' => [
                'intro' => 'Đây là kho câu hỏi. Mỗi lần có người làm bài, phần mềm tự bốc 36 câu từ kho (mỗi nhóm 6 câu) nên mỗi người gặp một đề khác nhau.',
                'steps' => [
                    ['Muốn sửa một câu', 'bấm "Sửa" ở cuối dòng, sửa chữ, bấm "Lưu thay đổi". Người đã làm bài trước đó vẫn giữ câu cũ.'],
                    ['Thấy câu chưa hay', 'tick ô đầu dòng rồi bấm "Tạm ngưng". Câu đó không còn được bốc vào đề nhưng không mất.'],
                    ['Thêm câu mới', 'bấm "Thêm câu hỏi". Câu mới ở tình trạng "Nháp", bấm "Dùng" thì mới được bốc vào đề.'],
                ],
                'note' => 'Mức ưu tiên (Cao, Vừa, Thấp) chỉ quyết định câu hay được bốc hay không, không ảnh hưởng điểm của học sinh.',
            ],
        ],
        'settings' => [
            'group' => 'Bài trắc nghiệm', 'label' => 'Cách bốc đề & chấm điểm', 'icon' => 'heroicon-o-adjustments-horizontal',
            'permission' => 'test_settings.manage', 'shortcut' => true,
            'page' => TestSettings::class,
            'summary' => 'Đề có bao nhiêu câu, khi nào khách thấy mã 3 chữ, gợi ý bao nhiêu ngành.',
            'help' => [
                'intro' => 'Các con số quyết định đề thi và cách đọc kết quả. Người làm bài sau khi lưu dùng cách mới; kết quả cũ giữ nguyên.',
                'steps' => [
                    ['Đổi một con số', 'sửa ô cần đổi rồi bấm "Lưu" ở cuối trang.'],
                    ['Lưu nhầm', 'kéo xuống "Lịch sử thay đổi", bấm "Quay lại bản này" ở bản cũ.'],
                    ['Các ô ít dùng', 'nằm trong phần "Nâng cao", bấm để mở.'],
                ],
                'note' => 'Các con số hiện là mức thử nghiệm theo tài liệu của Thầy, sẽ chỉnh sau khi chạy thử.',
            ],
        ],
        'codes' => [
            'group' => 'Bài trắc nghiệm', 'label' => 'Giải thích các mã Holland', 'icon' => 'heroicon-o-cube-transparent',
            'permission' => 'catalog.manage',
            'page' => HollandCodes::class,
            'summary' => 'Tên và lời giải thích của 6 nhóm, 15 cặp, chữ thứ ba mà khách đọc trong báo cáo.',
            'help' => [
                'intro' => 'Các đoạn khách đọc ở phần "Nhóm sở thích của bạn" trong báo cáo đầy đủ.',
                'steps' => [
                    ['Sửa đoạn của một nhóm / một cặp', 'chọn thẻ "6 nhóm" hoặc "15 cặp", bấm "Sửa" ở dòng cần sửa, sửa rồi bấm "Lưu".'],
                    ['Đổi ngành nền của một cặp', 'thẻ "15 cặp", bấm "Sửa", chọn ngành ở ô "Ngành nền của cặp".'],
                    ['Viết đoạn riêng cho một mã 3 chữ', 'thẻ "Mã 3 chữ riêng", bấm "Thêm mã".'],
                ],
                'note' => 'Đoạn nhóm không nhắc "nhóm nổi bật nhất" hay "nhóm thứ hai"; đoạn cặp không ngụ ý nhóm nào mạnh hơn.',
            ],
        ],
        'majors' => [
            'group' => 'Bài trắc nghiệm', 'label' => 'Ngành học & nghề gợi ý', 'icon' => 'heroicon-o-academic-cap',
            'permission' => 'catalog.manage',
            'page' => MajorsCatalog::class,
            'summary' => 'Danh mục ngành học tại Đức và nghề tham khảo dùng để gợi ý trong báo cáo.',
            'help' => [
                'intro' => 'Ngành gợi ý cho khách phải là ngành có thật ở Đức, kiểm tra theo nguồn chính thức.',
                'steps' => [
                    ['Kiểm tra một ngành', 'bấm "Sửa", dán link Hochschulkompass hoặc studienwahl.de vào "Link nguồn chính thức", tick "Đã kiểm tra", bấm "Lưu".'],
                    ['Thêm ngành', 'bấm "Thêm ngành", nhập tên và mã Holland của ngành (ví dụ ISC).'],
                    ['Không muốn gợi ý một ngành nữa', 'bấm "Sửa", tắt "Đang dùng". Ngành không bị xóa, lượt làm bài cũ vẫn đọc được.'],
                    ['Sửa tên nghề', 'chọn thẻ "Nghề", bấm "Sửa".'],
                ],
            ],
        ],
        'schools' => [
            'group' => 'Bài trắc nghiệm', 'label' => 'Trường & vùng', 'icon' => 'heroicon-o-building-library',
            'permission' => 'schools.view',
            'page' => SchoolsCatalog::class,
            'summary' => 'Trường đại học và bang ở Đức (thêm được nước khác) để tư vấn chọn trường.',
            'help' => [
                'intro' => 'Danh mục trường để tư vấn viên tra nhanh: loại hình, công lập / tư thục, bang, thành phố, trang web.',
                'steps' => [
                    ['Tìm trường', 'gõ tên, tên viết tắt hoặc thành phố vào ô tìm kiếm; lọc theo bang, loại hình.'],
                    ['Xem trường đang ẩn', 'chọn bộ lọc "Trường đang ẩn" → "Chỉ trường đang ẩn" (ĐH Hành chính, ĐH Đặc thù ẩn sẵn).'],
                    ['Nhập danh sách trường (quản trị viên)', 'bấm "Nhập từ Excel", chọn quốc gia và file, kiểm tra bước "Ghép cột" rồi bấm "Gửi". Trường đã có được cập nhật, không tạo trùng.'],
                    ['Thêm nước khác', 'thẻ "Quốc gia", bấm "Thêm quốc gia", rồi nhập file trường của nước đó.'],
                ],
                'note' => 'Gợi ý "trường có ngành này" sẽ có khi team điền file trường ↔ ngành.',
            ],
        ],
        'form-fields' => [
            'group' => 'Bài trắc nghiệm', 'label' => 'Form khách điền thông tin', 'icon' => 'heroicon-o-clipboard-document-list',
            'permission' => 'form_fields.manage',
            'page' => FormFields::class,
            'summary' => 'Các ô khách điền để xem báo cáo: ô nào bắt buộc, ô nào gửi sang CRM.',
            'help' => [
                'intro' => 'Form khách điền sau khi làm bài, trước khi nhận mã xác nhận qua email.',
                'steps' => [
                    ['Đổi chữ, bắt buộc, ẩn một ô', 'bấm "Sửa" ở dòng của ô, đổi rồi bấm "Lưu".'],
                    ['Đổi thứ tự', 'kéo biểu tượng ở đầu dòng lên / xuống.'],
                    ['Thêm câu hỏi mới (ví dụ "Học trường chuyên?")', 'bấm "Thêm ô", chọn kiểu ô, thêm lựa chọn nếu là ô chọn.'],
                    ['Ô mới gửi sang CRM thế nào', 'để trống "Tên trường bên CRM" thì CRM nhận ở phần mở rộng; CRM tự tạo trường chờ duyệt.'],
                ],
                'note' => 'Ô lõi (họ tên, ngày sinh, email, điện thoại, đối tượng) không xóa được. Ô Email luôn bắt buộc vì mã xác nhận gửi qua email.',
            ],
        ],

        // ---- Trang khách xem ----
        'intro' => [
            'group' => 'Trang khách xem', 'label' => 'Trang mở đầu', 'icon' => 'heroicon-o-pencil-square',
            'permission' => 'content.manage', 'shortcut' => true,
            'page' => IntroPage::class,
            'summary' => 'Sửa chữ, ảnh trang khách thấy đầu tiên: dạng biểu mẫu hoặc sửa thẳng trên trang.',
            'help' => [
                'intro' => 'Trang khách thấy đầu tiên gồm phần banner và các khối nội dung bên dưới. Có hai cách sửa: điền biểu mẫu ở đây, hoặc bấm "Sửa trực tiếp trên trang" để sửa ngay trên trang như khách thấy.',
                'steps' => [
                    ['Sửa tiêu đề, giới thiệu, nút, ảnh banner', 'thẻ "Banner": sửa ô cần sửa, bấm "Xem trước" để xem ở thẻ mới, vừa ý thì bấm "Lưu".'],
                    ['Thêm, xóa, đổi thứ tự khối', 'thẻ "Các khối nội dung": bấm "Thêm khối", kéo biểu tượng ⋮⋮ để đổi thứ tự, bấm thùng rác để xóa, rồi bấm "Lưu các khối".'],
                    ['Ẩn / hiện một khối', 'mở khối, bật hoặc tắt "Hiện khối này trên trang mở đầu", bấm "Lưu các khối".'],
                    ['Sửa ngay trên trang', 'bấm "Sửa trực tiếp trên trang", bấm vào chữ có viền chấm rồi gõ, xong bấm "Lưu thay đổi" trên thanh đen.'],
                    ['Lưu nhầm', 'kéo xuống "Lịch sử thay đổi", bấm "Quay lại bản này".'],
                ],
            ],
        ],
        'report-pages' => [
            'group' => 'Trang khách xem', 'label' => 'Chữ trên báo cáo & email', 'icon' => 'heroicon-o-envelope',
            'permission' => 'content.manage',
            'page' => ReportTexts::class,
            'summary' => 'Chữ trên trang làm bài, kết quả, form thông tin, mã xác nhận, báo cáo và email.',
            'help' => [
                'intro' => 'Mọi câu chữ khách đọc từ lúc làm bài đến lúc xem báo cáo đầy đủ, và email gửi mã xác nhận.',
                'steps' => [
                    ['Sửa một câu', 'tìm câu ở đúng phần (Trang làm bài, Kết quả sơ lược, Báo cáo đầy đủ…), sửa rồi bấm "Lưu".'],
                    ['Lưu nhầm', 'kéo xuống "Lịch sử thay đổi", bấm "Quay lại bản này".'],
                    ['Chữ trong ngoặc nhọn như {email}, {phut}', 'giữ nguyên, phần mềm tự thay bằng email, số phút thật.'],
                ],
                'note' => 'Lời giải thích 6 nhóm, 15 cặp mã sửa ở "Giải thích các mã Holland".',
            ],
        ],
        'embed' => [
            'group' => 'Trang khách xem', 'label' => 'Ô giới thiệu trên viger.edu.vn', 'icon' => 'heroicon-o-globe-alt',
            'permission' => 'content.manage',
            'page' => EmbedBox::class,
            'summary' => 'Ô mời khách làm bài trắc nghiệm hiện trên web chính viger.edu.vn.',
            'help' => [
                'intro' => 'Trên web chính viger.edu.vn có một ô mời khách làm bài trắc nghiệm. Sửa chữ, ảnh ở đây rồi bấm "Lưu" là ô trên web chính đổi theo, không cần nhờ người làm web chính.',
                'steps' => [
                    ['Đổi tiêu đề, mô tả, chữ trên nút', 'sửa các ô ở phần "Nội dung khối".'],
                    ['Đổi ảnh', 'bấm vào ô "Ảnh" để chọn ảnh trong kho, hoặc bấm nút tải lên bên cạnh để tải ảnh mới.'],
                    ['Xem trước', 'bấm "Xem trước" để mở ô ở thẻ mới (chưa lưu).'],
                ],
            ],
        ],
        'media' => [
            'group' => 'Trang khách xem', 'label' => 'Kho ảnh', 'icon' => 'heroicon-o-photo',
            'permission' => 'content.manage', 'shortcut' => true,
            'page' => MediaLibrary::class,
            'summary' => 'Tải ảnh banner, ảnh minh họa lên để dùng ở các trang.',
            'help' => [
                'intro' => 'Nơi chứa ảnh dùng trên các trang khách xem.',
                'steps' => [
                    ['Tải ảnh lên', 'bấm "Chọn ảnh" ở góc trên, chọn một hoặc nhiều ảnh trong máy, bấm "Tải lên". Ảnh lớn sẽ tự được thu nhỏ.'],
                    ['Dùng ảnh ở một trang', 'ở trang cần sửa, bấm vào ô ảnh rồi chọn ảnh trong danh sách (gõ tên ảnh để tìm).'],
                    ['Xóa ảnh', 'bấm "Xóa" dưới ảnh. Ảnh vào thùng rác; trang nào đang dùng ảnh đó vẫn hiện bình thường.'],
                    ['Lấy lại ảnh đã xóa', 'chọn bộ lọc "Thùng rác" → "Chỉ ảnh trong thùng rác", bấm "Khôi phục".'],
                ],
            ],
        ],
        'appearance' => [
            'group' => 'Trang khách xem', 'label' => 'Logo, màu, hotline, chân trang', 'icon' => 'heroicon-o-swatch',
            'permission' => 'appearance.manage', 'shortcut' => true,
            'page' => Appearance::class,
            'summary' => 'Số hotline, địa chỉ, logo, màu hiện trên mọi trang khách xem.',
            'help' => [
                'intro' => 'Thông tin hiện ở đầu và chân mọi trang khách xem.',
                'steps' => [
                    ['Đổi hotline, địa chỉ, email', 'sửa ở phần "Chân trang", bấm "Xem trước" để xem, rồi bấm "Lưu".'],
                    ['Đổi logo', 'bấm vào ô "Logo" để chọn ảnh trong kho, hoặc bấm nút tải lên bên cạnh để tải logo mới.'],
                    ['Đổi màu', 'bấm vào ô màu để chọn trên bảng màu, hoặc gõ mã màu như #c1121f.'],
                    ['Lưu nhầm', 'kéo xuống "Lịch sử thay đổi", bấm "Quay lại bản này".'],
                ],
            ],
        ],

        // ---- Quản lý hệ thống ----
        'crm' => [
            'group' => 'Quản lý hệ thống', 'label' => 'Gửi khách sang CRM', 'icon' => 'heroicon-o-link',
            'permission' => 'crm.manage',
            'page' => CrmConnection::class,
            'summary' => 'Bật / tắt gửi khách sang CRM, kiểm tra kết nối, xem các lần gửi lỗi.',
            'help' => [
                'intro' => 'Khách nhập đúng mã email là được gửi sang CRM để tư vấn viên chăm sóc. CRM lỗi hay đang tắt thì khách vẫn xem báo cáo bình thường, phần gửi nằm chờ ở hàng đợi.',
                'steps' => [
                    ['Kết nối lần đầu', 'dán địa chỉ CRM và token kênh holland (CRM cấp), bấm "Lưu", rồi bấm "Kiểm tra kết nối".'],
                    ['Bật gửi', 'bật "Gửi khách sang CRM" và bấm "Lưu". Khách đang chờ sẽ tự được gửi.'],
                    ['Thấy dòng "Lỗi"', 'đọc cột "Lỗi gần nhất", sửa nguyên nhân (thường là sai token) rồi bấm "Gửi lại" hoặc "Gửi lại tất cả bản lỗi".'],
                ],
                'note' => 'Bảng chỉ hiện số điện thoại đã che. Gửi lại vẫn giữ nguyên mã gói nên CRM không tạo khách trùng.',
            ],
        ],
        'api' => [
            'group' => 'Quản lý hệ thống', 'label' => 'Kết nối API (chatbot)', 'icon' => 'heroicon-o-key',
            'permission' => 'api.manage',
            'page' => ApiClients::class,
            'summary' => 'Cấp mã cho chatbot, CRM đọc kết quả Holland (không có họ tên, liên lạc).',
            'help' => [
                'intro' => 'Chatbot và CRM đọc kết quả Holland qua API. Mỗi ứng dụng cần một mã kết nối riêng.',
                'steps' => [
                    ['Cấp mã cho chatbot', 'bấm "Cấp mã kết nối", đặt tên "Chatbot", bấm "Gửi", sao chép mã và gửi riêng cho người làm chatbot.'],
                    ['Lỡ làm lộ mã', 'bấm "Thu hồi" ở dòng đó rồi cấp mã mới.'],
                ],
                'note' => 'Mã chỉ hiện một lần lúc cấp. Hướng dẫn cho người lập trình: docs/API_HOLLAND.md.',
            ],
        ],
        'seo' => [
            'group' => 'Quản lý hệ thống', 'label' => 'Hiển thị trên Google', 'icon' => 'heroicon-o-magnifying-glass-circle',
            'permission' => 'appearance.manage',
            'page' => SeoSettings::class,
            'summary' => 'Tiêu đề, mô tả trang khi hiện trên Google và khi chia sẻ link.',
            'help' => [
                'intro' => 'Chữ hiện trên Google và khi dán link trang làm bài lên Facebook, Zalo. Chỉ trang bắt đầu hiện trên Google; trang làm bài, báo cáo, quản trị luôn bị ẩn.',
                'steps' => [
                    ['Đổi tiêu đề, mô tả', 'sửa 2 ô rồi bấm "Lưu". Google cập nhật sau vài ngày.'],
                    ['Đổi ảnh khi chia sẻ link', 'bấm vào ô "Ảnh khi chia sẻ link" để chọn ảnh trong kho, hoặc bấm nút tải lên bên cạnh.'],
                ],
            ],
        ],
        'users' => [
            'group' => 'Quản lý hệ thống', 'label' => 'Tài khoản nhân sự', 'icon' => 'heroicon-o-users',
            'permission' => 'accounts.manage', 'shortcut' => true,
            'page' => UserResource::class,
            'summary' => 'Mỗi người một tài khoản. Người nghỉ việc: bấm "Khóa", đừng xóa.',
            'help' => [
                'intro' => 'Tạo tài khoản cho tư vấn viên, nhân viên; đặt lại mật khẩu; khóa tài khoản người đã nghỉ.',
                'steps' => [
                    ['Thêm người mới', 'bấm "Thêm tài khoản", điền tên, tên đăng nhập, chọn vai trò, đặt mật khẩu.'],
                    ['Quên mật khẩu', 'bấm "Sửa" ở dòng người đó, gõ mật khẩu mới ở phần "Đặt lại mật khẩu".'],
                    ['Người nghỉ việc', 'bấm "Khóa". Lịch sử vẫn biết người đó đã làm gì.'],
                ],
            ],
        ],
        'roles' => [
            'group' => 'Quản lý hệ thống', 'label' => 'Phân quyền', 'icon' => 'heroicon-o-shield-check',
            'permission' => 'accounts.manage',
            'page' => RoleResource::class,
            'summary' => 'Mỗi vai trò được làm những việc nào.',
            'help' => [
                'intro' => 'Mỗi vai trò là một bộ việc được làm. Tick việc nào thì mọi người có vai trò đó được làm việc ấy.',
                'steps' => [
                    ['Đổi việc của một vai trò', 'bấm "Sửa" ở vai trò đó, tick hoặc bỏ tick, bấm "Lưu".'],
                    ['Cần vai trò mới (ví dụ Kế toán)', 'bấm "Thêm vai trò", đặt tên, tick việc được làm.'],
                ],
                'note' => 'Quản trị viên luôn được làm mọi việc. Tải dữ liệu khách ra Excel chỉ dành cho quản trị viên.',
            ],
        ],
        'logs' => [
            'group' => 'Quản lý hệ thống', 'label' => 'Lịch sử thao tác', 'icon' => 'heroicon-o-clock',
            'permission' => 'logs.view',
            'page' => ActivityLog::class,
            'summary' => 'Ai sửa gì, ai xem báo cáo của khách nào, lúc nào.',
            'help' => [
                'intro' => 'Ghi lại mọi việc trên trang quản trị để kiểm tra khi cần: sửa nội dung, tra cứu và mở báo cáo của khách, tải dữ liệu.',
                'steps' => [
                    ['Xem việc của một người', 'chọn tên ở bộ lọc "Người làm".'],
                    ['Kiểm tra ai đã tải dữ liệu khách', 'chọn "Tải dữ liệu" ở bộ lọc "Loại việc".'],
                ],
                'note' => 'Lịch sử không sửa, không xóa được.',
            ],
        ],
    ],
];
