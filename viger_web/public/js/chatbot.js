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
     * Câu trả lời soạn sẵn theo từ khoá: dùng khi chưa cấu hình dịch vụ chatbot (CHATBOT_API_URL),
     * khi dịch vụ lỗi, hoặc ở trang tiếng Anh.
     */
    function getLocalReply(text) {
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

    // Dịch vụ chatbot (viger_chatbot) trả chữ thường có **đậm**, xuống dòng và link -> đổi sang HTML an toàn
    function formatBotText(text) {
        var html = String(text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/(https?:\/\/[^\s<)]+)/g, '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>');
        return html.replace(/\n/g, '<br>');
    }

    function renderSuggestions(list) {
        suggestions.innerHTML = '';
        (list || []).slice(0, 4).forEach(function (label) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'chatbot-chip';
            b.textContent = label;
            suggestions.appendChild(b);
        });
    }

    var endpoint = root.dataset.endpoint || '';
    var SESSION_KEY = 'viger_chatbot_session';
    var sessionCode = null;
    try { sessionCode = sessionStorage.getItem(SESSION_KEY); } catch (e) {}
    var busy = false;

    function askServer(text) {
        var ctrl = window.AbortController ? new AbortController() : null;
        var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, 15000) : null;
        return fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ message: text, session_code: sessionCode }),
            signal: ctrl ? ctrl.signal : undefined
        }).then(function (res) {
            if (timer) clearTimeout(timer);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        }).then(function (data) {
            if (data.session_code) {
                sessionCode = data.session_code;
                try { sessionStorage.setItem(SESSION_KEY, sessionCode); } catch (e) {}
            }
            return data;
        });
    }

    function send(text) {
        text = text.trim().slice(0, 1000);
        if (!text || busy) return;
        busy = true;

        addMessage(text, 'user');
        suggestions.innerHTML = '';
        input.value = '';
        var typing = showTyping();

        function done(html, chips) {
            typing.remove();
            addMessage(html, 'bot', true);
            renderSuggestions(chips);
            busy = false;
        }

        if (!endpoint) {
            setTimeout(function () { done(getLocalReply(text)); }, 700);
            return;
        }
        askServer(text).then(function (data) {
            done(formatBotText(data.reply || ''), data.suggestions);
        }).catch(function () {
            done(getLocalReply(text));
        });
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
