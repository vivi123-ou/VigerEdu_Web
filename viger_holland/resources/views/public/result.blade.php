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
                <p class="eyebrow">{{ setting('result_title', 'Kết quả trắc nghiệm của bạn') }}</p>
                <h1 class="result-hero__title">
                    {{ setting('result_greeting', 'Chào') }}{{ $result['name'] ? ' '.$result['name'] : '' }},
                </h1>
                <p class="result-hero__intro">{{ setting('result_intro') }}</p>

                <div class="code">
                    <p class="code__label">{{ setting('result_code_label', 'Mã Holland của bạn') }}</p>
                    <p class="code__value">{{ $result['code'] }}</p>
                    <ul class="code__letters">
                        @foreach ($result['codeLetters'] as $letter)
                            <li class="code__letter">
                                <span class="code__char">{{ $letter['letter'] }}</span>
                                <div>
                                    <p class="code__name">{{ $letter['name'] }}</p>
                                    <p class="code__desc">{{ $letter['description'] }}</p>
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
                <h2 class="section__title">{{ setting('result_chart_title', 'Mức độ sở thích theo 6 nhóm') }}</h2>

                @if ($result['isFlat'] && filled(setting('result_flat_note')))
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
                    <h2 class="section__title">{{ setting('result_fields_title', 'Lĩnh vực hợp với bạn') }}</h2>
                    <ol class="fields">
                        @foreach ($result['fields'] as $field)
                            <li class="fields__item"><span class="fields__num">{{ $loop->iteration }}</span> {{ $field['field'] }}</li>
                        @endforeach
                    </ol>
                @endif

                <div class="majors-grid">
                    @foreach ([['key' => 'university', 'title' => setting('result_university_title', 'Ngành đại học gợi ý')], ['key' => 'vocational', 'title' => setting('result_vocational_title', 'Nghề gợi ý')]] as $block)
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
                    <h2 class="contact-box__title">{{ setting('result_contact_title') }}</h2>
                    <p class="contact-box__text">{{ setting('result_contact_text') }}</p>
                    <div class="contact-box__actions">
                        @if (filled($hotline))
                            <a class="btn btn--primary" href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}">{{ setting('result_call_text', 'Gọi ngay') }} · {{ $hotline }}</a>
                        @endif
                        @if (filled($zalo))
                            <a class="btn" href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" target="_blank" rel="noopener">{{ setting('result_zalo_text', 'Nhắn Zalo') }}</a>
                        @endif
                        @if (filled(setting('main_site_url')))
                            <a class="btn btn--ghost" href="{{ setting('main_site_url') }}">{{ setting('main_site_link_text', 'Về trang chủ') }}</a>
                        @endif
                    </div>
                </div>

                <p class="disclaimer">{{ setting('result_disclaimer') }}</p>
                <p class="retake"><a href="{{ route('test.show', ['bo' => $attempt->questionSet?->code]) }}">{{ setting('result_retake', 'Làm lại bài trắc nghiệm') }}</a></p>
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
