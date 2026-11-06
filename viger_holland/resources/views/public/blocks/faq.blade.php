{{-- Khối "Câu hỏi thường gặp": bấm câu hỏi để mở câu trả lời. --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => filled($item['question'] ?? null)));
@endphp
<section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
    <div class="container">
        <div class="faq-wrap">
        <h2 id="{{ $headingId }}" class="section__title">{{ $data['title'] ?? '' }}</h2>
        <div class="faq">
            @foreach ($items as $item)
                <details class="faq__item">
                    <summary class="faq__question">{{ $item['question'] }}</summary>
                    <div class="faq__answer rich">{!! rich_text($item['answer'] ?? '') !!}</div>
                </details>
            @endforeach
        </div>
    </div>
    </div>
</section>
