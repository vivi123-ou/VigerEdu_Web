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
                · {{ $set->answer_type->label() }}: {{ collect($set->scaleOptions())->pluck('label')->implode(' / ') }} · {{ $set->per_page }} câu mỗi trang
            </p>
            @if (filled($set->description))<p class="muted">{{ $set->description }}</p>@endif
            <p class="group-counts">Câu đang bật theo nhóm:
                @foreach ($groupNames as $letter => $name)
                    <span class="tag" title="{{ $name }}">{{ $letter }}: {{ $groupCounts[$letter] ?? 0 }}</span>
                @endforeach
            </p>
            <p>Link làm bài: @include('admin.partials.copy-link', ['url' => $set->testUrl()])</p>
        </div>
        <div class="toolbar">
            <a class="btn" href="{{ route('admin.question-sets.edit', $set) }}">Sửa thông tin bộ</a>
            <a class="btn" href="{{ route('test.show', ['bo' => $set->code, 'xem_truoc' => 1]) }}" target="_blank" rel="noopener">Xem như khách</a>
            <a class="btn" href="{{ route('admin.excel.export', $set) }}">Tải Excel</a>
            <form method="post" action="{{ route('admin.question-sets.duplicate', $set) }}" data-confirm="Tạo một bộ mới giống hệt bộ này (bộ mới sẽ đang tắt)?">
                @csrf
                <button class="btn" type="submit">Nhân bản</button>
            </form>
        </div>
    </div>

    <div class="toolbar">
        <a class="btn btn--primary" href="{{ route('admin.question-sets.questions.create', $set) }}">+ Thêm câu</a>
        <details class="bulk" @if ($errors->has('lines')) open @endif>
            <summary class="btn">Dán nhiều câu</summary>
            <form method="post" action="{{ route('admin.question-sets.questions.bulk', $set) }}" class="card form bulk__form">
                @csrf
                <div class="field">
                    <label for="lines">Mỗi dòng một câu</label>
                    <textarea id="lines" name="lines" rows="8" placeholder="R | Tôi thích sửa chữa đồ dùng trong nhà&#10;I | Tôi thích tìm hiểu vì sao mọi việc xảy ra">{{ old('lines') }}</textarea>
                    <p class="field__hint">Đầu dòng ghi chữ nhóm và dấu | (ví dụ "R | …"). Dòng không ghi nhóm sẽ dùng nhóm chọn bên dưới. Mã câu tự đặt. Dán được từ Excel / Word.</p>
                    @foreach ($errors->get('lines') as $message)
                        <p class="field__error">{{ $message }}</p>
                    @endforeach
                </div>
                <div class="field">
                    <label for="bulk-riasec">Nhóm cho các câu không ghi nhóm</label>
                    <select id="bulk-riasec" name="riasec">
                        <option value="">— Không chọn —</option>
                        @foreach ($groupNames as $letter => $name)
                            <option value="{{ $letter }}" @selected(old('riasec') === $letter)>{{ $letter }} – {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn--primary" type="submit">Thêm các câu này</button>
            </form>
        </details>
    </div>

    <section class="card">
        <h2 class="card__title">Câu hỏi ({{ $questions->count() }})</h2>
        @if ($questions->isEmpty())
            <p class="muted">Bộ này chưa có câu nào. Bấm "+ Thêm câu" hoặc "Dán nhiều câu".</p>
        @else
            <p class="muted">Kéo biểu tượng ⋮⋮ để đổi thứ tự rồi bấm "Lưu thứ tự".</p>
            <div class="table-wrap">
                <table class="table table--wrap">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Mã</th>
                            <th>Câu hỏi</th>
                            <th>Nhóm</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody data-sortable>
                        @foreach ($questions as $question)
                            <tr data-id="{{ $question->id }}" @class(['is-off' => ! $question->is_active])>
                                <td class="drag" draggable="true" title="Kéo để đổi thứ tự" aria-label="Kéo để đổi thứ tự">⋮⋮</td>
                                <td>{{ $question->code }}</td>
                                <td>{{ $question->text }} @if (! empty($question->options))<span class="tag">Thang riêng</span>@endif</td>
                                <td title="{{ $groupNames[$question->riasec] ?? '' }}">{{ $question->riasec }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.questions.toggle', $question) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" @class(['switch', 'is-on' => $question->is_active]) title="Bấm để {{ $question->is_active ? 'tắt' : 'bật' }}">{{ $question->is_active ? 'Đang bật' : 'Đang tắt' }}</button>
                                    </form>
                                </td>
                                <td class="actions">
                                    <a href="{{ route('admin.holland-questions.edit', $question) }}">Sửa</a>
                                    <form method="post" action="{{ route('admin.questions.destroy', $question) }}" data-confirm="Đưa câu {{ $question->code }} vào thùng rác?">
                                        @csrf @method('DELETE')
                                        <button class="link-danger" type="submit">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form method="post" action="{{ route('admin.questions.reorder') }}" data-reorder-form hidden>
                @csrf
                <button class="btn btn--primary" type="submit">Lưu thứ tự</button>
                <span class="muted">Thứ tự đã thay đổi, chưa lưu.</span>
            </form>
        @endif
    </section>

    @if ($trashed->isNotEmpty())
        <section class="card">
            <h2 class="card__title">Thùng rác</h2>
            <ul class="history">
                @foreach ($trashed as $question)
                    <li class="history__item">
                        <span>{{ $question->code }} – {{ $question->text }} <span class="muted">· xóa lúc {{ $question->deleted_at->format('H:i d/m/Y') }}</span></span>
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
