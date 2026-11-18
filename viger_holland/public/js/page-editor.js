// "Sửa trực tiếp trên trang": bấm vào chữ để gõ, bấm vào ảnh để đổi, bấm "Lưu thay đổi".
// Chỉ chạy khi admin mở trang ở chế độ sửa (body[data-page-editor]).
(function () {
    'use strict';

    if (!document.body.hasAttribute('data-page-editor')) return;

    var bar = document.querySelector('[data-pe-bar]');
    var saveButton = bar.querySelector('[data-pe-save]');
    var resetButton = bar.querySelector('[data-pe-reset]');
    var status = bar.querySelector('[data-pe-status]');
    var formatBar = bar.querySelector('[data-pe-format]');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var original = new Map();
    var imageChanges = {};

    function valueOf(el) {
        return el.getAttribute('data-ed-type') === 'rich' ? el.innerHTML.trim() : el.innerText.replace(/ /g, ' ').trim();
    }

    document.querySelectorAll('[data-ed]').forEach(function (el) { original.set(el, valueOf(el)); });

    function changes() {
        var result = {};
        original.forEach(function (start, el) {
            var now = valueOf(el);
            if (now !== start) result[el.getAttribute('data-ed')] = now;
        });
        Object.keys(imageChanges).forEach(function (key) { result[key] = imageChanges[key]; });
        return result;
    }

    function refresh() {
        var count = Object.keys(changes()).length;
        saveButton.disabled = !count;
        resetButton.disabled = !count;
        saveButton.textContent = count ? 'Lưu thay đổi (' + count + ')' : 'Lưu thay đổi';
        if (count) setStatus('Có ' + count + ' thay đổi chưa lưu.');
        original.forEach(function (start, el) { el.classList.toggle('is-changed', valueOf(el) !== start); });
    }

    function setStatus(text, kind) {
        status.textContent = text;
        status.className = 'pe-bar__status' + (kind ? ' is-' + kind : '');
    }

    document.addEventListener('input', function (event) {
        if (event.target.closest && event.target.closest('[data-ed]')) refresh();
    });

    // Không cho Enter xuống dòng ở chữ một dòng (tiêu đề, nút…).
    document.addEventListener('keydown', function (event) {
        var el = event.target.closest && event.target.closest('[data-ed]');
        if (!el) return;
        event.stopPropagation();
        var multiline = el.getAttribute('data-ed-type') === 'rich' || /signature|address|desc|intro|subtitle|text|note|answer|quote/.test(el.getAttribute('data-ed'));
        if (event.key === 'Enter' && !multiline) { event.preventDefault(); el.blur(); }
    }, true);

    // Đang sửa: bấm link / nút không chuyển trang, bấm câu hỏi thường gặp không đóng mở.
    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-pe-bar], [data-media-dialog], .ed-bar')) return;
        var link = event.target.closest('a, summary, button[type=submit]');
        if (link) event.preventDefault();
    }, true);
    document.querySelectorAll('form').forEach(function (form) {
        if (!form.closest('[data-pe-bar], [data-media-dialog]')) form.addEventListener('submit', function (event) { event.preventDefault(); });
    });

    // Thanh định dạng cho chữ có định dạng (đậm, nghiêng, link, danh sách).
    var activeRich = null;
    document.addEventListener('focusin', function (event) {
        var rich = event.target.closest && event.target.closest('[data-ed-type="rich"]');
        if (rich) { activeRich = rich; formatBar.hidden = false; }
    });
    document.addEventListener('focusout', function () {
        setTimeout(function () {
            if (!document.activeElement || !document.activeElement.closest('[data-ed-type="rich"], [data-pe-format]')) formatBar.hidden = true;
        }, 150);
    });
    formatBar.addEventListener('mousedown', function (event) { event.preventDefault(); });
    formatBar.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cmd]');
        if (!button || !activeRich) return;
        var command = button.getAttribute('data-cmd');
        if (command === 'link') {
            var url = window.prompt('Địa chỉ link (ví dụ: viger.edu.vn/du-hoc-duc):', 'https://');
            if (!url || url === 'https://') return;
            if (!/^(https?:\/\/|mailto:|tel:|\/)/i.test(url)) url = 'https://' + url;
            document.execCommand('createLink', false, url);
        } else {
            document.execCommand(command, false, null);
        }
        refresh();
    });

    // Lưu / bỏ thay đổi.
    saveButton.addEventListener('click', function () {
        var data = changes();
        if (!Object.keys(data).length) return;
        saveButton.disabled = true;
        setStatus('Đang lưu…');
        fetch(bar.getAttribute('data-save-url'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ changes: data })
        }).then(function (response) {
            return response.json().then(function (json) { return { ok: response.ok, json: json }; });
        }).then(function (result) {
            if (!result.ok) {
                var errors = result.json.errors || {};
                var first = Object.keys(errors).map(function (key) { return errors[key][0]; })[0];
                setStatus('Chưa lưu: ' + (first || result.json.message || 'vui lòng thử lại.'), 'error');
                saveButton.disabled = false;
                return;
            }
            original.forEach(function (start, el) { original.set(el, valueOf(el)); });
            imageChanges = {};
            refresh();
            setStatus(result.json.message, 'ok');
        }).catch(function () {
            setStatus('Mất kết nối, chưa lưu. Vui lòng thử lại.', 'error');
            saveButton.disabled = false;
        });
    });

    resetButton.addEventListener('click', function () {
        if (!window.confirm('Bỏ mọi thay đổi chưa lưu?')) return;
        window.location.reload();
    });

    window.addEventListener('beforeunload', function (event) {
        if (Object.keys(changes()).length) { event.preventDefault(); event.returnValue = ''; }
    });

    // Bấm ảnh để đổi ảnh (thư viện ảnh).
    var dialog = document.querySelector('[data-media-dialog]');
    var activeImage = null;

    function pick(item) {
        if (!activeImage) return;
        var key = activeImage.getAttribute('data-ed-image');
        if (activeImage.tagName !== 'IMG') {
            var img = document.createElement('img');
            img.className = 'ed-image-new';
            Array.prototype.forEach.call(activeImage.attributes, function (attr) {
                if (attr.name.indexOf('data-ed') === 0 || attr.name === 'tabindex' || attr.name === 'title' || attr.name === 'role') img.setAttribute(attr.name, attr.value);
            });
            activeImage.replaceWith(img);
            activeImage = img;
        }
        activeImage.src = item.url;
        activeImage.classList.add('is-changed');
        imageChanges[key] = item.path;
        dialog.close();
        refresh();
    }

    function loadMedia(url, replace) {
        var list = dialog.querySelector('[data-media-list]');
        var note = dialog.querySelector('[data-media-status]');
        note.textContent = 'Đang tải ảnh…';
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                note.textContent = (replace && !data.items.length) ? 'Thư viện chưa có ảnh. Bấm "Tải ảnh mới lên".' : '';
                if (replace) list.innerHTML = '';
                data.items.forEach(function (item) {
                    var li = document.createElement('li');
                    li.className = 'media-grid__item';
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'media-grid__pick';
                    var img = document.createElement('img');
                    img.src = item.url;
                    img.alt = item.name;
                    button.appendChild(img);
                    button.addEventListener('click', function () { pick(item); });
                    li.appendChild(button);
                    list.appendChild(li);
                });
                var more = dialog.querySelector('[data-media-more]');
                more.hidden = !data.next;
                more.onclick = function () { loadMedia(data.next, false); };
            })
            .catch(function () { note.textContent = 'Không tải được thư viện ảnh.'; });
    }

    document.addEventListener('click', function (event) {
        var image = event.target.closest('[data-ed-image]');
        if (!image || !dialog) return;
        event.preventDefault();
        activeImage = image;
        loadMedia(dialog.getAttribute('data-list-url'), true);
        dialog.showModal();
    });

    if (dialog) {
        dialog.querySelector('[data-media-upload]').addEventListener('change', function (event) {
            var file = event.target.files[0];
            if (!file) return;
            var body = new FormData();
            body.append('files[]', file);
            dialog.querySelector('[data-media-status]').textContent = 'Đang tải ảnh lên…';
            fetch(dialog.getAttribute('data-upload-url'), { method: 'POST', body: body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } })
                .then(function (response) { return response.json().then(function (json) { return { ok: response.ok, json: json }; }); })
                .then(function (result) {
                    event.target.value = '';
                    if (!result.ok) { dialog.querySelector('[data-media-status]').textContent = result.json.message || 'Không tải được ảnh.'; return; }
                    pick(result.json.items[0]);
                });
        });
    }

    // Khối trang giới thiệu: lên / xuống / ẩn – hỏi trước nếu còn thay đổi chưa lưu.
    function confirmLeave() {
        return !Object.keys(changes()).length || window.confirm('Bạn còn thay đổi chưa lưu. Bấm "Hủy" rồi "Lưu thay đổi" trước, hoặc "OK" để bỏ các thay đổi đó.');
    }

    document.addEventListener('click', function (event) {
        var move = event.target.closest('[data-ed-move]');
        var toggle = event.target.closest('[data-ed-toggle]');
        if (!move && !toggle) return;
        event.preventDefault();
        if (!confirmLeave()) return;
        window.onbeforeunload = null;
        original.forEach(function (start, el) { original.set(el, valueOf(el)); });
        imageChanges = {};

        if (toggle) {
            var data = new FormData();
            data.append('_method', 'PATCH');
            data.append('_token', csrf);
            fetch(toggle.getAttribute('data-ed-toggle'), { method: 'POST', body: data, credentials: 'same-origin' }).then(function () { window.location.reload(); });
            return;
        }

        var ids = Array.prototype.map.call(document.querySelectorAll('[data-ed-block]'), function (el) { return el.getAttribute('data-ed-block'); });
        var id = move.closest('[data-ed-block]').getAttribute('data-ed-block');
        var index = ids.indexOf(id);
        var target = move.getAttribute('data-ed-move') === 'up' ? index - 1 : index + 1;
        if (target < 0 || target >= ids.length) return;
        ids.splice(index, 1);
        ids.splice(target, 0, id);
        var body = new FormData();
        body.append('_token', csrf);
        ids.forEach(function (value) { body.append('order[]', value); });
        fetch(bar.getAttribute('data-reorder-url'), { method: 'POST', body: body, credentials: 'same-origin' }).then(function () { window.location.reload(); });
    });

})();
