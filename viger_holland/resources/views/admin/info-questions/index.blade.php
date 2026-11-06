@extends('layouts.admin', ['menuKey' => 'info-form'])

@section('title', 'Form thông tin khách')

@section('content')
    <p class="lead">Khách điền form này ở bước cuối (sau khi làm xong câu Holland) để nhận kết quả. Câu bắt buộc hiện trước; câu không bắt buộc nằm trong phần "Thêm thông tin" (thu gọn); ô đồng ý luôn ở cuối.</p>

    <div class="toolbar">
        <a class="btn btn--primary" href="{{ route('admin.info-questions.create') }}">+ Thêm câu</a>
        <a class="btn" href="{{ route('test.show', ['xem_truoc' => 1]) }}" target="_blank" rel="noopener">Xem như khách</a>
    </div>

    <section class="card">
        <p class="muted">Kéo biểu tượng ⋮⋮ để đổi thứ tự, thả ra là tự lưu.</p>
        <div class="table-wrap">
            <table class="table table--wrap">
                <thead>
                    <tr>
                        <th></th>
                        <th>Mã</th>
                        <th>Câu hỏi</th>
                        <th>Kiểu</th>
                        <th>Gửi sang CRM</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-sortable>
                    @foreach ($questions as $question)
                        <tr data-id="{{ $question->id }}" @class(['is-off' => ! $question->is_active])>
                            <td class="drag" draggable="true" title="Kéo để đổi thứ tự" aria-label="Kéo để đổi thứ tự">⋮⋮</td>
                            <td>{{ $question->code }}</td>
                            <td>
                                {{ $question->text }}
                                @if ($question->is_required)<span class="tag">Bắt buộc</span>@endif
                                @if (! empty($question->show_if['question']))<span class="tag">Chỉ hiện khi {{ $question->show_if['question'] }} = {{ implode(', ', $question->show_if['in'] ?? []) }}</span>@endif
                            </td>
                            <td>{{ $question->answer_type->label() }}</td>
                            <td>
                                @if ($question->lead_field)
                                    {{ \App\Support\LeadFields::label($question->lead_field) }} <span class="muted">({{ $question->lead_field }})</span>
                                @elseif ($question->extra_field_key)
                                    Trường tùy biến <span class="muted">(extra_fields.{{ $question->extra_field_key }})</span>
                                @else
                                    <span class="muted">Không gửi</span>
                                @endif
                            </td>
                            <td>
                                @if ($question->lead_field === 'consent')
                                    <span class="status status--ok">Luôn bật</span>
                                @else
                                    <form method="post" action="{{ route('admin.questions.toggle', $question) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" @class(['switch', 'is-on' => $question->is_active]) title="Bấm để {{ $question->is_active ? 'tắt' : 'bật' }}">{{ $question->is_active ? 'Đang bật' : 'Đang tắt' }}</button>
                                    </form>
                                @endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('admin.info-questions.edit', $question) }}">Sửa</a>
                                @if ($question->lead_field !== 'consent')
                                    <form method="post" action="{{ route('admin.questions.destroy', $question) }}" data-confirm="Đưa câu {{ $question->code }} vào thùng rác? Câu sẽ không còn trên form.">
                                        @csrf @method('DELETE')
                                        <button class="link-danger" type="submit">Xóa</button>
                                    </form>
                                @endif
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
@endsection
