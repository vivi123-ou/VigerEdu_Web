@extends('layouts.category')

{{-- View chung cho chuyên mục thêm từ admin (không có view riêng). Controller truyền $category. --}}

@php
  $groupLabels = [
    'giao-duc-tai-duc' => 'Giáo dục tại Đức',
    'tu-van' => 'Tư vấn du học',
    'blog' => 'Blog',
  ];
@endphp

@section('title', $category->name)
@section('banner_image', 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=1600&q=80')
@section('banner_color', 'rgba(193, 18, 31, 0.8)')
@section('banner_pretitle', $groupLabels[$category->menu_group] ?? 'Chuyên mục')
@section('banner_heading', mb_strtoupper($category->name))
@section('banner_desc', $category->description ?: 'Tổng hợp các bài viết thuộc chuyên mục '.$category->name.'.')
