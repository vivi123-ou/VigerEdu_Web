@extends('layouts.public')

@php
    $groups = ['R' => 'Realistic', 'I' => 'Investigative', 'A' => 'Artistic', 'S' => 'Social', 'E' => 'Enterprising', 'C' => 'Conventional'];
@endphp

@section('content')
    <section class="hero">
        <div class="container hero__grid">
            <div class="hero__main">
                <p class="eyebrow">{{ setting('intro_eyebrow', setting('test_name')) }}</p>
                <h1 class="hero__title">{{ setting('intro_title') }}</h1>
                <p class="hero__subtitle">{{ setting('intro_subtitle') }}</p>

                @php
                    $benefits = array_filter([setting('intro_benefit_1'), setting('intro_benefit_2'), setting('intro_benefit_3')]);
                @endphp
                @if ($benefits)
                    <ul class="benefits">
                        @foreach ($benefits as $benefit)
                            <li>@include('partials.icon', ['name' => 'check', 'size' => 18, 'class' => 'benefits__icon']) {{ $benefit }}</li>
                        @endforeach
                    </ul>
                @endif

                {{-- Giai đoạn 3a: nút dẫn tới trang làm bài. Tạm thời báo đang hoàn thiện. --}}
                <button class="btn btn--primary btn--lg" type="button" disabled aria-describedby="sap-mo">
                    {{ setting('intro_button_text', 'Bắt đầu') }} <span class="btn__arrow" aria-hidden="true">→</span>
                </button>
                <p id="sap-mo" class="hero__note">{{ setting('intro_coming_soon') ?: setting('intro_button_note') }}</p>
            </div>

            @if (filled(setting('steps_title')))
                <aside class="steps" aria-labelledby="cach-lam">
                    <h2 id="cach-lam" class="steps__title">{{ setting('steps_title') }}</h2>
                    <ol class="steps__list">
                        @foreach ([1, 2, 3] as $step)
                            @if (filled(setting("step_{$step}_title")))
                                <li class="steps__item">
                                    <span class="steps__number">{{ $step }}</span>
                                    <div>
                                        <p class="steps__name">{{ setting("step_{$step}_title") }}</p>
                                        <p class="steps__text">{{ setting("step_{$step}_text") }}</p>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ol>
                    @if (filled(setting('intro_parent_note')))
                        <p class="steps__note">{{ setting('intro_parent_note') }}</p>
                    @endif
                </aside>
            @endif
        </div>
    </section>

    @if (filled(setting('groups_title')))
        <section class="section section--soft" aria-labelledby="phuong-phap">
            <div class="container">
                <div class="section__head">
                    <h2 id="phuong-phap" class="section__title">{{ setting('groups_title') }}</h2>
                    <p class="section__lead">{{ setting('groups_intro') }}</p>
                </div>

                <ul class="groups">
                    @foreach ($groups as $letter => $english)
                        <li class="group">
                            <span class="group__letter" aria-hidden="true">{{ $letter }}</span>
                            <div>
                                <h3 class="group__name">{{ setting("group_name_{$letter}") }} <span class="group__en">{{ $english }}</span></h3>
                                <p class="group__desc">{{ setting("group_desc_{$letter}") }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
@endsection
