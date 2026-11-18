{{--
    Kết quả sơ lược (H3) theo mẫu docs/mau_giao_dien/luong-khach.html (màn hình 2): mã Holland khách được thấy
    (2 chữ, 3 chữ khi nhóm thứ 3 đủ điểm), tên các nhóm, vài ngành đầu hiện rõ, các ngành sau làm mờ (không lộ tên thật),
    nút "Xem báo cáo đầy đủ" (form thông tin và mã email làm ở H4). Không hiện con số mức khớp, không hiện %.
--}}
@extends('layouts.public')

@section('title', setting('result_kicker', 'Kết quả Holland của bạn'))
@section('noindex', true)

@section('content')
    <div class="ksec">
        <div class="narrow" style="padding:0 20px">
            <div style="text-align:center">
                <span class="kicker">{{ setting('result_kicker', 'Kết quả Holland của bạn') }}</span>
                <div class="big" style="margin:18px 0 10px" aria-label="Mã Holland {{ $code }}">{{ $code[0] }}<span>{{ substr($code, 1) }}</span></div>
                <h1 class="serif" style="font-size:28px;margin:0">{{ implode(' – ', $names) }}</h1>
                @if (filled($interpretation))
                    <p class="interp">{{ $interpretation }}</p>
                @endif
            </div>

            @if ($majors)
                <hr class="rule">
                <h2 style="font-size:16px;font-weight:600;margin:0">{{ setting('result_majors_heading', 'Ngành học tại Đức nên tìm hiểu') }}</h2>
                <ul class="ol">
                    @foreach ($majors as $i => $major)
                        @if ($i < $visible)
                            <li>
                                <span class="n">{{ $i + 1 }}</span>
                                <span>{{ $major['name_vi'] }}<small>{{ $major['name_de'] }}</small></span>
                                <span class="lv">{{ ($major['source'] ?? null) === 'pair' ? setting('result_pair_label', 'Gợi ý chính') : $major['label'] }}</span>
                            </li>
                        @else
                            {{-- Dòng giữ chỗ: không in tên ngành thật, xem đủ trong báo cáo đầy đủ --}}
                            <li class="blur" aria-hidden="true">
                                <span class="n">{{ $i + 1 }}</span>
                                <span>Ngành gợi ý thêm cho bạn<small>Xem trong báo cáo đầy đủ</small></span>
                                <span class="lv">Liên quan</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif

            @php($more = array_filter([setting('result_more_1'), setting('result_more_2'), setting('result_more_3'), setting('result_more_4')]))
            @if ($more)
                <p style="margin:18px 0 0;font-weight:600;font-size:14px">{{ setting('result_more_heading', 'Báo cáo đầy đủ giúp bạn hiểu:') }}</p>
                <ul class="what">
                    @foreach ($more as $line)
                        <li><svg class="ic"><use href="#i-check"/></svg>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif

            <div style="text-align:center;margin-top:28px">
                <a class="kbtn kbtn--red kbtn--lg" href="{{ route('report.request') }}">
                    {{ setting('result_button_text', 'Xem báo cáo đầy đủ') }} <svg class="ic"><use href="#i-arrow-right"/></svg>
                </a>
            </div>
        </div>
    </div>
@endsection
