@extends('layouts.category')

{{-- Nội dung banner quản trị ở Admin > Giáo dục tại Đức (App\Support\EducationPages) --}}
@php($hero = \App\Support\EducationPages::hero('overview'))

@section('title', $hero['page_title'])
@section('banner_image', $hero['image'])
@section('banner_color', 'rgba(193, 18, 31, 0.8)')
@section('banner_pretitle', $hero['pretitle'])
@section('banner_heading', $hero['heading'])
@section('banner_desc', $hero['description'])
