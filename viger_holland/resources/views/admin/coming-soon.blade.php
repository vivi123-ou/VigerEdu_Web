@extends('layouts.admin', ['menuKey' => $item['key']])

@section('title', $item['label'])

@section('content')
    <section class="card card--empty">
        <p class="card__icon" aria-hidden="true">🛠️</p>
        <h2 class="card__title">Mục này đang được xây dựng</h2>
        <p>Sẽ có ở <strong>giai đoạn {{ $item['stage'] }}</strong>.</p>
        <p class="muted">{{ $item['help'] }}</p>
        <a class="btn" href="{{ route('admin.dashboard') }}">← Về bảng điều khiển</a>
    </section>
@endsection
