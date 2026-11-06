{{-- Khối "Cảm nhận phụ huynh / học sinh". --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $items = array_values(array_filter($data['items'] ?? [], fn ($item) => filled($item['quote'] ?? null)));
@endphp
@if ($items)
    <section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
        <div class="container">
            <h2 id="{{ $headingId }}" class="section__title">{{ $data['title'] ?? '' }}</h2>
            <ul class="quotes">
                @foreach ($items as $item)
                    <li class="quote">
                        <blockquote class="quote__text">{{ $item['quote'] }}</blockquote>
                        <p class="quote__author">
                            <strong>{{ $item['name'] ?? '' }}</strong>
                            @if (filled($item['role'] ?? null))<span>{{ $item['role'] }}</span>@endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
