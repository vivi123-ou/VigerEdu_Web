# API của Holland (holland.viger.edu.vn) – cho người làm chatbot, CRM

Bản 1 (18/11/2026). Theo `docs/HOP_DONG_KET_NOI.md` mục 3 (bản 3.1).

## 1. Xác thực

- Mọi lệnh (trừ `public/intro`) cần header `X-Api-Token: <mã kết nối>`.
- Quản trị viên Holland cấp mã ở **Quản lý hệ thống → Kết nối API (chatbot)**. Mã chỉ hiện **một lần** lúc cấp; Holland chỉ giữ bản băm (SHA-256), so bằng `hash_equals`.
- Phạm vi (scope) hiện có: `results:read` (đọc kết quả và danh mục).
- Giữ mã ở phía máy chủ (biến môi trường), **không** đặt trong JavaScript trình duyệt. Lộ mã thì báo quản trị viên "Thu hồi" và cấp mã mới.
- Giới hạn: 60 lần / phút / địa chỉ IP.

| Mã lỗi | Ý nghĩa |
|---|---|
| `401` | Sai, thiếu hoặc đã thu hồi mã |
| `403` | Mã không có phạm vi `results:read` |
| `404` | Không có lượt làm bài này, hoặc khách chưa nhập đúng mã email |
| `429` | Quá 60 lần / phút |

Không có dữ liệu cá nhân (họ tên, email, số điện thoại, ngày sinh) trong mọi phản hồi. Thông tin khách lấy ở CRM.

## 2. `GET /api/v1/results/{attempt_id}?view=khach|tuvan`

`{attempt_id}` là `quiz.attempt_id` trong gói `lead/2` Holland gửi sang CRM (UUID của lượt làm bài).

**`view=khach`** (mặc định): giống báo cáo khách đọc.

```json
{
  "schema_version": "holland-api-result/1", "view": "khach",
  "attempt_id": "b61b8a81-…", "report_code": "B61B8A81", "completed_at": "2026-11-18T09:33:00+07:00",
  "code": "IS", "names": ["Nghiên cứu", "Xã hội"],
  "opening": "Kết quả của bạn nổi bật ở hai nhóm Nghiên cứu và Xã hội.",
  "groups": [ { "heading": "Nhóm nổi bật nhất – Nghiên cứu (I)", "text": "Bạn thích quan sát…" } ],
  "third_letter": null,
  "combination": "Hai nhóm này khác nhau nhưng bổ sung cho nhau…",
  "levels": [ { "code": "I", "name": "Nghiên cứu", "level": "Rất quan tâm" } ],
  "majors": [ { "name_vi": "Tâm lý học", "name_de": "Psychologie", "routes": null, "label": "Gợi ý chính",
                "why": "Ngành nền của cặp Nghiên cứu – Xã hội.", "occupations": ["Nhà nghiên cứu tâm lý học (Psychologieforscher)"] } ]
}
```

**`view=tuvan`**: cho tư vấn (bản nội bộ).

```json
{
  "schema_version": "holland-api-result/1", "view": "tuvan", "attempt_id": "…", "report_code": "B61B8A81", "completed_at": "…",
  "holland_result": { "schema_version": "holland-result/3", "code_full": "ISA", "code_public": "IS", "…": "xem HOP_DONG_KET_NOI.md mục 3.2" },
  "answers": [ { "code": "I012", "group": "I", "value": 4, "ms": 5200 } ]
}
```

Không có phần trăm phù hợp. Mã khách thấy (`code`, `code_public`) hiện luôn 2 chữ; mã 3 chữ chỉ ở bản `tuvan`.

## 3. `GET /api/v1/catalog/{types|pairs|third|majors}`

Danh mục đúng như đang hiện trong báo cáo (admin sửa được):

| Loại | Nội dung |
|---|---|
| `types` | 6 nhóm: `code`, `name`, `label`, `description` |
| `pairs` | 15 cặp: `pair`, `name`, `relation`, `description`, `base_majors` (mã ngành nền) |
| `third` | Chữ thứ ba: `code`, `specialty_vi`, `specialty_de`, `skills`, `description` |
| `majors` | Ngành đang dùng: `code`, `name_vi`, `name_de`, `name_en`, `holland_codes`, `routes` (chỉ khi đã kiểm tra), `is_verified`, `source_url` |

Phản hồi: `{ "schema_version": "holland-catalog/1", "type": "pairs", "items": [ … ] }`.

## 4. `GET /api/v1/public/intro` (không cần mã)

Khối giới thiệu cho web chính (tiêu đề, mô tả, ảnh, chữ nút, link làm bài).

## 5. Ví dụ (Python)

```python
import os, requests
r = requests.get(
    f"https://holland.viger.edu.vn/api/v1/results/{attempt_id}",
    params={"view": "khach"},
    headers={"X-Api-Token": os.environ["HOLLAND_TOKEN"], "Accept": "application/json"},
    timeout=10,
)
r.raise_for_status()
report = r.json()
```
