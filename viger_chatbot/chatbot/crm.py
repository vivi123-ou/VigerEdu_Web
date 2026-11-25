"""Gửi khách sang CRM khi khách để lại SĐT / email trong khung chat.

POST {CRM_API_URL}/leads, gói lead/2, channel "chatbot" (HOP_DONG_KET_NOI.md mục 2.1).
Tư vấn viên thấy khách trong CRM (nguồn "AI Chatbot") kèm SĐT và đoạn chat gần nhất.
"""
from __future__ import annotations

import logging
import os
import time
import uuid

import httpx

log = logging.getLogger(__name__)

CONSENT_TEXT = ("Khách tự nhập số điện thoại / email trong khung chat AI Chatbot sau lời mời: "
                "\"Bạn để lại số điện thoại (hoặc email), tư vấn viên Viger sẽ gọi lại cho bạn\".")
MAX_NOTE = 2000
RETRY_DELAYS = (0, 2, 5)  # giây; gửi lại cùng event_id nên CRM không tạo trùng


def build_lead(contact: dict, history: list[dict], session_code: str) -> dict:
    lines = []
    for m in history[-10:]:
        who = "Khách" if m["role"] == "user" else "Bot"
        text = " ".join(str(m["content"]).split())
        lines.append(f"{who}: {text[:200]}")
    note = f"Khách để lại liên hệ qua AI Chatbot (phiên {session_code}).\nĐoạn chat gần nhất:\n" + "\n".join(lines)
    return {
        "schema_version": "lead/2",
        "event_id": str(uuid.uuid4()),
        "channel": "chatbot",
        "consent": {"agreed": True, "text": CONSENT_TEXT},
        "contact": {k: v for k, v in contact.items() if k in ("phone", "email", "full_name") and v},
        "extra_fields": {"loi_nhan": note[:MAX_NOTE]},
    }


def send_lead(payload: dict) -> bool:
    base = os.getenv("CRM_API_URL", "").rstrip("/")
    token = os.getenv("CRM_API_TOKEN", "")
    if not base or not token:
        log.warning("Chưa cấu hình CRM_API_URL / CRM_API_TOKEN: khách từ chatbot KHÔNG được gửi sang CRM")
        return False
    for delay in RETRY_DELAYS:
        time.sleep(delay)
        try:
            r = httpx.post(f"{base}/leads", json=payload, timeout=10,
                           headers={"X-Api-Token": token, "Accept": "application/json"})
        except httpx.HTTPError as e:
            log.warning("CRM không kết nối được: %s", type(e).__name__)
            continue
        if r.status_code < 300:
            return True
        # không ghi SĐT/email vào log (hợp đồng mục 1)
        log.warning("CRM /leads trả %s (event %s)", r.status_code, payload["event_id"])
        if r.status_code in (401, 403, 422):
            return False  # lỗi cấu hình / dữ liệu: gửi lại cũng vậy
    return False
