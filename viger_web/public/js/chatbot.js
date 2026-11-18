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

    function openChat() {
        chatWindow.hidden = false;
        root.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Đóng Chatbot AI');
        input.focus();
    }

    function closeChat() {
        chatWindow.hidden = true;
        root.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Mở Chatbot AI');
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
        if (/chi phí|học phí|tiền|giá/.test(q)) {
            return 'Các trường công lập tại Đức hầu như miễn học phí, bạn chỉ cần đóng phí học kỳ và lo chi phí sinh hoạt. Xem thêm tại <a href="/giao-duc-tai-duc/chi-phi-va-co-hoi">Chi phí và cơ hội</a>.';
        }
        if (/điều kiện|yêu cầu|hồ sơ|tiếng đức|ielts/.test(q)) {
            return 'Điều kiện thường gồm: bằng tốt nghiệp THPT/ĐH, chứng chỉ ngoại ngữ (tiếng Đức hoặc tiếng Anh) và chứng minh tài chính. Bạn có thể tham khảo <a href="/tu-van">trang Tư vấn</a> để biết chi tiết.';
        }
        if (/quy trình|các bước|tư vấn/.test(q)) {
            return 'Quy trình tư vấn của VIGER gồm nhiều bước từ đánh giá hồ sơ đến khi bạn sang Đức. Xem chi tiết tại <a href="/tu-van/quy-trinh-tu-van">Quy trình tư vấn</a>.';
        }
        if (/liên hệ|hotline|email|gặp|tư vấn viên/.test(q)) {
            return 'Bạn có thể để lại thông tin tại <a href="/lien-he">trang Liên hệ</a>, đội ngũ VIGER sẽ phản hồi sớm nhất.';
        }
        if (/chương trình|ngành|khoá học|khóa học/.test(q)) {
            return 'Bạn có thể tham khảo các chương trình tại <a href="/chuong-trinh-hoc">Chương trình học</a>.';
        }
        if (/^(xin )?chào|^hi|^hello/.test(q)) {
            return 'Chào bạn! Bạn muốn tìm hiểu về chi phí, điều kiện hay quy trình du học Đức?';
        }
        return 'Cảm ơn câu hỏi của bạn! Để được tư vấn chi tiết, bạn vui lòng để lại thông tin tại <a href="/lien-he">trang Liên hệ</a> nhé.';
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
