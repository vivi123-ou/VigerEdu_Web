{{--
    Báo cáo đầy đủ của khách (H4, bản External) theo mẫu luong-khach.html (màn hình 4), đầu trang chỉ có logo.
    01 Nhóm sở thích (giải thích 2 nhóm, chữ thứ 3 khi đủ điều kiện, khi hai nhóm kết hợp, lục giác)
    02 Mức độ quan tâm 6 nhóm (nhãn chữ, không hiện điểm số)  03 Ngành học tại Đức (nhãn chữ, không %)  04 Bước tiếp theo.
    Chỉ hiện một lần (ReportController::show), không họ tên, không PDF, không nút chia sẻ, không link qua email.
--}}
@extends('layouts.public')

@section('title', setting('report_kicker'))
@section('noindex', true)

@php
    $zalo = (string) setting('zalo');
    $zaloUrl = $zalo === '' ? null : (str_starts_with($zalo, 'http') ? $zalo : 'https://zalo.me/'.preg_replace('/\D/', '', $zalo));
    $topics = array_filter([setting('report_next_topic_1'), setting('report_next_topic_2'), setting('report_next_topic_3')]);
    $sec = 0;
@endphp

@section('content')
    <div class="ksec">
        <article class="rep">
            <header class="doc-head">
                <span class="kicker">{{ setting('report_kicker', 'Báo cáo sở thích nghề nghiệp') }}</span>
                <h1 class="serif">{{ implode(' – ', $report['names']) }}</h1>
                @if (filled($report['opening']))
                    <p class="opening">{{ $report['opening'] }}</p>
                @endif
            </header>

            <section class="sec">
                <h2><span>{{ sprintf('%02d', ++$sec) }}</span>{{ setting('report_sec1', 'Nhóm sở thích của bạn') }}</h2>
                <div class="cols">
                    <div>
                        <div class="code-line"><span class="c" aria-label="Mã Holland {{ $report['code'] }}">{{ $report['code'] }}</span></div>
                        <ul class="qa">
                            @foreach ($report['explain'] as $item)
                                <li><p class="q2">{{ $item['heading'] }}</p>@if (filled($item['text']))<p>{{ $item['text'] }}</p>@endif</li>
                            @endforeach
                            @if ($report['third'])
                                <li><p class="q2">{{ $report['third']['heading'] }}</p>@if (filled($report['third']['text']))<p>{{ $report['third']['text'] }}</p>@endif</li>
                            @endif
                        </ul>
                    </div>
                    <figure class="fig">
                        @php($hex = $report['hexagon'])
                        <svg class="hex" viewBox="-75 0 450 300" width="100%" role="img" aria-label="Hình lục giác 6 nhóm sở thích">
                            <polygon points="{{ $hex['outline'] }}" fill="#fff" stroke="#cfd6de"/>
                            <polygon points="{{ $hex['mid'] }}" fill="none" stroke="#e3e7ec" stroke-dasharray="3 4"/>
                            <g stroke="#eef1f4">
                                @foreach ($hex['axes'] as [$x, $y])
                                    <line x1="150" y1="150" x2="{{ $x }}" y2="{{ $y }}"/>
                                @endforeach
                            </g>
                            <polygon points="{{ $hex['polygon'] }}" fill="rgba(18,38,63,.08)" stroke="var(--navy)" stroke-width="1.8" stroke-linejoin="round"/>
                            @foreach ($hex['dots'] as [$x, $y])
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="var(--red)"/>
                            @endforeach
                            @foreach ($hex['labels'] as [$text, $x, $y, $anchor])
                                <text x="{{ $x }}" y="{{ $y }}" text-anchor="{{ $anchor }}">{{ $text }}</text>
                            @endforeach
                        </svg>
                    </figure>
                </div>
                @if (filled($report['combine']))
                    {{-- Trải ngang hết bề rộng, dưới cả phần chữ và hình lục giác (Vân góp ý 18/11). --}}
                    <div class="combine"><p class="q2">{{ setting('report_combine_heading', 'Khi hai nhóm kết hợp') }}</p><p>{{ $report['combine'] }}</p></div>
                @endif
            </section>

            <section class="sec">
                <h2><span>{{ sprintf('%02d', ++$sec) }}</span>{{ setting('report_sec2', 'Mức độ quan tâm theo 6 nhóm') }}</h2>
                <table class="scale">
                    @foreach ($report['scale'] as $row)
                        <tr @class(['top' => $row['top']])>
                            <td class="g"><b>{{ $row['code'] }}</b>{{ $row['name'] }}</td>
                            <td class="b"><div class="track"><i @class(['top' => $row['top']]) style="width:{{ $row['width'] }}%"></i></div></td>
                            <td class="l">{{ $row['band'] }}</td>
                        </tr>
                    @endforeach
                </table>
                @if ($report['ticks'])
                    <div class="ticks" style="grid-template-columns:repeat({{ count($report['ticks']) }},1fr)" aria-hidden="true">
                        @foreach ($report['ticks'] as $tick)
                            <span>{{ $tick }}</span>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($report['majors'])
                <section class="sec">
                    <h2><span>{{ sprintf('%02d', ++$sec) }}</span>{{ setting('report_sec3', 'Ngành học tại Đức phù hợp với bạn') }}</h2>
                    <div class="majors">
                        @foreach ($report['majors'] as $i => $major)
                            <div class="mj">
                                <span class="no">{{ sprintf('%02d', $i + 1) }}</span>
                                <div>
                                    <h3>{{ $major['name_vi'] }}<small>{{ collect([$major['name_de'], $major['routes']])->filter()->implode(' · ') }}</small></h3>
                                    <span class="lv lv{{ $major['level'] }}">{{ $major['label'] }}</span>
                                </div>
                                <div><p class="lab">{{ setting('report_why_label', 'Vì sao được đề xuất') }}</p><p>{{ $major['why'] }}</p></div>
                                <div>
                                    @if ($major['occupations'])
                                        <p class="lab">{{ setting('report_jobs_label', 'Nghề tham khảo') }}</p><p>{{ implode(', ', $major['occupations']) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="sec">
                <h2><span>{{ sprintf('%02d', ++$sec) }}</span>{{ setting('report_sec4', 'Bước tiếp theo') }}</h2>
                <div class="next">
                    <h3>{{ setting('report_next_title') }}</h3>
                    <p class="muted" style="margin:0">{{ setting('report_next_text') }}</p>
                    <div class="grid">
                        <div>
                            @if ($topics)
                                <p class="small" style="font-weight:600;margin:0 0 8px">{{ setting('report_next_topics_heading') }}</p>
                                <ul class="topics">
                                    @foreach ($topics as $topic)
                                        <li><svg class="ic"><use href="#i-check"/></svg>{{ $topic }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="cta">
                            @if ($zaloUrl)
                                <a class="kbtn kbtn--red" href="{{ $zaloUrl }}" target="_blank" rel="noopener">{{ setting('report_zalo_button', 'Nhắn Zalo để trao đổi thêm') }}</a>
                            @endif
                            @if (filled(setting('hotline')))
                                <a class="kbtn" href="tel:{{ preg_replace('/[^\d+]/', '', (string) setting('hotline')) }}"><svg class="ic"><use href="#i-phone"/></svg>{{ setting('hotline') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <div class="tools">
                <form method="post" action="{{ route('test.start') }}">
                    @csrf
                    <button class="linkbtn" type="submit">{{ setting('report_retake', 'Làm lại với câu hỏi mới') }}</button>
                </form>
                @if (filled(setting('report_once_note')))
                    <span>{{ setting('report_once_note') }}</span>
                @endif
            </div>
            <div class="notes">
                @if (filled(setting('report_note')))
                    <p>{{ setting('report_note') }}</p>
                @endif
                @if (filled(setting('report_tip')))
                    <p>{{ setting('report_tip') }}</p>
                @endif
            </div>
        </article>
    </div>
@endsection
