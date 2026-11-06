@extends('layouts.public')

@section('title', setting('result_title', 'Kết quả trắc nghiệm').' – '.setting('site_name'))
@section('noindex', true)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')
    @php
        $hotline = setting('hotline');
        $zalo = setting('zalo');
    @endphp

    <div class="result">
        {{-- Mở đầu: lời chào + mã Holland --}}
        <section class="result-hero">
            <div class="container">
                <p class="eyebrow">{{ ed('result_title', 'Kết quả trắc nghiệm của bạn', 'Dòng chữ nhỏ') }}</p>
                <h1 class="result-hero__title">
                    {{ ed('result_greeting', 'Chào', 'Lời chào') }}{{ $result['name'] ? ' '.$result['name'] : '' }},
                </h1>
                <p class="result-hero__intro">{{ ed('result_intro', null, 'Đoạn mở đầu') }}</p>

                <div class="code">
                    <p class="code__label">{{ ed('result_code_label', 'Mã Holland của bạn', 'Chữ trên mã Holland') }}</p>
                    <p class="code__value">{{ $result['code'] }}</p>
                    <ul class="code__letters">
                        @foreach ($result['codeLetters'] as $letter)
                            <li class="code__letter">
                                <span class="code__char">{{ $letter['letter'] }}</span>
                                <div>
                                    <p class="code__name">{{ ed('group_name_'.$letter['letter'], $letter['letter'], 'Tên nhóm') }}</p>
                                    <p class="code__desc">{{ ed('group_desc_'.$letter['letter'], null, 'Mô tả nhóm') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- Biểu đồ 6 nhóm --}}
        <section class="section">
            <div class="container">
                <h2 class="section__title">{{ ed('result_chart_title', 'Mức độ hứng thú theo 6 nhóm', 'Tiêu đề biểu đồ') }}</h2>

                @if (\App\Support\PageEditor::active())
                    {{-- Chế độ sửa: hiện cả hai câu nhắc (bình thường chỉ hiện khi điểm các nhóm khá đều / bằng nhau) --}}
                    <p class="note"><span class="ed-hint">Hiện khi điểm các nhóm khá đều:</span> {{ ed('result_flat_note', null, 'Câu nhắc') }}</p>
                    <p class="note"><span class="ed-hint">Hiện khi có nhóm bằng điểm:</span> {{ ed('result_tie_note', null, 'Câu nhắc') }}</p>
                @elseif ($result['isFlat'] && filled(setting('result_flat_note')))
                    <p class="note">{{ setting('result_flat_note') }}</p>
                @elseif ($result['hasTie'] && filled(setting('result_tie_note')))
                    <p class="note">{{ setting('result_tie_note') }}</p>
                @endif

                <ul class="chart">
                    @foreach ($result['groups'] as $group)
                        <li @class(['chart__row', 'is-top' => $group['in_code']])>
                            <span class="chart__name"><strong>{{ $group['letter'] }}</strong> {{ $group['name'] }}</span>
                            <span class="chart__track" role="img" aria-label="{{ $group['name'] }}: {{ $group['percent'] }}%">
                                <span class="chart__bar" style="width: {{ max(2, $group['percent']) }}%"></span>
                            </span>
                            <span class="chart__value">{{ $group['percent'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Lĩnh vực + ngành gợi ý (không hiện điểm FIT, không hứa đậu) --}}
        <section class="section section--soft">
            <div class="container">
                @if ($result['fields'])
                    <h2 class="section__title">{{ ed('result_fields_title', 'Lĩnh vực phù hợp', 'Tiêu đề lĩnh vực') }}</h2>
                    <ol class="fields">
                        @foreach ($result['fields'] as $field)
                            <li class="fields__item"><span class="fields__num">{{ $loop->iteration }}</span> {{ $field['field'] }}</li>
                        @endforeach
                    </ol>
                @endif

                <div class="majors-grid">
                    @foreach ([['key' => 'university', 'title' => ed('result_university_title', 'Ngành đại học gợi ý', 'Tiêu đề ngành đại học')], ['key' => 'vocational', 'title' => ed('result_vocational_title', 'Nghề gợi ý', 'Tiêu đề học nghề')]] as $block)
                        @if (! empty($result[$block['key']]))
                            <div>
                                <h3 class="majors__title">{{ $block['title'] }}</h3>
                                <ul class="majors">
                                    @foreach ($result[$block['key']] as $major)
                                        <li class="major">
                                            <div class="major__head">
                                                <p class="major__name">{{ $major['name'] }}</p>
                                                <span class="major__label">{{ $major['label'] }}</span>
                                            </div>
                                            @if (filled($major['name_de']))
                                                <p class="major__de">{{ $major['name_de'] }}</p>
                                            @endif
                                            @if (filled($major['description']))
                                                <p class="major__desc">{{ $major['description'] }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Tư vấn viên liên hệ + Gọi / Zalo --}}
        <section class="section">
            <div class="container">
                <div class="contact-box">
                    <h2 class="contact-box__title">{{ ed('result_contact_title', null, 'Tiêu đề khối liên hệ') }}</h2>
                    <div class="contact-box__text rich">{{ ed_rich('result_contact_text', 'Nội dung khối liên hệ') }}</div>
                    <div class="contact-box__actions">
                        @if (filled($hotline))
                            <a class="btn btn--primary" href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}">{{ ed('result_call_text', 'Gọi tư vấn', 'Chữ nút gọi') }} · {{ $hotline }}</a>
                        @endif
                        @if (filled($zalo))
                            <a class="btn" href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" target="_blank" rel="noopener">{{ ed('result_zalo_text', 'Nhắn Zalo', 'Chữ nút Zalo') }}</a>
                        @endif
                        @if (filled(setting('main_site_url')))
                            <a class="btn btn--ghost" href="{{ setting('main_site_url') }}">{{ setting('main_site_link_text', 'Về trang chủ') }}</a>
                        @endif
                    </div>
                </div>

                <p class="disclaimer">{{ ed('result_disclaimer', null, 'Lưu ý cuối trang') }}</p>
                <p class="retake"><a href="{{ route('test.show', ['bo' => $attempt->questionSet?->code]) }}">{{ ed('result_retake', 'Làm lại bài trắc nghiệm', 'Chữ link làm lại') }}</a></p>
            </div>
        </section>
    </div>
@endsection

@if (session('just_submitted'))
    @push('scripts')
        <script>
            // Nộp bài thành công: xóa bài làm lưu tạm trên máy.
            try { Object.keys(localStorage).filter(function (k) { return k.indexOf('holland:') === 0; }).forEach(function (k) { localStorage.removeItem(k); }); } catch (e) {}
        </script>
    @endpush
@endif
