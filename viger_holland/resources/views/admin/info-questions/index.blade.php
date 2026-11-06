@extends('layouts.admin', ['menuKey' => 'info-form'])

@section('title', 'Form thông tin khách')

@section('content')
    <p class="lead">Khách điền form này ở bước cuối, sau khi trả lời xong câu hỏi Holland, để nhận kết quả. Câu trả lời được lưu vào hồ sơ khách (CRM). Ô đồng ý luôn ở cuối.</p>

    <div class="toolbar">
        <a class="btn" href="{{ route('test.show', ['xem_truoc' => 1]) }}" target="_blank" rel="noopener">Xem như khách</a>
    </div>

    <section class="qcards-wrap" data-sort-holder>
        <p class="muted">Bấm vào một câu để sửa, sửa đến đâu tự lưu đến đó. Kéo biểu tượng ⋮⋮ để đổi thứ tự.</p>

        <div class="qcards" data-sortable data-qcards>
            @foreach ($questions as $question)
                {!! \App\Http\Controllers\Admin\InfoQuestionController::cardHtml($question) !!}
            @endforeach
        </div>
        <form method="post" action="{{ route('admin.questions.reorder') }}" data-reorder-form hidden>@csrf</form>

        {{-- Thêm câu: gõ câu hỏi, chọn cách trả lời, Enter. Câu trả lời tự lưu vào hồ sơ khách. --}}
        <form method="post" action="{{ route('admin.info-questions.store') }}" class="qcard qcard--add" data-quick-add>
            @csrf
            <input type="hidden" name="is_active" value="1">
            <input type="hidden" name="quick_add" value="1">
            <input type="hidden" name="crm_target" value="{{ \App\Http\Requests\Admin\InfoQuestionRequest::AUTO }}">
            <div class="quick-add">
                <input name="text" required maxlength="500" placeholder="+ Gõ câu hỏi mới rồi nhấn Enter" aria-label="Câu hỏi mới">
                <select name="answer_type" aria-label="Khách trả lời bằng">
                    @foreach (\App\Enums\AnswerType::partAOptions() as $value => $label)
                        @continue($value === 'consent')
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn--primary" type="submit">Thêm câu</button>
            </div>
            <p class="field__hint">Câu "Chọn 1 / Chọn nhiều": thêm xong, bấm vào câu để nhập các lựa chọn.</p>
            <p class="field__error" data-quick-add-error hidden></p>
        </form>
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
@endsection
