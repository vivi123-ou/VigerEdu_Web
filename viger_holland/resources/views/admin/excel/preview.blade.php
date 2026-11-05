@extends('layouts.admin', ['menuKey' => 'excel'])

@section('title', 'Xem trước khi nhập')

@section('content')
    @php
        $counts = $preview['counts'];
        $hasErrors = $preview['file_errors'] !== [] || $counts['errors'] > 0;
        $actionLabels = ['create' => 'Thêm mới', 'update' => 'Sửa', 'unchanged' => 'Không đổi'];
    @endphp

    <p><a href="{{ route('admin.excel.index') }}">← Chọn file khác</a></p>
    <p class="lead">File <strong>{{ $fileName }}</strong> → bộ <strong>{{ $set->name }}</strong>. Chưa có gì được ghi.</p>

    @foreach ($preview['file_errors'] as $error)
        <div class="alert alert--error" role="alert">{{ $error }}</div>
    @endforeach

    <div class="stats">
        <div class="stat"><p class="stat__value">{{ $counts['create'] }}</p><p class="stat__label">Câu thêm mới</p></div>
        <div class="stat"><p class="stat__value">{{ $counts['update'] }}</p><p class="stat__label">Câu được sửa</p></div>
        <div class="stat"><p class="stat__value">{{ $counts['deactivate'] }}</p><p class="stat__label">Câu sẽ bị tắt (không có trong file)</p></div>
        <div @class(['stat', 'stat--danger' => $counts['errors'] > 0])><p class="stat__value">{{ $counts['errors'] }}</p><p class="stat__label">Dòng bị lỗi</p></div>
    </div>

    @if ($hasErrors)
        <div class="alert alert--error" role="alert">File còn lỗi nên <strong>chưa thể nhập</strong>. Sửa các dòng báo đỏ trong Excel rồi tải lên lại.</div>
    @else
        <form method="post" action="{{ route('admin.excel.import') }}" class="form__actions" data-confirm="Nhập file này? Thêm {{ $counts['create'] }}, sửa {{ $counts['update'] }}, tắt {{ $counts['deactivate'] }} câu.">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="question_set_id" value="{{ $set->id }}">
            <input type="hidden" name="file_name" value="{{ $fileName }}">
            <button class="btn btn--primary" type="submit">Xác nhận nhập</button>
            <a class="btn btn--plain" href="{{ route('admin.excel.index') }}">Hủy</a>
        </form>
    @endif

    @if ($preview['rows'] !== [])
        <section class="card">
            <h2 class="card__title">Từng dòng trong file</h2>
            <div class="table-wrap">
                <table class="table table--wrap">
                    <thead>
                        <tr><th>Dòng</th><th>Mã câu</th><th>Câu hỏi</th><th>Kết quả</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $row)
                            <tr @class(['is-error' => $row['errors'] !== [], 'is-off' => $row['errors'] === [] && $row['action'] === 'unchanged'])>
                                <td>{{ $row['line'] }}</td>
                                <td>{{ $row['code'] ?: '(mới)' }}</td>
                                <td>{{ mb_strimwidth($row['text'], 0, 90, '…') }}</td>
                                <td>
                                    @if ($row['errors'] !== [])
                                        @foreach ($row['errors'] as $error)
                                            <p class="field__error">{{ $error }}</p>
                                        @endforeach
                                    @else
                                        {{ $actionLabels[$row['action']] }}@if ($row['changes'] !== []): {{ implode(', ', $row['changes']) }}@endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($preview['deactivate'] !== [])
        <section class="card">
            <h2 class="card__title">Câu đang bật nhưng không có trong file (sẽ bị tắt, không bị xóa)</h2>
            <ul>
                @foreach ($preview['deactivate'] as $question)
                    <li>{{ $question['code'] }} – {{ $question['text'] }}</li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
