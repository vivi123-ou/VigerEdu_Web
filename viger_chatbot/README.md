# viger_chatbot – AI chatbot tư vấn (Python)

Chatbot tư vấn du học Đức. Dữ liệu là 16 bang và 390 trường, lấy từ file đã chuẩn hóa
`data/DU_LIEU_16_BANG_390_TRUONG_DA_CHUAN_HOA.xlsx` (sheet `DM_KHU_VUC`, `DM_TRUONG`).
Claude chỉ trả lời dựa trên kết quả các công cụ tra cứu, không tự bịa tên trường hay số liệu.

## Quy tắc
- Không đưa file `.env`, khóa API hay token vào git. Các biến cần có ghi trong `.env.example`.
- Chatbot nối với Holland và CRM **chỉ qua API**, theo `viger_crm/docs/HOP_DONG_KET_NOI.md`:
  - đọc kết quả Holland: `GET {HOLLAND_API_URL}/results/{token}` (token `results:read`);
  - gửi khách sang CRM: khi khách nhập SĐT hoặc email trong khung chat, chatbot gửi `POST {CRM_API_URL}/leads` (gói `lead/2`, kênh `chatbot`, token `leads:write`) kèm 10 tin chat gần nhất. Tư vấn viên thấy khách trong CRM với nguồn "AI Chatbot". Mỗi SĐT hoặc email chỉ gửi một lần trong một phiên. CRM lỗi thì gửi lại 3 lần, cùng `event_id` nên không tạo trùng.

## Cài đặt và chạy
```bash
cd viger_chatbot
python -m venv .venv && .venv/Scripts/activate      # Linux: source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env                                # điền key / token nếu có
uvicorn app:app --host 127.0.0.1 --port 8100
```
Kiểm tra:
```bash
curl http://127.0.0.1:8100/health
curl -X POST http://127.0.0.1:8100/chat -H "Content-Type: application/json" \
     -d '{"message":"Trường ứng dụng công lập ở Bayern?"}'
```
Gói trả về có dạng `{"reply": "...", "session_code": "CB..."}`. Lần gửi sau gửi kèm `session_code` để giữ ngữ cảnh.

## Hai chế độ
| Chế độ | Bật bằng | Chi phí | Hành vi |
|---|---|---|---|
| **Từ khóa** (`RuleEngine`, mặc định) | `CHATBOT_ENGINE=rules` | 0đ | Nhận ra bang, thành phố, loại trường, công lập/tư thục, tên trường và câu hỏi thường gặp, rồi tra dữ liệu thật. Câu không hiểu thì mời khách để lại số điện thoại |
| Claude (`ClaudeEngine`) | `CHATBOT_ENGINE=claude` và `ANTHROPIC_API_KEY` | Tính theo lượt hỏi | Claude gọi các công cụ `tim_truong`, `thong_tin_bang`, `chi_tiet_truong`, `ket_qua_holland` |

`GET /health` cho biết đang chạy chế độ nào (`mode`).

## Câu hỏi thường gặp (FAQ)
Nội dung nằm trong `data/faq.json`. Mỗi mục gồm:
- `keywords`: từ khóa viết không dấu, chữ thường;
- `answer`: câu trả lời;
- `suggestions`: các nút gợi ý hiện kèm câu trả lời.

Nội dung hiện tại là **bản mẫu**, cần tư vấn viên duyệt lại (học phí, visa, tài khoản phong tỏa…). Sửa xong thì khởi động lại chatbot.

API `/chat` trả về thêm `suggestions` để web hiện thành nút bấm.

## Cập nhật dữ liệu
Thay file trong `data/` (giữ đúng tên sheet và tên cột) rồi khởi động lại dịch vụ.

## Test
```bash
python -m pytest -q
```
Test không gọi AI thật: API Claude được giả lập. `tests/test_rules.py` liệt kê các câu hỏi mẫu và ý định chatbot phải nhận ra. Khi gặp câu khách hỏi mà chatbot hiểu sai, thêm câu đó vào file này rồi sửa `chatbot/rules.py`.

## Còn mở
- CRM chưa có API `chat-sessions`, nên hiện chỉ gửi khách khi khách để lại liên hệ, chưa lưu mọi phiên chat.
- Dữ liệu chưa có ngành, học phí và điều kiện đầu vào của từng trường, nên chatbot sẽ mời khách để lại số điện thoại khi được hỏi về những thông tin này.
- Phiên chat đang lưu trong bộ nhớ, nên chỉ chạy 1 tiến trình uvicorn. Muốn chạy nhiều tiến trình thì cần chuyển sang Redis.
