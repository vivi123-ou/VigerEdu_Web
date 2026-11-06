<ol class="steps__list">
    @foreach ($items as $item)
        <li class="steps__item">
            <span class="steps__number">{{ $loop->iteration }}</span>
            <div>
                <p class="steps__name">{{ $item['title'] }}</p>
                @if (filled($item['text'] ?? null))
                    <p class="steps__text">{{ $item['text'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
@if (filled($data['note'] ?? null))
    <p class="steps__note">{{ $data['note'] }}</p>
@endif
