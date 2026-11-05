@extends('layouts.public')

@section('content')
    <section class="hero">
        <div class="container hero__inner">
            <p class="hero__eyebrow">{{ setting('test_name') }}</p>
            <h1 class="hero__title">{{ setting('intro_title') }}</h1>
            <p class="hero__subtitle">{{ setting('intro_subtitle') }}</p>

            <ul class="hero__facts">
                <li><strong>{{ $questionCount }}</strong> {{ setting('intro_fact_questions', 'câu hỏi') }}</li>
                @if (filled(setting('intro_fact_time')))
                    <li><strong>{{ setting('intro_fact_time') }}</strong></li>
                @endif
                @if (filled(setting('intro_fact_price')))
                    <li><strong>{{ setting('intro_fact_price') }}</strong></li>
                @endif
            </ul>

            {{-- Giai đoạn 3: nút dẫn tới form Phần A. Tạm thời báo đang hoàn thiện. --}}
            <button class="btn btn--primary btn--lg" type="button" disabled aria-describedby="sap-mo">
                {{ setting('intro_button_text', 'Bắt đầu làm bài') }}
            </button>
            <p id="sap-mo" class="hero__note">{{ setting('intro_coming_soon') }}</p>
        </div>
    </section>
@endsection
