<div class="row-item" data-row>
    <input name="items[{{ $index }}][title]" value="{{ $item['title'] ?? '' }}" maxlength="150" placeholder="Tên bước, ví dụ: Trả lời các câu hỏi" aria-label="Tên bước">
    <textarea name="items[{{ $index }}][text]" rows="2" maxlength="500" placeholder="Giải thích ngắn" aria-label="Giải thích">{{ $item['text'] ?? '' }}</textarea>
    <button class="link-danger" type="button" data-remove-row>Bỏ dòng</button>
</div>
