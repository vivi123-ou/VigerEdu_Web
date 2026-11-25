"""Bộ trả lời: theo từ khóa (mặc định, miễn phí) hoặc Claude + công cụ tra cứu (khi bật và có API key)."""
from __future__ import annotations

import os

from .data import Catalog
from .rules import DEFAULT_SUGGESTIONS, Reply, RuleEngine
from .tools import TOOLS, run_tool

MODEL = os.getenv("CHATBOT_MODEL", "claude-opus-5-5")
MAX_TOOL_ROUNDS = 5
FALLBACK_REPLY = ("Xin lỗi, hiện mình chưa trả lời được. Bạn để lại số điện thoại hoặc gọi hotline "
                  "để tư vấn viên Viger hỗ trợ nhé.")

SYSTEM_PROMPT = """Bạn là trợ lý tư vấn du học Đức của Viger Edu, trò chuyện bằng tiếng Việt (hoặc ngôn ngữ khách dùng).

Nguyên tắc:
- Thông tin về bang và trường đại học CHỈ lấy từ công cụ (tim_truong, thong_tin_bang, chi_tiet_truong). Không tự nêu tên trường, số liệu, học phí hay điều kiện tuyển sinh không có trong kết quả công cụ.
- Dữ liệu hiện chưa có ngành học cụ thể của từng trường, học phí hay điều kiện đầu vào: nếu khách hỏi, nói rõ là cần tư vấn viên kiểm tra và mời khách để lại số điện thoại.
- Nếu khách đưa mã báo cáo Holland, dùng ket_qua_holland để đọc kết quả rồi gợi ý hướng phù hợp.
- Trả lời ngắn gọn, thân thiện; liệt kê tối đa 5 trường mỗi lần, kèm thành phố và website; nói tổng số trường tìm được nếu nhiều hơn.
- Không hỏi hay nhắc lại thông tin cá nhân nhạy cảm ngoài tên, số điện thoại, email khách tự cung cấp."""


class ClaudeEngine:
    def __init__(self, catalog: Catalog, client=None):
        import anthropic
        self.catalog = catalog
        self.client = client or anthropic.Anthropic()

    def reply(self, history: list[dict]) -> Reply:
        """history: [{role: user|assistant, content: str}] — tin cuối là của khách."""
        messages = [{"role": m["role"], "content": m["content"]} for m in history]
        for _ in range(MAX_TOOL_ROUNDS):
            response = self.client.beta.messages.create(
                model=MODEL,
                max_tokens=4000,
                system=[{"type": "text", "text": SYSTEM_PROMPT, "cache_control": {"type": "ephemeral"}}],
                tools=TOOLS,
                messages=messages,
                output_config={"effort": "low"},
                betas=["server-side-fallback-2026-07-01"],
                fallbacks="default",
            )
            if response.stop_reason == "refusal":
                return Reply(FALLBACK_REPLY, DEFAULT_SUGGESTIONS, "refusal")
            if response.stop_reason != "tool_use":
                text = "".join(b.text for b in response.content if b.type == "text").strip()
                return Reply(text or FALLBACK_REPLY, DEFAULT_SUGGESTIONS, "claude")
            messages.append({"role": "assistant", "content": response.content})
            results = []
            for block in response.content:
                if block.type == "tool_use":
                    content, is_error = run_tool(self.catalog, block.name, block.input)
                    results.append({"type": "tool_result", "tool_use_id": block.id,
                                    "content": content, "is_error": is_error})
            messages.append({"role": "user", "content": results})
        return Reply(FALLBACK_REPLY, DEFAULT_SUGGESTIONS, "fallback")


def make_engine(catalog: Catalog):
    """Mặc định: chatbot theo từ khóa (miễn phí). Chỉ dùng Claude khi CHATBOT_ENGINE=claude và có API key."""
    if os.getenv("CHATBOT_ENGINE", "rules").lower() == "claude" and os.getenv("ANTHROPIC_API_KEY"):
        return ClaudeEngine(catalog)
    return RuleEngine(catalog)
