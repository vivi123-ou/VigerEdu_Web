{{-- Khối "Cách làm bài". Đặt cạnh banner ($inHero) hoặc thành một phần riêng. --}}
@php
    $data = $block['data'];
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => filled($item['title'] ?? null)));
    $headingId = 'khoi-'.$block['id'];
@endphp

@if ($inHero)
    <aside class="steps" aria-labelledby="{{ $headingId }}">
        <h2 id="{{ $headingId }}" class="steps__title">{{ $data['title'] ?? '' }}</h2>
        @include('public.blocks.steps-list')
    </aside>
@else
    <section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
        <div class="container">
            <div class="steps steps--section">
                <h2 id="{{ $headingId }}" class="section__title">{{ $data['title'] ?? '' }}</h2>
                @include('public.blocks.steps-list')
            </div>
        </div>
    </section>
@endif
