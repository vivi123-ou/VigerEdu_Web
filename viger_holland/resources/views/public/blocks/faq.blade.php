{{-- Khối "Câu hỏi thường gặp": bấm câu hỏi để mở câu trả lời. --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $items = array_filter($data['items'] ?? [], fn ($item) => filled($item['question'] ?? null));
@endphp
<section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
    <div class="container">
        <div class="faq-wrap">
        <h2 id="{{ $headingId }}" class="section__title">{{ ed_block($block, 'title', 'Tiêu đề khối') }}</h2>
        <div class="faq">
            @foreach ($items as $index => $item)
                <details class="faq__item" @if (\App\Support\PageEditor::active()) open @endif>
                    <summary class="faq__question">{{ ed_block($block, "items.{$index}.question", 'Câu hỏi') }}</summary>
                    <div class="faq__answer rich">{{ ed_block($block, "items.{$index}.answer", 'Câu trả lời', rich: true) }}</div>
                </details>
            @endforeach
        </div>
    </div>
    </div>
</section>
