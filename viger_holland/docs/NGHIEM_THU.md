# Nghiệm thu – Trắc nghiệm Holland

Bản 1 (18/11/2026). Đối chiếu `docs/TIEU_CHI_DE_DUNG_VA_NGHIEM_THU.md` (phần Holland) và các quyết định trong `CLAUDE.md`. Người dùng thật tự làm trên bản chạy thử, IT chỉ đứng xem.

Trạng thái: **Sẵn sàng thử** = đã làm và có test tự động; **Chờ dữ liệu** = chức năng có, cần team bổ sung dữ liệu; **Thuộc CRM** = làm ở `viger_crm`.

## 1. Theo bảng tiêu chí

| # | Việc (vai trò) | Trạng thái | Cách thử |
|---|---|---|---|
| QT-1 | Đổi hotline, địa chỉ, logo (quản trị) | Sẵn sàng thử | Logo, màu, hotline, chân trang → Lưu → mở trang khách |
| QT-4 | Sửa 1 câu hỏi Holland; thêm câu "Trường chuyên?" vào form (quản trị) | Sẵn sàng thử | Câu hỏi trắc nghiệm → Sửa; Form khách điền thông tin → Thêm ô → làm bài, điền form → CRM nhận ở `extra_fields` (xem `crm:payload`) |
| QT-5 | Đổi chữ trên trang kết quả (quản trị) | Sẵn sàng thử | Chữ trên báo cáo & email → sửa → làm bài xem kết quả |
| QT-7 | Tài khoản nhân viên chỉ thấy đúng phần của mình | Sẵn sàng thử | Tài khoản nhân sự → tạo Nhân viên → đăng nhập: chỉ thấy Khách cần liên lạc và các trang nội dung |
| QT-8 | Tắt / bật chatbot | Sẵn sàng thử (phía Holland) | Kết nối API (chatbot) → Thu hồi / Bật lại; khung chat: mã nhúng ở cài đặt |
| TV-1 | Gõ SĐT kiểu gì cũng được, mở đúng hồ sơ | Sẵn sàng thử (Holland) | Tra cứu báo cáo khách → `+84 912 345 678` |
| TV-2 | Xem bài Holland từng câu | Sẵn sàng thử (Holland) / Thuộc CRM | Báo cáo nội bộ → "36 câu khách đã trả lời" |
| MK-4 | Xem số khách mới theo nguồn | Sẵn sàng thử (Holland) | Số liệu làm bài → "Khách đến từ đâu" |
| QT-2, 3, 6; MK-1, 2, 3, 5; TV-3, 4 | Web chính / CRM | Thuộc web chính / CRM | – |

## 2. Theo quyết định của Vân (CLAUDE.md)

| Việc | Trạng thái |
|---|---|
| Làm bài 36 câu, 6 câu / trang, sao 1–5, câu nào cũng bắt buộc, thanh tiến độ liền | Sẵn sàng thử |
| Kết quả sơ lược: mã 2 chữ, 2 ngành rõ, ngành còn lại làm mờ không lộ tên | Sẵn sàng thử |
| Form 8 ô (ngày sinh chọn lịch, SĐT phụ huynh bắt buộc, chọn Phụ huynh thì ẩn SĐT học sinh), câu đồng ý dưới nút | Sẵn sàng thử |
| Mã 6 số qua email (5 phút, sai 5 lần khóa 30 phút, gửi lại sau 60 giây) | Sẵn sàng thử (cần SMTP thật khi chạy thật) |
| Báo cáo chỉ trong thẻ: tải lại còn, đóng thẻ mất; không họ tên, không %, không PDF | Sẵn sàng thử |
| Khách luôn thấy mã 2 chữ; công tắc "Cho khách thấy chữ thứ 3" | Sẵn sàng thử |
| Tư vấn viên chỉ tra cứu từng khách, không danh sách; nhân viên chỉ liên lạc khách mới; số liệu và tải dữ liệu chỉ quản trị viên; trang không có quyền báo 404, không ghi ra giới hạn | Sẵn sàng thử |
| Ngành phải có thật ở Đức theo nguồn chính thức; "Chỉ gợi ý ngành đã kiểm tra" | Chờ dữ liệu: 46 ngành chưa kiểm tra (Ngành học & nghề gợi ý) |
| Đoạn giải thích 6 nhóm, 15 cặp, chữ thứ ba | Sẵn sàng thử; chờ Thầy duyệt nội dung |
| Tên tiếng Việt 51 nghề | Sẵn sàng thử; chờ duyệt cách dịch |
| Gửi khách sang CRM (hàng đợi, gửi lại, đúng hợp đồng 3.1) | Sẵn sàng thử với CRM giả; chờ CRM thật |
| Danh mục 390 trường, 16 bang, nhập Excel nước khác | Sẵn sàng thử; "trường có ngành này" chờ file trường ↔ ngành |
| API cho chatbot | Sẵn sàng thử (docs/API_HOLLAND.md) |
| Sao lưu hằng đêm | Sẵn sàng thử (`php artisan holland:backup`); cần chọn nơi lưu ngoài máy chủ |

## 3. Trước khi chạy thật

- [ ] SMTP gửi mã (hộp thư gửi), Turnstile, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS.
- [ ] Cất riêng `APP_KEY` (mất là không đọc được dữ liệu khách).
- [ ] Hàng đợi và cron chạy trên máy chủ; thử `holland:backup` và khôi phục một lần.
- [ ] Link chính sách bảo mật thật (Chữ trên báo cáo & email).
- [ ] Thầy duyệt nội dung báo cáo (6 nhóm, 15 cặp, chữ thứ ba, tên nghề).
- [ ] Team kiểm tra ngành theo nguồn chính thức, rồi cân nhắc bật "Chỉ gợi ý ngành đã kiểm tra".
- [ ] Bật xác thực 2 lớp cho quản trị viên (chưa làm).
- [ ] Khi có CRM: địa chỉ + token kênh holland → Gửi khách sang CRM → Kiểm tra kết nối → bật.
