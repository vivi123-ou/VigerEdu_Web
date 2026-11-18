/*
 * Trang làm bài (H3): hiệu ứng sao khi rê chuột, hiện chữ của mức đã chọn, đo thời gian trả lời từng câu,
 * nhắc câu còn thiếu trước khi gửi. Không có JavaScript thì trang vẫn làm bài được (sao là ô chọn thật).
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-quiz]');
    if (!form) {
        return;
    }

    var labels = JSON.parse(form.getAttribute('data-labels') || '[]');
    var missingText = form.getAttribute('data-missing') || '';
    var lastTime = Date.now();

    form.querySelectorAll('[data-question]').forEach(function (question) {
        var stars = Array.prototype.slice.call(question.querySelectorAll('.stars label'));
        var inputs = Array.prototype.slice.call(question.querySelectorAll('.stars input'));
        var label = question.querySelector('[data-label]');
        var ms = question.querySelector('[data-ms]');

        function paint(count) {
            stars.forEach(function (star, i) { star.classList.toggle('on', i < count); });
        }

        function checkedValue() {
            var checked = inputs.filter(function (input) { return input.checked; })[0];
            return checked ? parseInt(checked.value, 10) : 0;
        }

        stars.forEach(function (star, i) {
            star.addEventListener('mouseenter', function () { paint(i + 1); });
        });
        question.querySelector('.stars').addEventListener('mouseleave', function () { paint(checkedValue()); });

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var value = parseInt(input.value, 10);
                paint(value);
                label.textContent = labels[value - 1] || '';
                label.classList.remove('no');
                question.classList.remove('todo');
                // Thời gian của lần chọn đầu tiên: từ lúc mở trang (hoặc câu vừa trả lời trước) tới lúc chọn.
                var now = Date.now();
                if (!ms.value) {
                    ms.value = String(now - lastTime);
                }
                lastTime = now;
                updateProgress();
            });
        });

        paint(checkedValue());
    });

    var bar = document.querySelector('[data-progress]');
    var baseWidth = bar ? parseFloat(bar.style.width) || 0 : 0;
    var initiallyChecked = form.querySelectorAll('.stars input:checked').length;

    function updateProgress() {
        if (!bar) {
            return;
        }
        var total = form.querySelectorAll('[data-question]').length;
        var pageShare = parseFloat(form.getAttribute('data-page-share') || '0');
        var nowChecked = form.querySelectorAll('.stars input:checked').length;
        if (pageShare > 0) {
            bar.style.width = Math.min(100, baseWidth + (nowChecked - initiallyChecked) * pageShare / total) + '%';
        }
    }

    form.addEventListener('submit', function (event) {
        var submitter = event.submitter;
        if (submitter && submitter.value === 'back') {
            return;
        }
        var firstMissing = null;
        form.querySelectorAll('[data-question]').forEach(function (question) {
            if (!question.querySelector('.stars input:checked')) {
                question.classList.add('todo');
                var label = question.querySelector('[data-label]');
                label.textContent = missingText;
                label.classList.add('no');
                firstMissing = firstMissing || question;
            }
        });
        if (firstMissing) {
            event.preventDefault();
            firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
