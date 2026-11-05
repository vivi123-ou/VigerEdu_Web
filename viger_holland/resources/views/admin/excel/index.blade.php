@extends('layouts.admin', ['menuKey' => 'excel'])

@section('title', 'Xuất / nhập Excel')

@section('content')
    <p class="lead">Soạn câu hỏi bằng Excel: tải file về, sửa, rồi nhập lại. File có đúng các cột như tab CAU_HOI của Google Sheet mẫu.</p>

    <section class="card">
        <h2 class="card__title">1. Tải bộ câu hỏi</h2>
        <p class="muted">File gồm câu hỏi thông tin khách và câu hỏi Holland của bộ bạn chọn, kèm tab HUONG_DAN (hướng dẫn sửa file).</p>
        <ul class="history">
            @foreach ($sets as $set)
                <li class="history__item">
                    <span>{{ $set->name }} @if ($set->is_default)<span class="badge">Mặc định</span>@endif</span>
                    <a class="btn btn--small" href="{{ route('admin.excel.export', $set) }}">Tải bộ câu hỏi</a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="card">
        <h2 class="card__title">2. Nhập bộ câu hỏi</h2>
        <form method="post" action="{{ route('admin.excel.preview') }}" enctype="multipart/form-data" class="form">
            @csrf
            <div class="field">
                <label for="question_set_id">Nhập vào bộ</label>
                <select id="question_set_id" name="question_set_id">
                    @foreach ($sets as $set)
                        <option value="{{ $set->id }}" @selected((string) old('question_set_id') === (string) $set->id)>{{ $set->name }}</option>
                    @endforeach
                </select>
                <p class="field__hint">Câu hỏi Holland mới (ô Mã câu để trống) được thêm vào bộ này.</p>
                @error('question_set_id')<p class="field__error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="file">File Excel (.xlsx)</label>
                <input id="file" name="file" type="file" accept=".xlsx" required>
                @error('file')<p class="field__error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn--primary" type="submit">Xem trước</button>
            <p class="field__hint">Bấm "Xem trước" chưa ghi gì cả. Bạn sẽ thấy câu nào được thêm, sửa, tắt và dòng nào sai trước khi xác nhận.</p>
        </form>
    </section>

    <section class="card">
        <h2 class="card__title">Lịch sử nhập</h2>
        @if ($history->isEmpty())
            <p class="muted">Chưa nhập file nào.</p>
        @else
            <ul class="history">
                @foreach ($history as $activity)
                    <li class="history__item">
                        <div>
                            {{ $activity->description }}
                            <p class="muted history__meta">{{ $activity->created_at->format('H:i d/m/Y') }} · {{ $activity->user?->name ?? 'Hệ thống' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
