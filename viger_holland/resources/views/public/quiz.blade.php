{{--
    Trang làm bài (H3) theo mẫu docs/mau_giao_dien/luong-khach.html (màn hình 1): thanh tiến độ một dải liền ở đầu trang,
    ô chú thích 5 mức sao, các câu của trang (mặc định 6), mỗi câu chọn 1–5 sao, câu nào cũng bắt buộc.
    Sao là ô chọn thật (radio) nên vẫn dùng được khi không có JavaScript; khach.js thêm hiệu ứng rê chuột, chữ của mức, đo thời gian.
--}}
@extends('layouts.public')

@section('title', setting('test_name'))
@section('noindex', true)

@section('header')
    <header class="khdr">
        <div class="khdr__in">
            <a class="kbrand" href="{{ route('home') }}">
                <img src="{{ media_url(setting('logo_path', 'img/logo.png')) }}" alt="{{ setting('site_name') }}" width="40" height="40">
                <span><b>{{ setting('brand_title', setting('site_name')) }}</b></span>
            </a>
            <div class="kprog" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Tiến độ làm bài">
                <i style="width: {{ $progress }}%" data-progress></i>
            </div>
        </div>
    </header>
@endsection

@section('content')
    <div class="ksec" style="padding-top:28px">
        <div class="narrow" style="padding:0 20px">
            <div class="legend">
                <p>{{ setting('quiz_legend_title', 'Chú thích') }}</p>
                <ul>
                    @foreach ($labels as $level => $label)
                        <li>
                            <span class="mini" aria-hidden="true">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg @class(['on' => $s <= $level + 1])><use href="#star"/></svg>
                                @endfor
                            </span>{{ $label }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <form method="post" action="{{ route('test.submit') }}" class="quiz" data-quiz data-labels='@json($labels)' data-page-share="{{ round($items->count() / max(1, $total) * 100, 3) }}"
                  data-missing="{{ setting('quiz_missing_text', 'Chưa chọn – câu này còn thiếu') }}" novalidate>
                @csrf
                <input type="hidden" name="page" value="{{ $page }}">

                <div class="qs">
                    @foreach ($items as $i => $item)
                        @php
                            $value = (int) old('answers.'.$item->question_id, $answers[$item->question_id] ?? 0);
                            $isMissing = $errors->has('answers') && $value === 0;
                        @endphp
                        <fieldset @class(['q', 'todo' => $isMissing]) data-question>
                            <legend class="q__t"><span>{{ $startNumber + $i }}</span>{{ $item->text_snapshot }}</legend>
                            <div class="q__a">
                                <span class="stars" role="radiogroup" aria-label="Mức giống bạn">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <input type="radio" id="q{{ $item->question_id }}s{{ $s }}" name="answers[{{ $item->question_id }}]" value="{{ $s }}" @checked($value === $s)>
                                        <label for="q{{ $item->question_id }}s{{ $s }}" title="{{ $labels[$s - 1] }}">
                                            <svg aria-hidden="true"><use href="#star"/></svg><span class="visually-hidden">{{ $s }} sao – {{ $labels[$s - 1] }}</span>
                                        </label>
                                    @endfor
                                </span>
                                <span @class(['q__lbl', 'no' => $isMissing]) data-label aria-live="polite">
                                    {{ $value ? $labels[$value - 1] : ($isMissing ? setting('quiz_missing_text') : '') }}
                                </span>
                                <input type="hidden" name="ms[{{ $item->question_id }}]" value="{{ old('ms.'.$item->question_id) }}" data-ms>
                            </div>
                        </fieldset>
                    @endforeach
                </div>

                @error('answers')
                    <p class="qerr" role="alert">{{ $message }}</p>
                @enderror

                <div class="qnav">
                    @if ($page > 1)
                        <button class="kbtn kbtn--ghost" type="submit" name="direction" value="back" formnovalidate>
                            <svg class="ic"><use href="#i-arrow-left"/></svg> {{ setting('quiz_prev_text', 'Phần trước') }}
                        </button>
                    @else
                        <span></span>
                    @endif
                    <button class="kbtn kbtn--red" type="submit" name="direction" value="next">
                        {{ $page < $pageCount ? setting('quiz_next_text', 'Tiếp tục') : setting('quiz_finish_text', 'Xem kết quả') }}
                        <svg class="ic"><use href="#i-arrow-right"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/khach.js') }}" defer></script>
@endpush
