{{-- Trình soạn thảo đơn giản (giống web chính: CKEditor 5 bản 41.4.2 qua CDN). HTML được lọc an toàn khi lưu. --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    document.querySelectorAll('textarea[data-rich]').forEach(function (textarea) {
        if (typeof ClassicEditor === 'undefined') return;
        ClassicEditor.create(textarea, {
            toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
            language: 'vi',
            link: { addTargetToExternalLinks: true }
        }).catch(function () { /* Không tải được trình soạn thảo: vẫn gõ chữ thường trong ô được. */ });
    });
</script>
