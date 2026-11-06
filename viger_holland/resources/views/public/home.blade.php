@extends('layouts.public')

@php
    use App\Support\IntroBlocks;
    use App\Support\PageEditor;

    $editing = PageEditor::active();
    $blocks = $blocks ?? ($editing ? IntroBlocks::all() : IntroBlocks::enabled());
    $bannerPath = setting('banner_image');
    $bannerImage = media_url($bannerPath);
    $bannerMobile = media_url(setting('banner_image_mobile')) ?? $bannerImage;
    // Khối "Cách làm bài" đầu tiên (đang bật) được đặt cạnh banner khi admin chọn và banner không có ảnh.
    $heroSteps = ($bannerImage || $editing) ? null : collect($blocks)->first(fn ($block) => $block['type'] === 'steps' && ($block['data']['in_hero'] ?? false));
    $sectionBlocks = collect($blocks)->reject(fn ($block) => $heroSteps && $block['id'] === $heroSteps['id'])->values();
    $benefitKeys = $editing ? ['intro_benefit_1', 'intro_benefit_2', 'intro_benefit_3'] : array_keys(array_filter([
        'intro_benefit_1' => setting('intro_benefit_1'), 'intro_benefit_2' => setting('intro_benefit_2'), 'intro_benefit_3' => setting('intro_benefit_3'),
    ]));
@endphp

@section('content')
    <section class="hero">
        <div @class(['container', 'hero__grid', 'hero__grid--single' => ! $bannerImage && ! $heroSteps && ! $editing])>
            <div class="hero__main">
                @if ($bannerMobile)
                    <img class="hero__image hero__image--mobile" src="{{ $bannerMobile }}" alt="">
                @endif
                <p class="eyebrow">{{ ed('intro_eyebrow', setting('test_name'), 'Dòng chữ nhỏ trên tiêu đề') }}</p>
                <h1 class="hero__title">{{ ed('intro_title', null, 'Tiêu đề lớn') }}</h1>
                @if ($editing || filled(setting('intro_subtitle')))
                    <p class="hero__subtitle">{{ ed('intro_subtitle', null, 'Đoạn giới thiệu') }}</p>
                @endif

                @if ($benefitKeys)
                    <ul class="benefits">
                        @foreach ($benefitKeys as $key)
                            <li>@include('partials.icon', ['name' => 'check', 'size' => 18, 'class' => 'benefits__icon']) {{ ed($key, null, 'Lợi ích (để trống thì ẩn)') }}</li>
                        @endforeach
                    </ul>
                @endif

                <a class="btn btn--primary btn--lg" href="{{ route('test.show', array_filter(request()->only(['bo', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']))) }}">
                    {{ ed('intro_button_text', 'Bắt đầu', 'Chữ trên nút') }} <span class="btn__arrow" aria-hidden="true">→</span>
                </a>
                @if ($editing || filled(setting('intro_button_note')))
                    <p class="hero__note">{{ ed('intro_button_note', null, 'Dòng chữ nhỏ dưới nút') }}</p>
                @endif
            </div>

            @if ($bannerImage || $editing)
                <div class="hero__media">
                    @if ($bannerImage)
                        <img class="hero__image hero__image--desktop" src="{{ $bannerImage }}" alt="" {{ PageEditor::imageAttributes('banner_image', $bannerPath) }}>
                    @else
                        <div class="ed-image-empty" {{ PageEditor::imageAttributes('banner_image', '') }}>+ Bấm để thêm ảnh banner</div>
                    @endif
                </div>
            @elseif ($heroSteps)
                @include('public.blocks.steps', ['block' => $heroSteps, 'inHero' => true])
            @endif
        </div>
    </section>

    @foreach ($sectionBlocks as $block)
        @if ($editing)
            @include('public.blocks.edit-bar', ['block' => $block, 'first' => $loop->first, 'last' => $loop->last])
        @endif
        <div @class(['ed-block', 'is-off' => ! $block['enabled']])>
            @include('public.blocks.'.$block['type'], ['block' => $block, 'inHero' => false, 'soft' => $loop->odd])
        </div>
    @endforeach
@endsection
