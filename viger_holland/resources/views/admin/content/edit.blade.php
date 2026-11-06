@extends('layouts.admin', ['menuKey' => $page['menu']])

@section('title', $page['title'])

@php
    $hasRich = collect(\App\Support\ContentPages::fields($page))->contains('type', 'rich');
@endphp

@section('content')
    @includeWhen($pageKey === 'intro', 'admin.intro-blocks.tabs', ['active' => 'banner'])

    @php $editorPage = ['result-page' => 'trang-ket-qua', 'email' => 'email'][$pageKey] ?? null; @endphp
    @if ($editorPage)
        <p><a class="btn btn--primary" href="{{ route('admin.page-editor.show', $editorPage) }}">✎ Sửa trực tiếp trên trang (dễ hơn)</a></p>
    @endif

    <p class="lead">Sửa xong bấm <strong>"Xem trước"</strong> để mở trang khách ở tab mới (chưa lưu), vừa ý thì bấm <strong>"Lưu"</strong>. Sửa nhầm thì bấm "Quay lại bản này" ở Lịch sử thay đổi cuối trang.</p>

    <form method="post" action="{{ route('admin.content.update', $pageKey) }}" class="form form--wide" data-content-form>
        @csrf
        @method('PUT')

        @foreach ($page['sections'] as $sectionTitle => $fields)
            <section class="card">
                <h2 class="card__title">{{ $sectionTitle }}</h2>
                @foreach ($fields as $field)
                    @include('admin.content.field', ['field' => $field, 'value' => old($field['key'], $values[$field['key']] ?? null)])
                @endforeach
            </section>
        @endforeach

        <div class="form__actions form__actions--sticky">
            <button class="btn" type="submit" formaction="{{ route('admin.content.preview', $pageKey) }}" formtarget="_blank">Xem trước</button>
            <button class="btn btn--primary" type="submit">Lưu</button>
        </div>
    </form>

    @includeWhen($pageKey === 'embed', 'admin.content.embed-codes')
    @include('admin.partials.history', ['activities' => $activities])
    @include('admin.media.picker')
@endsection

@if ($hasRich)
    @push('scripts')
        @include('admin.partials.rich-editor')
    @endpush
@endif
