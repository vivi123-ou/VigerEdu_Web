# TIÊU CHÍ "DỄ DÙNG – KHÔNG CẦN CODE" VÀ NGHIỆM THU THEO VAI TRÒ
Áp dụng cho cả 3 platform: Web chính · Test Holland · CRM

> Người dùng thật là **sếp (quản trị)**, **team marketing**, **tư vấn viên** – không ai làm IT.
> Một tính năng chỉ được coi là **XONG** khi **người dùng thật tự làm được** theo bảng nghiệm thu ở mục 3, **không cần lập trình viên ngồi cạnh và không sửa code**.

## 1. Quy tắc thiết kế admin (bắt buộc)

1. **Tiếng Việt 100%**, dùng chữ đời thường ("Thêm thư mục", "Ẩn bài", "Gửi duyệt"), không dùng từ kỹ thuật (slug, token, migration…) ở màn hình người dùng thường; chữ kỹ thuật chỉ ở mục Cấu hình kèm giải thích.
2. **Mọi thứ khách nhìn thấy đều sửa được trên admin**: chữ, hình, video, banner, menu, chân trang, thông tin công ty, form, câu hỏi, danh mục, lựa chọn. Không có chữ nào "ghi cứng" trong code.
3. **Thêm được cái mới**: thư mục, loại nội dung, trường thông tin, chuyên mục, tag, trạng thái, vai trò, mẫu xuất Excel… đều có nút **"+ Thêm"**.
4. **Mỗi ô nhập có chú thích nhỏ** ngay dưới ("Ảnh nên rộng 1920px", "Số điện thoại hiện ở chân trang"); báo lỗi rõ ràng bằng tiếng Việt ngay tại ô sai.
5. **Không sợ làm hỏng**: xem trước trước khi lưu; lưu phiên bản và quay lại bản cũ; xóa = vào thùng rác (khôi phục được); hỏi xác nhận trước thao tác lớn (xóa nhiều, đổi cấu hình); nhật ký ai sửa gì lúc nào.
6. **Ít bước**: việc thường ngày (đăng bài, đổi banner, tìm khách theo SĐT, xuất Excel) làm được trong **≤ 3 lần bấm** từ bảng điều khiển.
7. **Dùng được trên laptop, máy tính bảng và điện thoại** (đặc biệt: tìm khách, xem hồ sơ khách, duyệt bài trên điện thoại).
8. **Hướng dẫn ngay trong admin**: nút **"?"** ở mỗi trang mở hướng dẫn ngắn có ảnh; trang **"Hướng dẫn sử dụng"** theo vai trò; lần đăng nhập đầu có **tour giới thiệu** 5–7 bước (tắt được).
9. **Mỗi người chỉ thấy phần của mình** (theo vai trò) → màn hình gọn, khó bấm nhầm.
10. **Dữ liệu ra Excel bất cứ lúc nào**, đúng tiếng Việt, mở được bằng Excel / Google Sheets.

## 2. Bàn giao để dùng lâu dài

- **Sổ tay sử dụng** (PDF + trang trong admin) cho từng vai trò, mỗi việc 1 trang: mục đích → các bước có ảnh chụp → lỗi thường gặp.
- **Video quay màn hình 2–5 phút** cho các việc chính (đăng bài, đổi banner, thêm thư mục, thêm câu hỏi Holland, tìm khách, xuất Excel).
- **Buổi hướng dẫn trực tiếp** cho sếp và marketing; ghi lại câu hỏi để bổ sung sổ tay.
- **Tài khoản mẫu** cho từng vai trò trên bản chạy thử để tập.
- **Sổ tay kỹ thuật** (cho người IT sau này): cài đặt, sao lưu/khôi phục, cập nhật phiên bản, gỡ/gắn kết nối.

## 3. Bảng nghiệm thu theo vai trò (người dùng thật tự làm, IT chỉ đứng xem)

### Sếp – quản trị viên
| # | Việc | Platform | Đạt khi |
|---|---|---|---|
| 1 | Đổi số hotline, địa chỉ, logo | Web chính | Trang ngoài đổi ngay, không cần code |
| 2 | Tạo thư mục mới "Học bổng" + thư mục con "DAAD", gắn vào menu | Web chính | Hiện trên menu, mở được trang |
| 3 | Tạo loại nội dung mới "Đối tác trường" với 4 trường thông tin | Web chính | Đăng được 1 mục, hiện đúng trên trang |
| 4 | Sửa 1 câu hỏi Holland, thêm 1 câu Phần A "Trường chuyên?" | Holland | Khách làm bài thấy câu mới; CRM nhận được câu trả lời |
| 5 | Đổi chữ trên trang kết quả Holland | Holland | Trang kết quả đổi ngay |
| 6 | Thêm trạng thái chăm sóc "Đã ký hợp đồng", thêm trường "Ngày ký" | CRM | Chọn được trên hồ sơ, có trong Excel |
| 7 | Tạo tài khoản cho nhân viên marketing chỉ được đăng tin và xem báo cáo | Cả 3 | Nhân viên đăng nhập chỉ thấy đúng phần đó |
| 8 | Tắt kết nối chatbot rồi bật lại | Web chính / Holland | Khung chat biến mất / hiện lại |

### Team marketing
| # | Việc | Platform | Đạt khi |
|---|---|---|---|
| 1 | Đăng bài tin tức có ảnh, video YouTube, gắn tag, hẹn giờ đăng | Web chính | Bài tự hiện đúng giờ |
| 2 | Thay banner trang chủ, cho chạy từ thứ 2 đến chủ nhật | Web chính | Banner tự hiện và tự tắt |
| 3 | Tạo form "Đăng ký hội thảo" có thêm ô "Lớp" | Web chính | Gửi form → khách xuất hiện trong CRM |
| 4 | Xem số khách mới tuần này theo nguồn (Facebook, web, Holland) | CRM | Có số liệu, xuất được Excel |
| 5 | Đặt lịch gửi Excel danh sách khách mới mỗi sáng thứ 2 | CRM | Email tự đến đúng lịch |

### Tư vấn viên
| # | Việc | Platform | Đạt khi |
|---|---|---|---|
| 1 | Khách gọi đến: gõ SĐT (kiểu gì cũng được: có cách, +84) | CRM | Mở đúng hồ sơ trong ≤ 3 giây |
| 2 | Xem bài test Holland từng câu và tin nhắn chatbot của khách | CRM | Thấy đầy đủ trên 1 hồ sơ |
| 3 | Ghi nhận cuộc gọi, đổi trạng thái, đặt lịch hẹn | CRM | Lưu được, hiện trên dòng thời gian |
| 4 | Sửa đề xuất trường cho khách theo mong muốn phụ huynh | CRM | Lưu được, đánh dấu "tư vấn viên đã chỉnh" |

**Cách chạy nghiệm thu:** cuối mỗi giai đoạn, gửi link bản chạy thử + tài khoản mẫu cho đúng người dùng; họ tự làm theo bảng (không hướng dẫn trước, chỉ được đọc nút "?"); ghi lại chỗ vướng → sửa → làm lại đến khi đạt. Lưu kết quả vào `docs/NGHIEM_THU.md` (ngày, người thử, đạt/chưa, ghi chú).
