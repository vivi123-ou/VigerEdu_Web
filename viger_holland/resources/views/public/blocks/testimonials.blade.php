{{-- Khối "Cảm nhận phụ huynh / học sinh". Không có lời cảm nhận nào thì tự ẩn (trừ khi admin đang sửa trang). --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $items = array_filter($data['items'] ?? [], fn ($item) => filled($item['quote'] ?? null));
@endphp
@if ($items || \App\Support\PageEditor::active())
    <section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
        <div class="container">
            <h2 id="{{ $headingId }}" class="section__title">{{ ed_block($block, 'title', 'Tiêu đề khối') }}</h2>
            <ul class="quotes">
                @foreach ($items as $index => $item)
                    <li class="quote">
                        <blockquote class="quote__text">{{ ed_block($block, "items.{$index}.quote", 'Lời nhận xét') }}</blockquote>
                        <p class="quote__author">
                            <strong>{{ ed_block($block, "items.{$index}.name", 'Tên người nói') }}</strong>
                            @if (\App\Support\PageEditor::active() || filled($item['role'] ?? null))<span>{{ ed_block($block, "items.{$index}.role", 'Vai trò') }}</span>@endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
