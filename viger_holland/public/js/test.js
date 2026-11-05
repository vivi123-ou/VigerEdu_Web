// Trang làm bài: chia bước, thanh tiến độ (không ghi số câu), lưu tạm câu trả lời Holland trên máy,
// ẩn/hiện câu thông tin theo điều kiện, chép tham số utm_ từ link. Không có JS thì vẫn làm và nộp được.
(function () {
    'use strict';

    var form = document.getElementById('test-form');
    if (!form) return;

    form.classList.add('is-enhanced');

    var steps = Array.prototype.slice.call(form.querySelectorAll('.step'));
    var progressBar = form.querySelector('[data-progress]');
    var storageKey = form.getAttribute('data-storage-key');
    var hasOldInput = form.getAttribute('data-has-old') === '1';
    var current = 0;

    // ---------- Lưu tạm (chỉ câu trả lời Holland, không lưu thông tin cá nhân) ----------
    function load() {
        try { return JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (e) { return {}; }
    }

    // answers: { "answers[B01]": ["1"], "answers[B60][]": ["a", "c"] } – nút chọn, ô tick nhiều, danh sách sổ xuống.
    function save() {
        var data = { answers: {}, step: current };
        form.querySelectorAll('input[name^="answers["]:checked').forEach(function (input) {
            (data.answers[input.name] = data.answers[input.name] || []).push(input.value);
        });
        form.querySelectorAll('select[name^="answers["]').forEach(function (select) {
            if (select.value) data.answers[select.name] = [select.value];
        });
        try { localStorage.setItem(storageKey, JSON.stringify(data)); } catch (e) {}
    }

    function restore() {
        var data = load();
        Object.keys(data.answers || {}).forEach(function (name) {
            var safeName = name.replace(/"/g, '');
            [].concat(data.answers[name]).forEach(function (value) {
                var safeValue = String(value).replace(/"/g, '');
                var input = form.querySelector('input[name="' + safeName + '"][value="' + safeValue + '"]');
                if (input) { input.checked = true; return; }
                var select = form.querySelector('select[name="' + safeName + '"]');
                if (select) select.value = safeValue;
            });
        });
        return typeof data.step === 'number' ? Math.min(data.step, steps.length - 1) : 0;
    }

    // ---------- Chuyển bước ----------
    function show(index, focus) {
        current = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach(function (step, i) { step.hidden = i !== current; });
        if (progressBar) progressBar.style.width = Math.round((current / Math.max(1, steps.length - 1)) * 100) + '%';
        if (focus !== false) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            var heading = steps[current].querySelector('h1, h2, legend');
            if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus({ preventScroll: true }); }
        }
        save();
    }

    function unansweredIn(step) {
        // Câu không bắt buộc (data-optional) được bỏ trống.
        return Array.prototype.slice.call(step.querySelectorAll('fieldset.q:not([data-optional])')).filter(function (fieldset) {
            var select = fieldset.querySelector('select');
            return !fieldset.querySelector('input:checked') && !(select && select.value);
        });
    }

    form.addEventListener('click', function (event) {
        var next = event.target.closest('[data-next]');
        var prev = event.target.closest('[data-prev]');
        if (!next && !prev) return;

        var step = steps[current];
        var warning = step.querySelector('[data-step-warning]');

        if (next) {
            var missing = unansweredIn(step);
            step.querySelectorAll('fieldset.q').forEach(function (fieldset) { fieldset.classList.remove('is-missing'); });
            if (missing.length) {
                missing.forEach(function (fieldset) { fieldset.classList.add('is-missing'); });
                if (warning) warning.hidden = false;
                missing[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (warning) warning.hidden = true;
            show(current + 1);
        } else {
            show(current - 1);
        }
    });

    form.addEventListener('change', function (event) {
        var fieldset = event.target.closest('fieldset.q');
        if (fieldset) {
            fieldset.classList.remove('is-missing', 'has-error');
            save();
        }
        if (event.target.name && event.target.name.indexOf('info[') === 0) updateConditions();
    });

    // ---------- Câu có điều kiện (ví dụ thông tin phụ huynh chỉ hiện khi chọn "Học sinh") ----------
    function valueOf(code) {
        var checked = form.querySelector('[name="info[' + code + ']"]:checked');
        if (checked) return checked.value;
        var field = form.querySelector('select[name="info[' + code + ']"], input[name="info[' + code + ']"]:not([type=radio]):not([type=checkbox])');
        return field ? field.value : '';
    }

    function updateConditions() {
        form.querySelectorAll('[data-show-if]').forEach(function (wrapper) {
            var rule;
            try { rule = JSON.parse(wrapper.getAttribute('data-show-if')); } catch (e) { return; }
            var visible = (rule['in'] || []).map(String).indexOf(String(valueOf(rule.question))) !== -1;
            wrapper.hidden = !visible;
            wrapper.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = !visible; });
        });
    }

    // ---------- Tham số utm_ từ link quảng cáo ----------
    var params = new URLSearchParams(window.location.search);
    form.querySelectorAll('[data-utm]').forEach(function (input) {
        var value = params.get(input.getAttribute('data-utm'));
        if (value && !input.value) input.value = value.slice(0, 100);
    });

    // ---------- Chống bấm nộp 2 lần ----------
    form.addEventListener('submit', function () {
        var button = form.querySelector('[data-submit]');
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
        save();
    });

    // ---------- Khởi động ----------
    updateConditions();

    var start = hasOldInput ? 0 : restore();
    if (hasOldInput) {
        // Có lỗi từ máy chủ: mở bước chứa lỗi đầu tiên.
        var firstError = form.querySelector('.has-error, [data-has-error]');
        var errorStep = firstError ? firstError.closest('.step') : null;
        start = errorStep ? steps.indexOf(errorStep) : steps.length - 1;
    }
    show(start, false);
})();
