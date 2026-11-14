@extends('layouts.category')

{{-- Nội dung banner quản trị ở Admin > Tư vấn (App\Support\ConsultPages) --}}
@php($hero = \App\Support\ConsultPages::hero('master'))

@section('title', $hero['page_title'])
@section('banner_image', $hero['image'])
@section('banner_color', 'rgba(18, 38, 63, 0.8)')
@section('banner_pretitle', $hero['pretitle'])
@section('banner_heading', $hero['heading'])
@section('banner_desc', $hero['description'])
