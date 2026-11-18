{{-- Ô mã + nút "Chép". Biến: $code --}}
<span class="copy-link copy-code">
    <textarea readonly rows="{{ substr_count($code, "\n") + 1 }}" aria-label="Đoạn mã" data-copy-source>{{ $code }}</textarea>
    <button class="btn btn--small" type="button" data-copy>Chép</button>
</span>
