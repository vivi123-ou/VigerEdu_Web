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

    // Kéo thả để sắp xếp: kéo ô ⋮⋮ rồi thả – thứ tự tự lưu ngay.
    document.querySelectorAll('[data-sortable]').forEach(function (body) {
        var holder = body.closest('.card, [data-sort-holder]') || document;
        var saveForm = holder.querySelector('[data-reorder-form]');
        var dragging = null;
        var rowOf = function (el) {
            var row = el && el.closest('[data-id]');
            return row && row.parentElement === body ? row : null;
        };

        body.addEventListener('dragstart', function (event) {
            if (!event.target.classList || !event.target.classList.contains('drag')) return;
            dragging = rowOf(event.target);
            if (!dragging) return;
            dragging.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragging.getAttribute('data-id'));
        });

        body.addEventListener('dragover', function (event) {
            if (!dragging) return;
            event.preventDefault();
            var target = rowOf(event.target);
            if (!target || target === dragging) return;
            var box = target.getBoundingClientRect();
            body.insertBefore(dragging, event.clientY > box.top + box.height / 2 ? target.nextSibling : target);
        });

        body.addEventListener('dragend', function () {
            if (!dragging) return;
            dragging.classList.remove('is-dragging');
            dragging = null;
            if (!saveForm) return;
            saveForm.querySelectorAll('input[name="order[]"]').forEach(function (input) { input.remove(); });
            Array.prototype.forEach.call(body.children, function (row) {
                if (!row.hasAttribute('data-id')) return;
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order[]';
                input.value = row.getAttribute('data-id');
                saveForm.appendChild(input);
            });
            saveForm.submit();
        });
    });

    // Form câu hỏi: ẩn/hiện ô theo kiểu câu, mục lưu hồ sơ khách và câu điều kiện.
    // Ô đang ẩn bị tắt để không gửi nhầm (ví dụ 2 ô "các mức" cho 2 kiểu câu khác nhau).
    function initTypeForm(form) {
        if (form.hasAttribute('data-type-ready')) return;
        form.setAttribute('data-type-ready', '');
        var typeSelect = form.querySelector('[data-answer-type]');
        var targetSelect = form.querySelector('[data-crm-target]');
        var conditionSelect = form.querySelector('[data-condition-question]');

        var refresh = function () {
            var type = typeSelect ? typeSelect.value : '';
            form.querySelectorAll('[data-show-for]').forEach(function (el) {
                var visible = el.getAttribute('data-show-for').split(' ').indexOf(type) !== -1;
                el.hidden = !visible;
                el.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = !visible; });
            });
            form.querySelectorAll('[data-show-for-target]').forEach(function (el) {
                el.hidden = !targetSelect || targetSelect.value !== el.getAttribute('data-show-for-target');
            });
            form.querySelectorAll('[data-condition-for]').forEach(function (el) {
                var active = conditionSelect && conditionSelect.value === el.getAttribute('data-condition-for');
                el.hidden = !active;
                el.querySelectorAll('input').forEach(function (input) { input.disabled = !active; });
            });
        };

        [typeSelect, targetSelect, conditionSelect].forEach(function (el) { if (el) el.addEventListener('change', refresh); });
        refresh();
    }
    document.querySelectorAll('[data-type-form]').forEach(initTypeForm);
    window.HollandAdmin = { initTypeForm: initTypeForm };

    // Danh sách dòng dùng chung (lựa chọn, link, câu hỏi thường gặp…): "+ Thêm", "Bỏ". Chạy cả với phần thêm sau.
    var rowCounter = 1000;
    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add-row]');
        if (add) {
            var holder = add.parentElement;
            var body = holder.querySelector('[data-rows]');
            var template = holder.querySelector('[data-row-template]');
            if (!body || !template) return;
            body.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__i__/g, String(rowCounter++)));
            var first = body.lastElementChild && body.lastElementChild.querySelector('input, textarea');
            if (first) first.focus();
            body.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }

        var remove = event.target.closest('[data-remove-row]');
        if (!remove) return;
        var row = remove.closest('tr, [data-row]');
        var rows = row && row.parentElement;
        if (!rows) return;
        if (rows.children.length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input, textarea').forEach(function (input) {
                if (input.type === 'checkbox') input.checked = false; else input.value = '';
            });
        }
        rows.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Ô màu: bảng màu và mã màu đi cùng nhau.
    document.querySelectorAll('[data-color-picker]').forEach(function (picker) {
        var text = picker.parentElement.querySelector('[data-color-text]');
        if (!text) return;
        picker.addEventListener('input', function () { text.value = picker.value; });
        text.addEventListener('input', function () {
            if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value;
        });
    });

    // Ô ảnh + hộp "Chọn ảnh" (thư viện ảnh).
    var mediaDialog = document.querySelector('[data-media-dialog]');
    var activeImageField = null;

    function setImage(field, path, url) {
        field.querySelector('[data-image-input]').value = path;
        var preview = field.querySelector('[data-image-preview]');
        var clear = field.querySelector('[data-clear-image]');
        preview.src = url || '';
        preview.hidden = !path;
        if (clear) clear.hidden = !path;
    }

    document.addEventListener('click', function (event) {
        var pick = event.target.closest('[data-pick-image]');
        var clear = event.target.closest('[data-clear-image]');
        if (clear) { setImage(clear.closest('[data-image-field]'), '', ''); return; }
        if (!pick || !mediaDialog) return;
        activeImageField = pick.closest('[data-image-field]');
        loadMedia(mediaDialog.getAttribute('data-list-url'), true);
        mediaDialog.showModal();
    });

    function renderMedia(items, replace) {
        var list = mediaDialog.querySelector('[data-media-list]');
        if (replace) list.innerHTML = '';
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'media-grid__item';
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'media-grid__pick';
            button.title = item.name;
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = item.name;
            img.loading = 'lazy';
            var meta = document.createElement('span');
            meta.className = 'media-grid__meta';
            meta.textContent = item.width + '×' + item.height + ' · ' + item.size;
            button.appendChild(img);
            button.appendChild(meta);
            button.addEventListener('click', function () {
                if (activeImageField) setImage(activeImageField, item.path, item.url);
                mediaDialog.close();
            });
            li.appendChild(button);
            list.appendChild(li);
        });
        var status = mediaDialog.querySelector('[data-media-status]');
        if (replace && !items.length) status.textContent = 'Thư viện chưa có ảnh. Bấm "Tải ảnh mới lên".';
    }

    function loadMedia(url, replace) {
        if (!url) return;
        var status = mediaDialog.querySelector('[data-media-status]');
        status.textContent = 'Đang tải ảnh…';
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                status.textContent = '';
                renderMedia(data.items || [], replace);
                var more = mediaDialog.querySelector('[data-media-more]');
                more.hidden = !data.next;
                more.onclick = function () { loadMedia(data.next, false); };
            })
            .catch(function () { status.textContent = 'Không tải được thư viện ảnh. Vui lòng thử lại.'; });
    }

    if (mediaDialog) {
        mediaDialog.querySelector('[data-media-upload]').addEventListener('change', function (event) {
            var file = event.target.files[0];
            if (!file) return;
            var status = mediaDialog.querySelector('[data-media-status]');
            var data = new FormData();
            data.append('files[]', file);
            status.textContent = 'Đang tải ảnh lên…';
            fetch(mediaDialog.getAttribute('data-upload-url'), {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
                .then(function (response) { return response.json().then(function (json) { return { ok: response.ok, json: json }; }); })
                .then(function (result) {
                    event.target.value = '';
                    if (!result.ok) { status.textContent = result.json.message || 'Không tải được ảnh.'; return; }
                    var item = result.json.items[0];
                    status.textContent = '';
                    if (activeImageField) setImage(activeImageField, item.path, item.url);
                    mediaDialog.close();
                })
                .catch(function () { status.textContent = 'Không tải được ảnh. Vui lòng thử lại.'; });
        });
    }

})();

