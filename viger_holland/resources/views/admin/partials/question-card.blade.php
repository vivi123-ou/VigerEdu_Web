{{-- Thẻ câu hỏi (danh sách kiểu Google Form). Bấm vào thẻ để mở form sửa (tải bằng JS, tự lưu khi sửa).
     Biến: $question, $meta (dòng mô tả ngắn), $formUrl, $duplicateUrl (null = không có), $canDelete --}}
<article @class(['qcard', 'is-off' => ! $question->is_active]) data-id="{{ $question->id }}" data-qcard data-form-url="{{ $formUrl }}">
    <div class="qcard__head">
        <span class="drag" draggable="true" title="Kéo để đổi thứ tự" aria-label="Kéo để đổi thứ tự">⋮⋮</span>
        <button class="qcard__summary" type="button" data-qcard-toggle aria-expanded="false">
            <span class="qcard__num" aria-hidden="true"></span>
            <span class="qcard__text" data-qcard-text>{{ $question->text }}</span>
            <span class="qcard__meta" data-qcard-meta>{{ $meta }}</span>
        </button>
        <span class="qcard__status" data-qcard-status role="status"></span>
    </div>
    <div class="qcard__body" data-qcard-body hidden></div>
    <div class="qcard__tools" data-qcard-tools hidden>
        <button class="btn btn--small btn--primary" type="button" data-qcard-close>Xong</button>
        @if ($duplicateUrl)
            <button class="btn btn--small" type="button" data-qcard-action="{{ $duplicateUrl }}" data-method="POST">Nhân đôi</button>
        @endif
        @if ($canDelete)
            <button class="btn btn--small btn--plain link-danger" type="button" data-qcard-action="{{ route('admin.questions.destroy', $question) }}" data-method="DELETE"
                    data-confirm="Đưa câu này vào thùng rác? Khôi phục được ở cuối trang.">Xóa</button>
        @endif
        <span class="muted qcard__hint">Sửa đến đâu tự lưu đến đó.</span>
    </div>
</article>
