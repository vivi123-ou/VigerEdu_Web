@extends('layouts.category')

{{-- Nội dung quản trị ở Admin > Chương trình học (App\Support\ProgramsPage) --}}
@php
  $hero = \App\Support\ProgramsPage::hero('main');
  $cta = \App\Support\ProgramsPage::all();
@endphp

@section('title', $hero['page_title'])
@section('banner_image', $hero['image'])
@section('banner_color', 'rgba(18, 38, 63, 0.8)')
@section('banner_pretitle', $hero['pretitle'])
@section('banner_heading', $hero['heading'])
@section('banner_desc', $hero['description'])

@section('after_content')
  <section class="cta-section animate-hidden">
    <div class="page-container">
      <div class="section-pretitle" style="justify-content: center">
        <span>{{ $cta['programs_cta_pretitle'] }}</span>
      </div>
      <h2 class="cta-title">{{ $cta['programs_cta_title'] }}</h2>
      <p class="cta-desc">{{ $cta['programs_cta_description'] }}</p>
      <a class="cta-pill" href="{{ $cta['programs_cta_button_url'] }}">{{ $cta['programs_cta_button_text'] }}</a>
    </div>
  </section>
@endsection