// ---------- Thẻ câu hỏi (kiểu Google Form): bấm để sửa tại chỗ, tự lưu, nhân đôi, xóa, thêm nhanh ----------
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;
    var timers = new WeakMap();

    function request(url, method, body) {
        return fetch(url, {
            method: method,
            body: body || null,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) { return { ok: response.ok, status: response.status, json: json }; });
        });
    }

    function setStatus(card, text, kind) {
        var status = card.querySelector('[data-qcard-status]');
        if (!status) return;
        status.textContent = text;
        status.className = 'qcard__status' + (kind ? ' is-' + kind : '');
    }

    function showErrors(card, errors) {
        var box = card.querySelector('[data-qcard-errors]');
        if (!box) return;
        var messages = [];
        Object.keys(errors || {}).forEach(function (key) { messages = messages.concat(errors[key]); });
        box.hidden = !messages.length;
        box.innerHTML = '';
        messages.slice(0, 5).forEach(function (message) {
            var p = document.createElement('p');
            p.className = 'field__error';
            p.textContent = message;
            box.appendChild(p);
        });
    }

    function save(form) {
        var card = form.closest('[data-qcard]');
        setStatus(card, 'Đang lưu…');
        request(form.action, 'POST', new FormData(form)).then(function (result) {
            if (result.ok) {
                showErrors(card, {});
                setStatus(card, 'Đã lưu ✓', 'ok');
                card.querySelector('[data-qcard-text]').textContent = result.json.text;
                card.querySelector('[data-qcard-meta]').textContent = result.json.meta;
                card.classList.toggle('is-off', !result.json.is_active);
            } else if (result.status === 422) {
                showErrors(card, result.json.errors || { x: [result.json.message] });
                setStatus(card, 'Chưa lưu – xem dòng đỏ', 'error');
            } else {
                setStatus(card, 'Chưa lưu được, thử lại sau', 'error');
            }
        }).catch(function () { setStatus(card, 'Mất kết nối, chưa lưu', 'error'); });
    }

    function queueSave(form) {
        clearTimeout(timers.get(form));
        setStatus(form.closest('[data-qcard]'), 'Đang sửa…');
        timers.set(form, setTimeout(function () { save(form); }, 900));
    }

    function openCard(card) {
        var body = card.querySelector('[data-qcard-body]');
        var tools = card.querySelector('[data-qcard-tools]');
        var toggle = card.querySelector('[data-qcard-toggle]');
        var show = function () {
            body.hidden = false;
            tools.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            card.classList.add('is-open');
            var first = body.querySelector('textarea, input:not([type=hidden])');
            if (first) first.focus({ preventScroll: true });
        };
        if (body.childElementCount) { show(); return; }
        setStatus(card, 'Đang mở…');
        fetch(card.getAttribute('data-form-url'), { credentials: 'same-origin', headers: { Accept: 'text/html' } })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                body.innerHTML = html;
                var form = body.querySelector('form');
                if (form && window.HollandAdmin) window.HollandAdmin.initTypeForm(form);
                setStatus(card, '');
                show();
            })
            .catch(function () { setStatus(card, 'Không mở được, thử lại', 'error'); });
    }

    function closeCard(card) {
        var form = card.querySelector('[data-autosave]');
        if (form && timers.get(form)) { clearTimeout(timers.get(form)); save(form); }
        card.querySelector('[data-qcard-body]').hidden = true;
        card.querySelector('[data-qcard-tools]').hidden = true;
        card.querySelector('[data-qcard-toggle]').setAttribute('aria-expanded', 'false');
        card.classList.remove('is-open');
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-qcard-toggle]');
        if (toggle) {
            var card = toggle.closest('[data-qcard]');
            if (card.classList.contains('is-open')) closeCard(card); else openCard(card);
            return;
        }

        var close = event.target.closest('[data-qcard-close]');
        if (close) { closeCard(close.closest('[data-qcard]')); return; }

        var action = event.target.closest('[data-qcard-action]');
        if (!action) return;
        var question = action.getAttribute('data-confirm');
        if (question && !window.confirm(question)) return;
        var owner = action.closest('[data-qcard]');
        var method = action.getAttribute('data-method') || 'POST';
        request(action.getAttribute('data-qcard-action'), method).then(function (result) {
            if (!result.ok) { setStatus(owner, result.json.message || 'Không làm được, thử lại', 'error'); return; }
            if (method === 'DELETE') {
                owner.remove();
            } else if (result.json.card) {
                owner.insertAdjacentHTML('afterend', result.json.card);
                openCard(owner.nextElementSibling);
            }
        });
    });

    // Tự lưu khi sửa trong thẻ (gõ xong khoảng 1 giây thì lưu).
    ['input', 'change'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            var form = event.target.closest && event.target.closest('form[data-autosave]');
            if (form) queueSave(form);
        });
    });
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.matches('form[data-autosave]')) {
            event.preventDefault();
            clearTimeout(timers.get(form));
            save(form);
        }
        if (form.matches('form[data-quick-add]')) {
            event.preventDefault();
            var error = form.querySelector('[data-quick-add-error]');
            request(form.action, 'POST', new FormData(form)).then(function (result) {
                if (!result.ok) {
                    var errors = result.json.errors || {};
                    var first = Object.keys(errors).map(function (key) { return errors[key][0]; })[0];
                    error.textContent = first || result.json.message || 'Chưa thêm được, vui lòng thử lại.';
                    error.hidden = false;
                    return;
                }
                error.hidden = true;
                var list = form.parentElement.querySelector('[data-qcards]');
                list.insertAdjacentHTML('beforeend', result.json.card);
                form.querySelector('[name="text"]').value = '';
                var card = list.lastElementChild;
                setStatus(card, 'Đã thêm ✓', 'ok');
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                form.querySelector('[name="text"]').focus();
            });
        }
    });
})();
