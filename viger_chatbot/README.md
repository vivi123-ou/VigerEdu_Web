# viger_chatbot – AI chatbot tư vấn (Python)

Thư mục dành cho source code chatbot của team IT. Hiện đang trống.

Khi đưa code vào:
- Không đưa file `.env`, khóa API, token vào git. Ghi các biến cần có vào `.env.example`.
- Chatbot nối với Holland và CRM **chỉ qua API**, theo `viger_crm/docs/HOP_DONG_KET_NOI.md`:
  - đọc kết quả Holland: `GET https://holland.viger.edu.vn/api/v1/results/{token}` (token `results:read`);
  - gửi phiên chat sang CRM: `POST https://crm.viger.edu.vn/api/v1/chat-sessions`.
- Ghi cách cài đặt và chạy vào file README này.
