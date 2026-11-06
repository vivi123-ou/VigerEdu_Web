@extends('layouts.public')

@php
    use App\Support\IntroBlocks;

    $blocks = $blocks ?? IntroBlocks::enabled();
    $bannerImage = media_url(setting('banner_image'));
    $bannerMobile = media_url(setting('banner_image_mobile')) ?? $bannerImage;
    // Khối "Cách làm bài" đầu tiên được đặt cạnh banner (khi admin chọn và không có ảnh banner).
    $heroSteps = $bannerImage ? null : collect($blocks)->first(fn ($block) => $block['type'] === 'steps' && ($block['data']['in_hero'] ?? false));
    $sectionBlocks = collect($blocks)->reject(fn ($block) => $heroSteps && $block['id'] === $heroSteps['id'])->values();
    $benefits = array_filter([setting('intro_benefit_1'), setting('intro_benefit_2'), setting('intro_benefit_3')]);
@endphp

@section('content')
    <section class="hero">
        <div @class(['container', 'hero__grid', 'hero__grid--single' => ! $bannerImage && ! $heroSteps])>
            <div class="hero__main">
                @if ($bannerMobile)
                    <img class="hero__image hero__image--mobile" src="{{ $bannerMobile }}" alt="">
                @endif
                <p class="eyebrow">{{ setting('intro_eyebrow', setting('test_name')) }}</p>
                <h1 class="hero__title">{{ setting('intro_title') }}</h1>
                @if (filled(setting('intro_subtitle')))
                    <p class="hero__subtitle">{{ setting('intro_subtitle') }}</p>
                @endif

                @if ($benefits)
                    <ul class="benefits">
                        @foreach ($benefits as $benefit)
                            <li>@include('partials.icon', ['name' => 'check', 'size' => 18, 'class' => 'benefits__icon']) {{ $benefit }}</li>
                        @endforeach
                    </ul>
                @endif

                <a class="btn btn--primary btn--lg" href="{{ route('test.show', array_filter(request()->only(['bo', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']))) }}">
                    {{ setting('intro_button_text', 'Bắt đầu') }} <span class="btn__arrow" aria-hidden="true">→</span>
                </a>
                @if (filled(setting('intro_button_note')))
                    <p class="hero__note">{{ setting('intro_button_note') }}</p>
                @endif
            </div>

            @if ($bannerImage)
                <div class="hero__media">
                    <img class="hero__image hero__image--desktop" src="{{ $bannerImage }}" alt="">
                </div>
            @elseif ($heroSteps)
                @include('public.blocks.steps', ['block' => $heroSteps, 'inHero' => true])
            @endif
        </div>
    </section>

    @foreach ($sectionBlocks as $block)
        @include('public.blocks.'.$block['type'], ['block' => $block, 'inHero' => false, 'soft' => $loop->odd])
    @endforeach
@endsection
