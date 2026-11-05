@extends('layouts.public')

@section('title', setting('test_title', setting('test_name')).' – '.setting('site_name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')
    @php
        $isYesNo = count($scale) <= 2;
        $totalSteps = count($pages) + 1;
        $infoHasErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'info.') || $key === 'submit');
    @endphp

    @if ($isAdminPreview ?? false)
        {{-- Chỉ người quản trị đang đăng nhập mới thấy dòng này --}}
        <div class="preview-banner" role="status">
            Đang xem trước bộ "{{ $form->set->name }}"@if (! $form->set->is_active) (bộ đang TẮT: khách chưa mở được, nộp bài sẽ báo lỗi)@endif.
        </div>
    @endif

    <div class="test">
        <div class="container container--narrow">
            <form method="post" action="{{ route('test.submit') }}" id="test-form" class="test-form" novalidate
                  data-storage-key="holland:{{ $form->set->code }}"
                  data-has-old="{{ $errors->any() ? '1' : '0' }}"
                  data-unanswered="{{ setting('test_unanswered') }}">
                @csrf
                <input type="hidden" name="bo" value="{{ $form->set->code }}">
                @foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utm)
                    <input type="hidden" name="utm[{{ $utm }}]" value="{{ old('utm.'.$utm, request($utm)) }}" data-utm="{{ $utm }}">
                @endforeach

                <div class="progress" aria-hidden="true"><div class="progress__bar" data-progress></div></div>

                @if ($errors->any())
                    <div class="alert alert--error" role="alert">
                        {{ $errors->first('submit') ?: setting('info_error_summary') }}
                    </div>
                @endif

                {{-- Các trang câu hỏi Holland --}}
                @foreach ($pages as $index => $page)
                    <section class="step" data-step="{{ $index }}" aria-label="{{ setting('test_title') }}">
                        @if ($index === 0)
                            <h1 class="test__title">{{ setting('test_title', setting('test_name')) }}</h1>
                            <p class="test__lead">{{ setting('test_instruction') }}</p>
                        @endif

                        @php
                            // Bảng "Cách chọn" chỉ khi trang có câu dùng thang ô số của bộ.
                            $pageUsesSetScale = $page->contains(fn ($q) => $q->answer_type === $form->set->answer_type && empty($q->options)
                                && $q->displayStyle($form->set) === \App\Models\Question::DISPLAY_BUTTONS);
                        @endphp
                        @if (! $isYesNo && $pageUsesSetScale)
                            <div class="legend">
                                <p class="legend__title">{{ setting('test_scale_title', 'Cách chọn') }}</p>
                                <ol class="legend__list">
                                    @foreach ($scale as $option)
                                        <li><span class="legend__num">{{ $loop->iteration }}</span> {{ $option['label'] }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif

                        @foreach ($page as $question)
                            @include('public.partials.holland-question', ['question' => $question, 'set' => $form->set])
                        @endforeach

                        <p class="step__warning" data-step-warning hidden>{{ setting('test_unanswered') }}</p>

                        <div class="step__nav js-only">
                            @if ($index > 0)
                                <button type="button" class="btn btn--ghost" data-prev>{{ setting('test_prev', 'Quay lại') }}</button>
                            @endif
                            <button type="button" class="btn btn--primary" data-next>{{ setting('test_next', 'Tiếp tục') }} <span aria-hidden="true">→</span></button>
                        </div>
                    </section>
                @endforeach

                {{-- Bước cuối: thông tin để nhận kết quả (Phần A) --}}
                <section class="step step--info" data-step="{{ count($pages) }}" data-info-step @if ($infoHasErrors) data-has-error @endif>
                    <h2 class="test__title">{{ setting('info_title') }}</h2>
                    <p class="test__lead">{{ setting('info_intro') }}</p>

                    {{-- Hiện đúng thứ tự admin xếp; câu không bắt buộc không có dấu * --}}
                    <div class="info-grid">
                        @foreach ($sections['fields'] as $question)
                            @include('public.partials.info-field', ['question' => $question])
                        @endforeach
                    </div>

                    @foreach ($sections['consent'] as $question)
                        @include('public.partials.info-field', ['question' => $question])
                    @endforeach

                    <p class="privacy">{{ setting('info_privacy') }}</p>

                    <div class="step__nav">
                        @if (count($pages) > 0)
                            <button type="button" class="btn btn--ghost js-only" data-prev>{{ setting('test_prev', 'Quay lại') }}</button>
                        @endif
                        <button type="submit" class="btn btn--primary" data-submit>{{ setting('info_submit', 'Xem kết quả') }} <span aria-hidden="true">→</span></button>
                    </div>
                </section>
            </form>

            <p class="test__saved">{{ setting('test_saved_note') }}</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/test.js') }}" defer></script>
@endpush
