"""Đọc kết quả Holland qua API (token results:read), theo HOP_DONG_KET_NOI.md mục 3.1."""
from __future__ import annotations

import os
import re

import httpx


def get_result(token: str) -> dict:
    base = os.getenv("HOLLAND_API_URL", "").rstrip("/")
    api_token = os.getenv("HOLLAND_API_TOKEN", "")
    if not base or not api_token:
        return {"error": "Chưa cấu hình kết nối Holland"}
    if not re.fullmatch(r"[A-Za-z0-9_-]{4,64}", token or ""):
        return {"error": "Mã báo cáo không hợp lệ"}
    r = httpx.get(f"{base}/results/{token}", params={"view": "khach"},
                  headers={"X-Api-Token": api_token, "Accept": "application/json"}, timeout=10)
    if r.status_code == 404:
        return {"error": "Không tìm thấy báo cáo với mã này"}
    r.raise_for_status()
    return r.json()
