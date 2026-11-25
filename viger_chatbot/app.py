"""API chatbot: POST /chat, GET /health.  Chạy: uvicorn app:app --port 8100"""
from __future__ import annotations

import os
import re
import secrets
import time
from collections import defaultdict, deque

from dotenv import load_dotenv
from fastapi import BackgroundTasks, FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

load_dotenv()

from chatbot import crm  # noqa: E402
from chatbot.data import get_catalog  # noqa: E402
from chatbot.engine import make_engine  # noqa: E402
from chatbot.rules import EMAIL_RE, PHONE_RE, FALLBACK, DEFAULT_SUGGESTIONS, Reply  # noqa: E402

app = FastAPI(title="Viger Chatbot", docs_url=None, redoc_url=None)
app.add_middleware(
    CORSMiddleware,
    allow_origins=[o.strip() for o in os.getenv("CHATBOT_ALLOWED_ORIGINS", "").split(",") if o.strip()],
    allow_methods=["POST", "GET"],
    allow_headers=["Content-Type"],
)

MAX_HISTORY = 20            # số tin giữ lại mỗi phiên
SESSION_TTL = 2 * 3600      # giây
RATE_PER_MIN = int(os.getenv("CHATBOT_RATE_PER_MIN", "20"))

_sessions: dict[str, dict] = {}
_hits: dict[str, deque] = defaultdict(deque)
_engine = None



def engine():
    global _engine
    if _engine is None:
        _engine = make_engine(get_catalog())
    return _engine


class ChatIn(BaseModel):
    message: str = Field(min_length=1, max_length=1000)
    session_code: str | None = Field(default=None, max_length=64)


class ChatOut(BaseModel):
    reply: str
    suggestions: list[str] = []
    session_code: str


def _rate_limited(ip: str) -> bool:
    now, q = time.time(), _hits[ip]
    while q and q[0] < now - 60:
        q.popleft()
    q.append(now)
    return len(q) > RATE_PER_MIN


def _session(code: str | None) -> tuple[str, dict]:
    now = time.time()
    for k in [k for k, s in _sessions.items() if now - s["updated"] > SESSION_TTL]:
        _sessions.pop(k, None)
    if not code or code not in _sessions or not re.fullmatch(r"[A-Za-z0-9_-]+", code):
        code = "CB" + secrets.token_hex(8)
        _sessions[code] = {"history": [], "contact": {}, "updated": now}
    _sessions[code]["updated"] = now
    return code, _sessions[code]


@app.get("/health")
def health():
    cat = get_catalog()
    return {"ok": True, "mode": type(engine()).__name__, "regions": len(cat.regions), "schools": len(cat.schools)}


@app.post("/chat", response_model=ChatOut)
def chat(body: ChatIn, request: Request, background: BackgroundTasks):
    ip = request.client.host if request.client else "?"
    if _rate_limited(ip):
        raise HTTPException(429, "Bạn gửi quá nhanh, thử lại sau ít phút.")

    code, sess = _session(body.session_code)
    text = body.message.strip()
    new_contact = False
    if m := PHONE_RE.search(text):
        phone = re.sub(r"[\s.-]", "", m.group())
        new_contact |= sess["contact"].get("phone") != phone
        sess["contact"]["phone"] = phone
    if m := EMAIL_RE.search(text):
        new_contact |= sess["contact"].get("email") != m.group()
        sess["contact"]["email"] = m.group()

    sess["history"].append({"role": "user", "content": text})
    sess["history"] = sess["history"][-MAX_HISTORY:]
    while sess["history"] and sess["history"][0]["role"] != "user":
        sess["history"].pop(0)
    try:
        result = engine().reply(sess["history"])
    except Exception:
        result = Reply(FALLBACK, DEFAULT_SUGGESTIONS, "error")
    reply = result.text
    sess["history"].append({"role": "assistant", "content": reply})

    # Khách vừa để lại SĐT / email mới -> gửi sang CRM kèm đoạn chat để tư vấn viên gọi lại
    if new_contact:
        background.add_task(crm.send_lead, crm.build_lead(sess["contact"], sess["history"], code))
    return ChatOut(reply=reply, suggestions=result.suggestions, session_code=code)
