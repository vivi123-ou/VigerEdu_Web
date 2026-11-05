@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', 'Bộ câu hỏi')

@section('content')
    <p class="lead">Mỗi bộ câu là một bài test Holland riêng. Khách mở link chung sẽ làm <strong>bộ mặc định</strong>; bộ khác có link riêng (gửi cho từng nhóm khách hoặc gắn quảng cáo).</p>

    <div class="toolbar">
        <a class="btn btn--primary" href="{{ route('admin.question-sets.create') }}">+ Thêm bộ câu</a>
        <a class="btn" href="{{ route('admin.excel.index') }}">Xuất / nhập Excel</a>
    </div>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tên bộ</th>
                        <th>Kiểu trả lời</th>
                        <th>Số câu đang bật</th>
                        <th>Trạng thái</th>
                        <th>Link làm bài</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sets as $set)
                        <tr>
                            <td>
                                <a href="{{ route('admin.question-sets.show', $set) }}"><strong>{{ $set->name }}</strong></a>
                                @if ($set->is_default)<span class="badge">Mặc định</span>@endif
                            </td>
                            <td>{{ $set->answer_type->label() }}</td>
                            <td>{{ $set->active_questions_count }} / {{ $set->questions_count }}</td>
                            <td><span @class(['status', 'status--ok' => $set->is_active, 'status--warn' => ! $set->is_active])>{{ $set->is_active ? 'Đang bật' : 'Đang tắt' }}</span></td>
                            <td>@include('admin.partials.copy-link', ['url' => $set->testUrl()])</td>
                            <td class="actions">
                                <a href="{{ route('admin.question-sets.show', $set) }}">Câu hỏi</a>
                                <a href="{{ route('admin.question-sets.edit', $set) }}">Sửa</a>
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
