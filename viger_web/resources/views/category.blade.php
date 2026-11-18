@extends('layouts.category')

{{-- View chung cho chuyên mục thêm từ admin (không có view riêng). Controller truyền $category. --}}

@php
  $groupLabels = __('category.groups');
@endphp

@section('title', $category->name)
@section('banner_image', 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=1600&q=80')
@section('banner_color', 'rgba(193, 18, 31, 0.8)')
@section('banner_pretitle', $groupLabels[$category->menu_group] ?? __('category.default_pretitle'))
@section('banner_heading', mb_strtoupper($category->name))
@section('banner_desc', $category->getTranslation('description', app()->getLocale(), false) ?: __('category.default_description', ['name' => $category->name]))
