@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', 'Bộ câu hỏi')

@section('content')
    <section class="card">
        <div class="list-toolbar">
            <p class="muted" style="margin:0">Mỗi bộ câu là một bài test. Khách mở link chung sẽ làm <strong>bộ mặc định</strong>.</p>
            <div class="list-toolbar__right">
                <a class="btn" href="{{ route('admin.excel.index') }}">@include('partials.icon', ['name' => 'table', 'size' => 16]) Excel</a>
                <a class="btn btn--accent" href="{{ route('admin.question-sets.create') }}">@include('partials.icon', ['name' => 'plus', 'size' => 16]) Thêm mới</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="center">STT</th>
                        <th>Tên bộ câu</th>
                        <th class="center">Số câu</th>
                        <th>Link làm bài</th>
                        <th class="center">Hiển thị</th>
                        <th class="center">Tác vụ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sets as $set)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('admin.question-sets.show', $set) }}">{{ $set->name }}</a>
                                @if ($set->is_default)<span class="badge">Mặc định</span>@endif
                            </td>
                            <td class="center">{{ $set->active_questions_count }}</td>
                            <td>@include('admin.partials.copy-link', ['url' => $set->testUrl()])</td>
                            <td class="center">
                                <form method="post" action="{{ route('admin.question-sets.toggle', $set) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" @class(['toggle', 'is-on' => $set->is_active]) title="{{ $set->is_active ? 'Đang hiện – bấm để ẩn' : 'Đang ẩn – bấm để hiện' }}" aria-label="{{ $set->is_active ? 'Đang hiện' : 'Đang ẩn' }}"></button>
                                </form>
                            </td>
                            <td class="center actions">
                                <a class="icon-btn icon-btn--view" href="{{ route('admin.question-sets.show', $set) }}" title="Xem và sửa câu hỏi" aria-label="Câu hỏi">@include('partials.icon', ['name' => 'list', 'size' => 16])</a>
                                <a class="icon-btn icon-btn--edit" href="{{ route('admin.question-sets.edit', $set) }}" title="Cài đặt bộ câu" aria-label="Sửa">@include('partials.icon', ['name' => 'edit', 'size' => 16])</a>
                                @unless ($set->is_default)
                                    <form method="post" action="{{ route('admin.question-sets.destroy', $set) }}" style="display:inline" data-confirm="Đưa bộ &quot;{{ $set->name }}&quot; vào thùng rác?">
                                        @csrf @method('DELETE')
                                        <button class="icon-btn icon-btn--delete" type="submit" title="Xóa (vào thùng rác)" aria-label="Xóa">@include('partials.icon', ['name' => 'trash', 'size' => 16])</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($trashed->isNotEmpty())
        <section class="card">
            <h2 class="card__title">Thùng rác</h2>
            <ul class="history">
                @foreach ($trashed as $set)
                    <li class="history__item">
                        <span>{{ $set->name }} <span class="muted">· xóa lúc {{ $set->deleted_at->format('H:i d/m/Y') }}</span></span>
                        <form method="post" action="{{ route('admin.question-sets.restore', $set->id) }}">
                            @csrf
                            <button class="btn btn--small" type="submit">Khôi phục</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
