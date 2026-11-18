{{-- Khối "6 nhóm Holland": tên, mô tả nhóm lấy từ mục "Mô tả 6 nhóm Holland". --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $english = ['R' => 'Realistic', 'I' => 'Investigative', 'A' => 'Artistic', 'S' => 'Social', 'E' => 'Enterprising', 'C' => 'Conventional'];
@endphp
<section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
    <div class="container">
        <div class="section__head">
            <h2 id="{{ $headingId }}" class="section__title">{{ ed_block($block, 'title', 'Tiêu đề khối') }}</h2>
            @if (\App\Support\PageEditor::active() || filled($data['intro'] ?? null))
                <p class="section__lead">{{ ed_block($block, 'intro', 'Đoạn giới thiệu') }}</p>
            @endif
        </div>

        <ul class="groups">
            @foreach ($english as $letter => $name)
                <li class="group">
                    <span class="group__letter" aria-hidden="true">{{ $letter }}</span>
                    <div>
                        <h3 class="group__name">{{ ed("group_name_{$letter}", $letter, 'Tên nhóm') }} <span class="group__en">{{ $name }}</span></h3>
                        <p class="group__desc">{{ ed("group_desc_{$letter}", null, 'Mô tả nhóm') }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
