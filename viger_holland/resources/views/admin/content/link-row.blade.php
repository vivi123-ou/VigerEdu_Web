{{-- Một dòng link. Biến: $key, $index, $row --}}
<tr>
    <td><input name="{{ $key }}[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" maxlength="60" aria-label="Chữ hiển thị" placeholder="Ví dụ: Facebook"></td>
    <td><input name="{{ $key }}[{{ $index }}][url]" value="{{ $row['url'] ?? '' }}" maxlength="255" aria-label="Địa chỉ" placeholder="https://"></td>
    <td><input type="checkbox" name="{{ $key }}[{{ $index }}][new_tab]" value="1" @checked($row['new_tab'] ?? false) aria-label="Mở ở tab mới"></td>
    <td><button class="link-danger" type="button" data-remove-row>Bỏ</button></td>
</tr>
