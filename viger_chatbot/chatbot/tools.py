"""Công cụ Claude được gọi để tra dữ liệu thật (không tự bịa)."""
from __future__ import annotations

import json

from .data import Catalog
from . import holland

TOOLS = [
    {
        "name": "tim_truong",
        "description": "Tìm trường đại học ở Đức theo bang, thành phố, loại trường, công lập/tư thục hoặc từ khóa tên. "
                       "Trả về tổng số và tối đa `limit` trường.",
        "input_schema": {
            "type": "object",
            "properties": {
                "region": {"type": "string", "description": "Tên hoặc mã bang, vd 'Bayern', 'BY', 'Berlin'"},
                "city": {"type": "string", "description": "Thành phố, vd 'München'"},
                "school_type": {"type": "string", "enum": ["UNI", "HAW", "ART", "ADMIN", "SPECIAL", ""],
                                "description": "UNI = ĐH Nghiên cứu, HAW = ĐH Ứng dụng, ART = Nghệ thuật, "
                                               "ADMIN = Hành chính, SPECIAL = Đặc thù"},
                "ownership": {"type": "string", "enum": ["public", "private", "church", ""]},
                "keyword": {"type": "string", "description": "Từ khóa trong tên trường"},
                "limit": {"type": "integer", "description": "Số trường tối đa (1-30), mặc định 10"},
            },
            "required": [],
        },
    },
    {
        "name": "thong_tin_bang",
        "description": "Thông tin một bang của Đức: thủ phủ, dân số, GDP, lĩnh vực nổi bật, mô tả, mức chi phí, số trường.",
        "input_schema": {"type": "object", "properties": {"region": {"type": "string"}}, "required": ["region"]},
    },
    {
        "name": "chi_tiet_truong",
        "description": "Chi tiết một trường theo mã (T001), tên viết tắt hoặc tên.",
        "input_schema": {"type": "object", "properties": {"query": {"type": "string"}}, "required": ["query"]},
    },
    {
        "name": "ket_qua_holland",
        "description": "Đọc kết quả bài trắc nghiệm Holland của khách theo mã báo cáo (khách cung cấp).",
        "input_schema": {"type": "object", "properties": {"token": {"type": "string"}}, "required": ["token"]},
    },
]


def run_tool(catalog: Catalog, name: str, args: dict) -> tuple[str, bool]:
    """Trả (nội dung JSON, is_error)."""
    try:
        if name == "tim_truong":
            result = catalog.search_schools(**{k: v for k, v in args.items()
                                               if k in ("region", "city", "school_type", "ownership", "keyword", "limit")})
        elif name == "thong_tin_bang":
            result = catalog.region_info(args.get("region", "")) or {"error": "Không có bang này trong dữ liệu"}
        elif name == "chi_tiet_truong":
            result = catalog.school_detail(args.get("query", "")) or {"error": "Không tìm thấy trường"}
        elif name == "ket_qua_holland":
            result = holland.get_result(args.get("token", ""))
        else:
            return json.dumps({"error": f"Công cụ lạ: {name}"}), True
    except Exception as e:  # công cụ lỗi -> báo Claude, không làm sập phiên chat
        return json.dumps({"error": type(e).__name__}), True
    return json.dumps(result, ensure_ascii=False, default=str), False
