<div class="row-item" data-row>
    <textarea name="items[{{ $index }}][quote]" rows="3" maxlength="800" placeholder="Lời nhận xét" aria-label="Lời nhận xét">{{ $item['quote'] ?? '' }}</textarea>
    <div class="grid-2">
        <input name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" maxlength="100" placeholder="Tên người nói" aria-label="Tên">
        <input name="items[{{ $index }}][role]" value="{{ $item['role'] ?? '' }}" maxlength="100" placeholder="Ví dụ: Phụ huynh học sinh lớp 11, Hà Nội" aria-label="Vai trò">
    </div>
    <button class="link-danger" type="button" data-remove-row>Bỏ dòng</button>
</div>
