{{-- Khối "Chữ và ảnh". --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $image = media_url($data['image'] ?? null);
@endphp
<section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
    <div @class(['container', 'text-image', 'text-image--left' => ($data['image_side'] ?? 'right') === 'left', 'text-image--no-image' => ! $image])>
        <div class="text-image__body">
            @if (filled($data['title'] ?? null))
                <h2 id="{{ $headingId }}" class="section__title">{{ $data['title'] }}</h2>
            @endif
            <div class="rich">{!! rich_text($data['body'] ?? '') !!}</div>
            @if (filled($data['button_text'] ?? null) && filled($data['button_url'] ?? null))
                <a class="btn btn--ghost" href="{{ $data['button_url'] }}">{{ $data['button_text'] }}</a>
            @endif
        </div>
        @if ($image)
            <img class="text-image__img" src="{{ $image }}" alt="{{ $data['image_alt'] ?? '' }}" loading="lazy">
        @endif
    </div>
</section>
