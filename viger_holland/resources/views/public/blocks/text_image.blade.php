{{-- Khối "Chữ và ảnh". --}}
@php
    use App\Support\PageEditor;

    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $image = media_url($data['image'] ?? null);
    $editing = PageEditor::active();
@endphp
<section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
    <div @class(['container', 'text-image', 'text-image--left' => ($data['image_side'] ?? 'right') === 'left', 'text-image--no-image' => ! $image && ! $editing])>
        <div class="text-image__body">
            @if ($editing || filled($data['title'] ?? null))
                <h2 id="{{ $headingId }}" class="section__title">{{ ed_block($block, 'title', 'Tiêu đề khối') }}</h2>
            @endif
            <div class="rich">{{ ed_block($block, 'body', 'Nội dung', rich: true) }}</div>
            @if (filled($data['button_text'] ?? null) && filled($data['button_url'] ?? null))
                <a class="btn btn--ghost" href="{{ $data['button_url'] }}">{{ $data['button_text'] }}</a>
            @endif
        </div>
        @if ($image)
            <img class="text-image__img" src="{{ $image }}" alt="{{ $data['image_alt'] ?? '' }}" loading="lazy" {{ PageEditor::imageAttributes('block:'.$block['id'].':image', $data['image'] ?? '') }}>
        @elseif ($editing)
            <div class="ed-image-empty" {{ PageEditor::imageAttributes('block:'.$block['id'].':image', '') }}>+ Bấm để thêm ảnh</div>
        @endif
    </div>
</section>
