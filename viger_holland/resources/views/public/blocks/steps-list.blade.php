<ol class="steps__list">
    @foreach ($items as $index => $item)
        <li class="steps__item">
            <span class="steps__number">{{ $loop->iteration }}</span>
            <div>
                <p class="steps__name">{{ ed_block($block, "items.{$index}.title", 'Tên bước') }}</p>
                @if (\App\Support\PageEditor::active() || filled($item['text'] ?? null))
                    <p class="steps__text">{{ ed_block($block, "items.{$index}.text", 'Giải thích ngắn') }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
@if (\App\Support\PageEditor::active() || filled($data['note'] ?? null))
    <p class="steps__note">{{ ed_block($block, 'note', 'Dòng ghi chú') }}</p>
@endif
