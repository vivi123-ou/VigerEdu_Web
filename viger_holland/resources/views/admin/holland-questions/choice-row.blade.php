{{-- Một dòng lựa chọn của câu chọn 1 / chọn nhiều. Biến: $index, $choice, $groups --}}
<tr>
    <td><input name="choices[{{ $index }}][label]" value="{{ $choice['label'] ?? '' }}" maxlength="200" aria-label="Lựa chọn {{ is_int($index) ? $index + 1 : '' }}" placeholder="Ví dụ: Sửa xe đạp"></td>
    <td>
        <select name="choices[{{ $index }}][group]" aria-label="Nhóm của lựa chọn">
            <option value="">Không tính điểm</option>
            @foreach ($groups as $letter => $name)
                <option value="{{ $letter }}" @selected(($choice['group'] ?? null) === $letter)>{{ $name }} ({{ $letter }})</option>
            @endforeach
        </select>
    </td>
    <td><input name="choices[{{ $index }}][points]" value="{{ $choice['points'] ?? '' }}" inputmode="decimal" class="input--tiny" aria-label="Điểm" placeholder="1"></td>
    <td><button class="link-danger" type="button" data-remove-row aria-label="Bỏ lựa chọn này">Bỏ</button></td>
</tr>
