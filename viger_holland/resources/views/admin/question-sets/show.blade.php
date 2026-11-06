@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', $set->name)

@section('content')
    @php $groupNames = collect(['R', 'I', 'A', 'S', 'E', 'C'])->mapWithKeys(fn ($g) => [$g => setting("group_name_{$g}", $g)]); @endphp

    <p><a href="{{ route('admin.question-sets.index') }}">← Tất cả bộ câu</a></p>

    <div class="set-head card">
        <div>
            <p>
                <span @class(['status', 'status--ok' => $set->is_active, 'status--warn' => ! $set->is_active])>{{ $set->is_active ? 'Đang bật' : 'Đang tắt' }}</span>
                @if ($set->is_default)<span class="badge">Mặc định</span>@endif
                · {{ $set->per_page }} câu mỗi trang
            </p>
            @if (filled($set->description))<p class="muted">{{ $set->description }}</p>@endif
            <p class="group-counts">Số câu cộng điểm cho từng nhóm:
                @foreach ($groupNames as $letter => $name)
                    <span class="tag">{{ $name }}: {{ $groupCounts[$letter] ?? 0 }}</span>
                @endforeach
            </p>
            <p>Link làm bài: @include('admin.partials.copy-link', ['url' => $set->testUrl()])</p>
        </div>
        <div class="toolbar">
            <a class="btn" href="{{ route('test.show', ['bo' => $set->code, 'xem_truoc' => 1]) }}" target="_blank" rel="noopener">Xem như khách</a>
            <a class="btn" href="{{ route('admin.question-sets.edit', $set) }}">Cài đặt bộ</a>
            <details class="dropdown dropdown--right more-menu">
                <summary class="btn">Thêm ▾</summary>
                <div class="dropdown__menu">
                    <a href="{{ route('admin.excel.export', $set) }}">Tải Excel</a>
                    <form method="post" action="{{ route('admin.question-sets.duplicate', $set) }}" data-confirm="Tạo một bộ mới giống hệt bộ này (bộ mới sẽ đang tắt)?">
                        @csrf
                        <button type="submit">Nhân bản bộ câu</button>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <section class="qcards-wrap" data-sort-holder>
        <h2 class="card__title">Câu hỏi ({{ $questions->count() }})</h2>
        <p class="muted">Bấm vào một câu để sửa, sửa đến đâu tự lưu đến đó. Kéo biểu tượng ⋮⋮ để đổi thứ tự.</p>

        <div class="qcards" data-sortable data-qcards>
            @foreach ($questions as $question)
                {!! \App\Http\Controllers\Admin\HollandQuestionController::cardHtml($question) !!}
            @endforeach
        </div>
        <form method="post" action="{{ route('admin.questions.reorder') }}" data-reorder-form hidden>@csrf</form>

        {{-- Thêm câu: gõ câu hỏi, chọn nhóm, Enter. Đổi kiểu câu (chọn 1, chọn nhiều…) trong thẻ vừa thêm. --}}
        <form method="post" action="{{ route('admin.question-sets.questions.store', $set) }}" class="qcard qcard--add" data-quick-add>
            @csrf
            <input type="hidden" name="answer_type" value="{{ $set->answer_type->value }}">
            <input type="hidden" name="is_active" value="1">
            <input type="hidden" name="is_required" value="1">
            <div class="quick-add">
                <input name="text" required maxlength="500" placeholder="+ Gõ câu hỏi mới rồi nhấn Enter" aria-label="Câu hỏi mới">
                <select name="riasec" required aria-label="Câu này cộng điểm cho nhóm">
                    <option value="">Cộng điểm cho nhóm…</option>
                    @foreach ($groupNames as $letter => $name)
                        <option value="{{ $letter }}">{{ $name }} ({{ $letter }})</option>
                    @endforeach
                </select>
                <button class="btn btn--primary" type="submit">Thêm câu</button>
            </div>
            <p class="field__error" data-quick-add-error hidden></p>
        </form>

        <details class="bulk" @if ($errors->has('lines')) open @endif>
            <summary class="btn btn--small">Dán nhiều câu cùng lúc</summary>
            <form method="post" action="{{ route('admin.question-sets.questions.bulk', $set) }}" class="card form bulk__form">
                @csrf
                <div class="field">
                    <label for="lines">Mỗi dòng một câu</label>
                    <textarea id="lines" name="lines" rows="8" placeholder="R | Tôi thích sửa chữa đồ dùng trong nhà&#10;I | Tôi thích tìm hiểu vì sao mọi việc xảy ra">{{ old('lines') }}</textarea>
                    <p class="field__hint">Đầu dòng ghi chữ nhóm và dấu | (ví dụ "R | …"), hoặc chọn một nhóm chung bên dưới. Dán được từ Excel / Word.</p>
                    @foreach ($errors->get('lines') as $message)
                        <p class="field__error">{{ $message }}</p>
                    @endforeach
                </div>
                <div class="field">
                    <label for="bulk-riasec">Nhóm cho các dòng không ghi nhóm</label>
                    <select id="bulk-riasec" name="riasec">
                        <option value="">— Không chọn —</option>
                        @foreach ($groupNames as $letter => $name)
                            <option value="{{ $letter }}" @selected(old('riasec') === $letter)>{{ $name }} ({{ $letter }})</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn--primary" type="submit">Thêm các câu này</button>
            </form>
        </details>
    </section>

    @if ($trashed->isNotEmpty())
        <section class="card">
            <h2 class="card__title">Thùng rác</h2>
            <ul class="history">
                @foreach ($trashed as $question)
                    <li class="history__item">
                        <span>{{ $question->text }} <span class="muted">· xóa lúc {{ $question->deleted_at->format('H:i d/m/Y') }}</span></span>
                        <form method="post" action="{{ route('admin.questions.restore', $question->id) }}">
                            @csrf
                            <button class="btn btn--small" type="submit">Khôi phục</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @include('admin.partials.history', ['activities' => $activities])
@endsection
