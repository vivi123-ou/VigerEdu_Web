@extends('layouts.public')

@section('content')
    <section class="hero">
        <div class="container">
            <p class="hero__eyebrow">{{ setting('test_name') }}</p>
            <h1 class="hero__title">{{ setting('intro_title') }}</h1>
            <p class="hero__subtitle">{{ setting('intro_subtitle') }}</p>

            <dl class="facts">
                <div class="facts__item">
                    <dt>{{ setting('intro_fact_questions', 'câu hỏi') }}</dt>
                    <dd>{{ $questionCount }}</dd>
                </div>
                @if (filled(setting('intro_fact_time')))
                    <div class="facts__item">
                        <dt>{{ setting('intro_fact_time_label', 'Thời gian') }}</dt>
                        <dd>{{ setting('intro_fact_time') }}</dd>
                    </div>
                @endif
                @if (filled(setting('intro_fact_price')))
                    <div class="facts__item">
                        <dt>{{ setting('intro_fact_price_label', 'Chi phí') }}</dt>
                        <dd>{{ setting('intro_fact_price') }}</dd>
                    </div>
                @endif
            </dl>

            {{-- Giai đoạn 3: nút dẫn tới form Phần A. Tạm thời báo đang hoàn thiện. --}}
            <button class="btn btn--primary btn--lg" type="button" disabled aria-describedby="sap-mo">
                {{ setting('intro_button_text', 'Bắt đầu làm bài') }} <span class="btn__arrow" aria-hidden="true">→</span>
            </button>
            <p id="sap-mo" class="hero__note">{{ setting('intro_coming_soon') }}</p>
        </div>
    </section>
@endsection
