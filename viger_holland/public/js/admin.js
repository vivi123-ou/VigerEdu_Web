// Trang quản trị: mở/đóng menu trên điện thoại, nút "?" hướng dẫn, đóng menu thả xuống khi bấm ra ngoài.
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    var burger = document.querySelector('.topbar__burger');

    document.querySelectorAll('[data-toggle-sidebar]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (!sidebar) return;
            var open = sidebar.classList.toggle('is-open');
            if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            sidebar.classList.remove('is-open');
            if (burger) burger.setAttribute('aria-expanded', 'false');
        }
    });

    var helpButton = document.querySelector('[data-open-help]');
    var helpDialog = document.getElementById('help-dialog');
    if (helpButton && helpDialog && typeof helpDialog.showModal === 'function') {
        helpButton.addEventListener('click', function () { helpDialog.showModal(); });
    }

    document.addEventListener('click', function (event) {
        document.querySelectorAll('details.dropdown[open]').forEach(function (details) {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });

    // Hỏi xác nhận trước thao tác lớn: <form data-confirm="…"> hoặc <button data-confirm="…">.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var submitter = event.submitter;
        var message = (submitter && submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) event.preventDefault();
    });

    // Nút "Chép link".
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = button.parentElement.querySelector('[data-copy-source]');
            if (!input) return;
            input.select();
            var done = function () {
                var text = button.textContent;
                button.textContent = 'Đã chép';
                setTimeout(function () { button.textContent = text; }, 1500);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
            } else {
                document.execCommand('copy');
                done();
            }
        });
    });

    // Kéo thả để sắp xếp: kéo ô ⋮⋮, rồi bấm "Lưu thứ tự".
    document.querySelectorAll('[data-sortable]').forEach(function (body) {
        var card = body.closest('.card');
        var saveForm = card ? card.querySelector('[data-reorder-form]') : null;
        var dragging = null;

        body.addEventListener('dragstart', function (event) {
            var row = event.target.closest('tr');
            if (!row || !event.target.classList.contains('drag')) return;
            dragging = row;
            row.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', row.getAttribute('data-id'));
        });

        body.addEventListener('dragover', function (event) {
            if (!dragging) return;
            event.preventDefault();
            var target = event.target.closest('tr');
            if (!target || target === dragging) return;
            var box = target.getBoundingClientRect();
            var after = event.clientY > box.top + box.height / 2;
            body.insertBefore(dragging, after ? target.nextSibling : target);
        });

        body.addEventListener('dragend', function () {
            if (!dragging) return;
            dragging.classList.remove('is-dragging');
            dragging = null;
            if (saveForm) saveForm.hidden = false;
        });

        if (saveForm) {
            saveForm.addEventListener('submit', function () {
                saveForm.querySelectorAll('input[name="order[]"]').forEach(function (input) { input.remove(); });
                body.querySelectorAll('tr[data-id]').forEach(function (row) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'order[]';
                    input.value = row.getAttribute('data-id');
                    saveForm.appendChild(input);
                });
            });
        }
    });

    // Form câu hỏi (Holland và thông tin khách): ẩn/hiện ô theo kiểu câu, trường CRM và câu điều kiện.
    // Ô đang ẩn bị tắt để không gửi nhầm (ví dụ 2 ô "Hiện dạng" cho 2 kiểu câu khác nhau).
    var infoForm = document.querySelector('[data-info-form], [data-type-form]');
    if (infoForm) {
        var typeSelect = infoForm.querySelector('[data-answer-type]');
        var targetSelect = infoForm.querySelector('[data-crm-target]');
        var conditionSelect = infoForm.querySelector('[data-condition-question]');

        var refresh = function () {
            var type = typeSelect ? typeSelect.value : '';
            infoForm.querySelectorAll('[data-show-for]').forEach(function (el) {
                var visible = el.getAttribute('data-show-for').split(' ').indexOf(type) !== -1;
                el.hidden = !visible;
                el.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = !visible; });
                if (el.matches('input, select, textarea')) el.disabled = !visible;
            });
            infoForm.querySelectorAll('[data-show-for-target]').forEach(function (el) {
                el.hidden = !targetSelect || targetSelect.value !== el.getAttribute('data-show-for-target');
            });
            infoForm.querySelectorAll('[data-condition-for]').forEach(function (el) {
                var active = conditionSelect && conditionSelect.value === el.getAttribute('data-condition-for');
                el.hidden = !active;
                el.querySelectorAll('input').forEach(function (input) { input.disabled = !active; });
            });
        };

        [typeSelect, targetSelect, conditionSelect].forEach(function (el) { if (el) el.addEventListener('change', refresh); });
        refresh();

        // Lựa chọn của câu chọn 1 / chọn nhiều: "+ Thêm lựa chọn", "Bỏ".
        var choiceBody = infoForm.querySelector('[data-choices]');
        var choiceTemplate = infoForm.querySelector('[data-choice-template]');
        var addChoice = infoForm.querySelector('[data-add-choice]');
        if (choiceBody && choiceTemplate && addChoice) {
            var nextIndex = choiceBody.querySelectorAll('tr').length;
            addChoice.addEventListener('click', function () {
                var html = choiceTemplate.innerHTML.replace(/__i__/g, String(nextIndex++));
                choiceBody.insertAdjacentHTML('beforeend', html);
                var inputs = choiceBody.querySelectorAll('tr:last-child input');
                if (inputs[0]) inputs[0].focus();
            });
            choiceBody.addEventListener('click', function (event) {
                var remove = event.target.closest('[data-remove-choice]');
                if (!remove) return;
                var row = remove.closest('tr');
                if (choiceBody.querySelectorAll('tr').length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                }
            });
        }
    }

    // Chạy thử chấm điểm: chọn học sinh mẫu thì điền sẵn % 6 nhóm.
    var sample = document.querySelector('[data-trial-sample]');
    if (sample) {
        sample.addEventListener('change', function () {
            if (!sample.value) return;
            var percent = JSON.parse(sample.value);
            Object.keys(percent).forEach(function (group) {
                var input = document.querySelector('[data-trial-group="' + group + '"]');
                if (input) input.value = percent[group];
            });
        });
    }
})();
