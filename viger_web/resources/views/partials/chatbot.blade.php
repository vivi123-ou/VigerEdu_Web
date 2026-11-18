{{-- Chatbot AI: nút nổi góc phải dưới + cửa sổ chat --}}
<div class="chatbot" id="chatbot">
  <div
    class="chatbot-window"
    id="chatbot-window"
    role="dialog"
    aria-modal="false"
    aria-labelledby="chatbot-title"
    hidden
  >
    <div class="chatbot-header">
      <div class="chatbot-header-info">
        <span class="chatbot-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="8" width="18" height="12" rx="3" />
            <path d="M12 8V4" />
            <circle cx="12" cy="3" r="1" />
            <circle cx="9" cy="14" r="1" fill="currentColor" />
            <circle cx="15" cy="14" r="1" fill="currentColor" />
          </svg>
        </span>
        <div>
          <div class="chatbot-title" id="chatbot-title">Trợ lý VIGER AI</div>
          <div class="chatbot-status"><span class="chatbot-dot"></span> Đang trực tuyến</div>
        </div>
      </div>
      <button type="button" class="chatbot-close" id="chatbot-close" aria-label="Đóng khung chat">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M18 6 6 18M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="chatbot-messages" id="chatbot-messages" aria-live="polite">
      <div class="chatbot-msg chatbot-msg-bot">
        Xin chào! 👋 Mình là trợ lý AI của VIGER. Bạn cần tư vấn gì về du học Đức?
      </div>
    </div>

    <div class="chatbot-suggestions" id="chatbot-suggestions">
      <button type="button" class="chatbot-chip">Chi phí du học Đức</button>
      <button type="button" class="chatbot-chip">Điều kiện du học</button>
      <button type="button" class="chatbot-chip">Quy trình tư vấn</button>
    </div>

    <form class="chatbot-form" id="chatbot-form" autocomplete="off">
      <input
        type="text"
        class="chatbot-input"
        id="chatbot-input"
        placeholder="Nhập câu hỏi của bạn..."
        aria-label="Nhập câu hỏi"
        maxlength="500"
      />
      <button type="submit" class="chatbot-send" aria-label="Gửi">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 2 11 13" />
          <path d="M22 2 15 22l-4-9-9-4 20-7z" />
        </svg>
      </button>
    </form>
  </div>

  <button
    type="button"
    class="chatbot-toggle"
    id="chatbot-toggle"
    aria-label="Mở Chatbot AI"
    aria-controls="chatbot-window"
    aria-expanded="false"
  >
    <svg class="chatbot-icon-open" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      <path d="M8 10h.01M12 10h.01M16 10h.01" />
    </svg>
    <svg class="chatbot-icon-close" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
      <path d="M18 6 6 18M6 6l12 12" />
    </svg>

  </button>
</div>
