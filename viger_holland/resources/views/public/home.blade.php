{{--
    Trang bắt đầu (H3) theo mẫu docs/mau_giao_dien/trang-gioi-thieu.html: tiêu đề, lợi ích, nút "Bắt đầu làm bài",
    bên phải là "Báo cáo mẫu" (hoặc ảnh banner nếu admin chọn). Mọi chữ sửa được ở "Trang mở đầu" (sửa trực tiếp hoặc biểu mẫu).
    Các khối nội dung cũ (Cách làm bài, 6 nhóm, Câu hỏi thường gặp) mặc định tắt, admin bật lại được.
--}}
@extends('layouts.public')

@php
    use App\Modules\Assessment\Support\SampleReport;
    use App\Support\IntroBlocks;
    use App\Support\PageEditor;

    $editing = PageEditor::active();
    $blocks = $editing ? IntroBlocks::all() : IntroBlocks::enabled();
    $bannerPath = setting('banner_image');
    $bannerImage = media_url($bannerPath);
    $benefitKeys = $editing ? ['intro_benefit_1', 'intro_benefit_2', 'intro_benefit_3'] : array_keys(array_filter([
        'intro_benefit_1' => setting('intro_benefit_1'), 'intro_benefit_2' => setting('intro_benefit_2'), 'intro_benefit_3' => setting('intro_benefit_3'),
    ]));
    $sample = SampleReport::make(setting('intro_sample_code'));
    $utm = array_filter(request()->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']));
    // Chữ có {so_cau} tự hiện số câu của đề theo cài đặt (khi sửa trang thì giữ nguyên {so_cau} để admin thấy).
    $fill = fn ($html) => $editing ? $html : new \Illuminate\Support\HtmlString(str_replace('{so_cau}', (string) ((int) \App\Modules\Assessment\Support\TestConfig::get('items_per_group') * 6), (string) $html));
@endphp

@section('content')
    <section class="hero">
        <div class="wrap">
            <div>
                <span class="kicker">{{ ed('intro_eyebrow', setting('test_name'), 'Dòng chữ nhỏ trên tiêu đề') }}</span>
                <h1 class="serif">{{ ed('intro_title', null, 'Tiêu đề lớn') }}
                    @if ($editing || filled(setting('intro_title_accent')))
                        <em>{{ ed('intro_title_accent', null, 'Phần cuối tiêu đề (chữ nghiêng đỏ)') }}</em>
                    @endif
                </h1>
                @if ($editing || filled(setting('intro_subtitle')))
                    <p class="lead">{{ $fill(ed('intro_subtitle', null, 'Đoạn giới thiệu')) }}</p>
                @endif
                @if ($benefitKeys)
                    <ul class="vals">
                        @foreach ($benefitKeys as $key)
                            <li>{{ $fill(ed($key, null, 'Lợi ích (để trống thì ẩn)')) }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="post" action="{{ route('test.start') }}">
                    @csrf
                    @foreach ($utm as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <button class="kbtn kbtn--red kbtn--lg" type="submit">
                        {{ ed('intro_button_text', 'Bắt đầu làm bài', 'Chữ trên nút') }} <svg class="ic"><use href="#i-arrow-right"/></svg>
                    </button>
                </form>
                @if ($editing || filled(setting('intro_button_note')))
                    <p class="hero__note">{{ ed('intro_button_note', null, 'Dòng chữ nhỏ dưới nút') }}</p>
                @endif
            </div>

            @if ($bannerImage)
                <img class="hero__img" src="{{ $bannerImage }}" alt="" {{ PageEditor::imageAttributes('banner_image', $bannerPath) }}>
            @else
                <div class="sample" aria-label="{{ setting('intro_sample_label') }}">
                    <div class="sample__bar">{{ ed('intro_sample_label', null, 'Dòng chữ trên khung') }}</div>
                    <div class="sample__body">
                        <div class="sample__top">
                            <div>
                                <p class="sample__k">Mã Holland</p>
                                <div class="code">{{ $sample['code'][0] }}<span>{{ $sample['code'][1] }}</span></div>
                                <p class="sample__name">{{ implode(' – ', $sample['names']) }}</p>
                            </div>
                            <svg class="hexa" viewBox="-20 0 340 300" width="150" aria-hidden="true">
                                <polygon points="{{ SampleReport::outline() }}" fill="#fff" stroke="#cfd6de"/>
                                <polygon points="{{ $sample['polygon'] }}" fill="rgba(18,38,63,.08)" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                                @foreach ($sample['dots'] as [$x, $y])
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="7" fill="var(--red)"/>
                                @endforeach
                                @foreach ($sample['labels'] as [$letter, $x, $y, $anchor])
                                    <text x="{{ $x }}" y="{{ $y }}" text-anchor="{{ $anchor }}">{{ $letter }}</text>
                                @endforeach
                            </svg>
                        </div>
                        @if ($editing || filled(setting('intro_sample_text')))
                            <p class="sample__text">{{ ed('intro_sample_text', null, 'Câu giải thích mẫu') }}</p>
                        @endif
                        @if ($sample['majors'])
                            <p class="sample__k" style="margin-top:18px">{{ ed('intro_sample_majors_heading', null, 'Tiêu đề phần ngành') }}</p>
                            <ul>
                                @foreach ($sample['majors'] as $major)
                                    <li><span>{{ $major['name_vi'] }}<small>{{ $major['name_de'] }}</small></span><span class="lv">{{ setting('result_pair_label', 'Gợi ý chính') }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($editing && ! $bannerImage)
        <div class="wrap" style="margin-top:16px">
            <div class="ed-image-empty" {{ PageEditor::imageAttributes('banner_image', '') }}>+ Bấm để dùng ảnh banner thay cho "Báo cáo mẫu"</div>
        </div>
    @endif

    @foreach ($blocks as $block)
        @if ($editing)
            @include('public.blocks.edit-bar', ['block' => $block, 'first' => $loop->first, 'last' => $loop->last])
        @endif
        <div @class(['ed-block', 'is-off' => ! $block['enabled']])>
            @include('public.blocks.'.$block['type'], ['block' => $block, 'inHero' => false, 'soft' => $loop->odd])
        </div>
    @endforeach
@endsection
