// Nút lên đầu trang: hiện khi cuộn xuống quá 400px
(function () {
    var btn = document.getElementById('backToTop');
    if (!btn) return;

    function toggle() {
        btn.classList.toggle('is-visible', window.scrollY > 400);
    }

    window.addEventListener('scroll', toggle, { passive: true });
    toggle();

    btn.addEventListener('click', function () {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });

    // Ẩn nút khi khung chat mở
    var chat = document.querySelector('.chatbot');
    if (chat) {
        new MutationObserver(function () {
            document.body.classList.toggle('chat-open', chat.classList.contains('is-open'));
        }).observe(chat, { attributes: true, attributeFilter: ['class'] });
    }
})();
