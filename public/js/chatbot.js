/* Chatbot AI: mở/đóng cửa sổ chat và xử lý tin nhắn */
(function () {
    var root = document.getElementById('chatbot');
    if (!root) return;

    var toggle = document.getElementById('chatbot-toggle');
    var chatWindow = document.getElementById('chatbot-window');
    var closeBtn = document.getElementById('chatbot-close');
    var form = document.getElementById('chatbot-form');
    var input = document.getElementById('chatbot-input');
    var messages = document.getElementById('chatbot-messages');
    var suggestions = document.getElementById('chatbot-suggestions');

    // Chữ giao diện + câu trả lời theo ngôn ngữ đang chọn (từ resources/lang/*/chatbot.php)
    var t = {};
    try { t = JSON.parse(root.dataset.i18n || '{}'); } catch (e) {}

    function openChat() {
        chatWindow.hidden = false;
        root.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', t.close_bot);
        input.focus();
    }

    function closeChat() {
        chatWindow.hidden = true;
        root.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', t.open);
        toggle.focus();
    }

    toggle.addEventListener('click', function () {
        chatWindow.hidden ? openChat() : closeChat();
    });
    closeBtn.addEventListener('click', closeChat);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !chatWindow.hidden) closeChat();
    });

    function addMessage(text, from, allowHtml) {
        var el = document.createElement('div');
        el.className = 'chatbot-msg chatbot-msg-' + from;
        if (allowHtml) el.innerHTML = text;
        else el.textContent = text;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    }

    function showTyping() {
        return addMessage('<span class="chatbot-typing"><span></span><span></span><span></span></span>', 'bot', true);
    }

    /*
     * Trả lời tạm thời theo từ khoá.
     * Khi kết nối AI thật, thay hàm này bằng một request tới backend (vd POST /chatbot).
     */
    function getBotReply(text) {
        var q = text.toLowerCase();
        var r = t.replies || {};
        if (/chi phí|học phí|tiền|giá|cost|fee|tuition|price|money/.test(q)) return r.cost;
        if (/điều kiện|yêu cầu|hồ sơ|tiếng đức|ielts|requirement|admission|condition|document|german/.test(q)) return r.requirements;
        if (/quy trình|các bước|tư vấn|process|step|consult/.test(q)) return r.process;
        if (/liên hệ|hotline|email|gặp|tư vấn viên|contact|phone|call/.test(q)) return r.contact;
        if (/chương trình|ngành|khoá học|khóa học|program|course|major/.test(q)) return r.programs;
        if (/^(xin )?chào|^hi|^hello|^hey/.test(q)) return r.greeting;
        return r.fallback;
    }

    function send(text) {
        text = text.trim();
        if (!text) return;

        addMessage(text, 'user');
        suggestions.innerHTML = '';
        input.value = '';

        var typing = showTyping();
        setTimeout(function () {
            typing.remove();
            addMessage(getBotReply(text), 'bot', true);
        }, 700);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send(input.value);
    });

    suggestions.addEventListener('click', function (e) {
        if (e.target.classList.contains('chatbot-chip')) {
            send(e.target.textContent);
        }
    });
})();
