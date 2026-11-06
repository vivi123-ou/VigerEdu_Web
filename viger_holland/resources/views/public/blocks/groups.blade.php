{{-- Khối "6 nhóm Holland": tên, mô tả nhóm lấy từ mục "Mô tả 6 nhóm Holland". --}}
@php
    $data = $block['data'];
    $headingId = 'khoi-'.$block['id'];
    $english = ['R' => 'Realistic', 'I' => 'Investigative', 'A' => 'Artistic', 'S' => 'Social', 'E' => 'Enterprising', 'C' => 'Conventional'];
@endphp
<section @class(['section', 'section--soft' => $soft ?? false]) aria-labelledby="{{ $headingId }}">
    <div class="container">
        <div class="section__head">
            <h2 id="{{ $headingId }}" class="section__title">{{ $data['title'] ?? '' }}</h2>
            @if (filled($data['intro'] ?? null))
                <p class="section__lead">{{ $data['intro'] }}</p>
            @endif
        </div>

        <ul class="groups">
            @foreach ($english as $letter => $name)
                <li class="group">
                    <span class="group__letter" aria-hidden="true">{{ $letter }}</span>
                    <div>
                        <h3 class="group__name">{{ setting("group_name_{$letter}") }} <span class="group__en">{{ $name }}</span></h3>
                        <p class="group__desc">{{ setting("group_desc_{$letter}") }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
