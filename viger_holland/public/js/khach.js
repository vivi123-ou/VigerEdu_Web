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

/*
 * Form nhận báo cáo (H4): ô có điều kiện (data-conditions, lấy từ form_fields.conditions) ẩn hoặc đổi chữ ngay khi
 * khách chọn ở ô khác, ví dụ chọn "Phụ huynh" thì ẩn SĐT học sinh và ô Email thành "Email của học sinh".
 * Máy chủ kiểm tra lại theo cùng điều kiện nên tắt JavaScript vẫn đúng.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-report-form]');
    if (!form) {
        return;
    }

    function valueOf(key) {
        var checked = form.querySelector('[name="' + key + '"]:checked');
        if (checked) {
            return checked.value;
        }
        var input = form.querySelector('[name="' + key + '"]:not([type="radio"])');
        return input ? input.value : '';
    }

    function apply() {
        form.querySelectorAll('[data-conditions]').forEach(function (box) {
            var rules = JSON.parse(box.getAttribute('data-conditions') || '{}');
            var hidden = false;
            Object.keys(rules.hide_if || {}).forEach(function (key) {
                if ((rules.hide_if[key] || []).map(String).indexOf(valueOf(key)) !== -1) {
                    hidden = true;
                }
            });
            box.hidden = hidden;

            var label = box.getAttribute('data-label');
            Object.keys(rules.label_if || {}).forEach(function (key) {
                var chosen = valueOf(key);
                if (chosen && rules.label_if[key][chosen]) {
                    label = rules.label_if[key][chosen];
                }
            });
            var text = box.querySelector('[data-label-text]');
            if (text) {
                text.textContent = label;
            }
        });
    }

    form.addEventListener('change', apply);
})();

/*
 * Nhập mã (H4): nút "Gửi lại mã" đếm ngược theo số giây máy chủ báo; ô mã chỉ nhận số.
 */
(function () {
    'use strict';

    var button = document.querySelector('[data-resend]');
    if (button) {
        var left = parseInt(button.getAttribute('data-wait'), 10) || 0;
        var counter = button.querySelector('[data-countdown]');
        var tick = function () {
            if (left <= 0) {
                button.disabled = false;
                counter.textContent = '';
                return;
            }
            button.disabled = true;
            counter.textContent = ' sau ' + Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
            left -= 1;
            setTimeout(tick, 1000);
        };
        tick();
    }

    var otp = document.querySelector('.otp-in');
    if (otp) {
        otp.addEventListener('input', function () {
            otp.value = otp.value.replace(/\D/g, '').slice(0, parseInt(otp.getAttribute('maxlength'), 10) || 6);
        });
    }
})();

/*
 * Báo cáo (H5): bấm nút Gọi / Zalo thì báo cho máy chủ (POST /t) để gửi kèm sang CRM. Dùng sendBeacon nên không làm chậm
 * việc mở Zalo / gọi điện; trình duyệt không hỗ trợ thì bỏ qua.
 */
(function () {
    'use strict';

    var page = document.querySelector('[data-report-page]');
    if (!page || !navigator.sendBeacon) {
        return;
    }
    var url = page.getAttribute('data-track-url');
    var token = document.querySelector('meta[name="csrf-token"]');
    page.addEventListener('click', function (event) {
        var link = event.target.closest('[data-track]');
        if (!link || !url) {
            return;
        }
        var data = new FormData();
        data.append('type', link.getAttribute('data-track'));
        data.append('_token', token ? token.content : '');
        navigator.sendBeacon(url, data);
    });
})();
