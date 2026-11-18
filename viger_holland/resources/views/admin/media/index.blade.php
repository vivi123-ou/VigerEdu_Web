@extends('layouts.admin', ['menuKey' => 'media'])

@section('title', 'Kho ảnh')

@section('content')
    <p class="lead">Ảnh dùng cho banner, logo, khối nội dung. Ảnh rộng hơn 1920px được tự thu nhỏ và nén để trang tải nhanh. Trong các trang nội dung, bấm "Chọn ảnh" để lấy ảnh từ đây.</p>

    <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="card form">
        @csrf
        <div class="field">
            <label for="files">Tải ảnh lên</label>
            <input id="files" name="files[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
            <p class="field__hint">Chọn được nhiều ảnh cùng lúc. JPG, PNG, WebP hoặc GIF, mỗi ảnh tối đa 8 MB. Banner nên rộng 1600–1920px.</p>
            @error('files')<p class="field__error">{{ $message }}</p>@enderror
            @error('files.*')<p class="field__error">{{ $message }}</p>@enderror
        </div>
        <button class="btn btn--primary" type="submit">Tải lên</button>
    </form>

    <section class="card">
        <h2 class="card__title">Ảnh đã có ({{ $media->total() }})</h2>
        @if ($media->isEmpty())
            <p class="muted">Chưa có ảnh nào.</p>
        @else
            <ul class="media-grid">
                @foreach ($media as $item)
                    <li class="media-grid__item">
                        <img src="{{ $item->url() }}" alt="" loading="lazy">
                        <p class="media-grid__name" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                        <p class="muted media-grid__meta">{{ $item->width }}×{{ $item->height }} · {{ $item->sizeLabel() }}</p>
                        <div class="media-grid__actions">
                            @include('admin.partials.copy-link', ['url' => $item->url()])
                            <form method="post" action="{{ route('admin.media.destroy', $item) }}" data-confirm="Đưa ảnh này vào thùng rác?">
                                @csrf @method('DELETE')
                                <button class="link-danger" type="submit">Xóa</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            {{ $media->links('admin.partials.pagination') }}
        @endif
    </section>

    @if ($trashed->isNotEmpty())
        <section class="card">
            <h2 class="card__title">Thùng rác</h2>
            <ul class="history">
                @foreach ($trashed as $item)
                    <li class="history__item">
                        <span>{{ $item->original_name }} <span class="muted">· xóa lúc {{ $item->deleted_at->format('H:i d/m/Y') }}</span></span>
                        <form method="post" action="{{ route('admin.media.restore', $item->id) }}">
                            @csrf
                            <button class="btn btn--small" type="submit">Khôi phục</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
