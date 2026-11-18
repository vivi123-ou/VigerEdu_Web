<div class="row-item" data-row>
    <input name="items[{{ $index }}][question]" value="{{ $item['question'] ?? '' }}" maxlength="300" placeholder="Câu hỏi" aria-label="Câu hỏi">
    <textarea name="items[{{ $index }}][answer]" rows="3" maxlength="5000" placeholder="Câu trả lời (để trống một dòng giữa các đoạn)" aria-label="Câu trả lời">{{ trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>'], ["\n\n", "\n"], $item['answer'] ?? '')))) }}</textarea>
    <button class="link-danger" type="button" data-remove-row>Bỏ dòng</button>
</div>
