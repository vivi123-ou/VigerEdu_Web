@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', $question->exists ? 'Sửa câu '.$question->code : 'Thêm câu vào bộ "'.$set->name.'"')

@section('content')
    <p><a href="{{ route('admin.question-sets.show', $set) }}">← Bộ "{{ $set->name }}"</a></p>

    <form method="post" action="{{ $question->exists ? route('admin.holland-questions.update', $question) : route('admin.question-sets.questions.store', $set) }}" class="card form">
        @csrf
        @if ($question->exists) @method('PUT') @endif

        <div class="field">
            <label for="text">Nội dung câu hỏi</label>
            <textarea id="text" name="text" rows="3" required maxlength="500" @error('text') aria-invalid="true" @enderror>{{ old('text', $question->text) }}</textarea>
            <p class="field__hint">Viết ngắn, một ý. Ví dụ: "Tôi thích sửa chữa đồ dùng trong nhà".</p>
            @error('text')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <fieldset class="field">
            <legend>Nhóm Holland</legend>
            <div class="radio-grid">
                @foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $letter)
                    <label class="check">
                        <input type="radio" name="riasec" value="{{ $letter }}" @checked(old('riasec', $question->riasec) === $letter) required>
                        <span><strong>{{ $letter }}</strong> – {{ setting("group_name_{$letter}", $letter) }}</span>
                    </label>
                @endforeach
            </div>
            <p class="field__hint">Câu trả lời "đúng" của câu này cộng điểm cho nhóm được chọn.</p>
            @error('riasec')<p class="field__error">{{ $message }}</p>@enderror
        </fieldset>

        <details class="advanced" @if ($errors->hasAny(['options_text', 'max_score', 'form', 'sort_order'])) open @endif>
            <summary>Tùy chọn nâng cao (thường không cần sửa)</summary>

            <div class="field">
                <label for="form">Đầu câu (không bắt buộc)</label>
                <input id="form" name="form" value="{{ old('form', $question->form) }}" maxlength="50">
                <p class="field__hint">Ví dụ: "Tôi là người…", "Tôi có thể…". Dùng để nhóm câu khi soạn, khách không thấy.</p>
            </div>

            <div class="field">
                <label for="options_text">Thang trả lời riêng của câu này</label>
                <textarea id="options_text" name="options_text" rows="3" placeholder="{{ \App\Support\ChoiceList::format($set->scaleOptions()) }}">{{ old('options_text', \App\Support\ChoiceList::format($question->options)) }}</textarea>
                <p class="field__hint">Để trống = theo thang của bộ ({{ collect($set->scaleOptions())->map(fn ($o) => $o['label'].' = '.$o['value'])->implode(', ') }}). Mỗi dòng "Nhãn = điểm".</p>
                @error('options_text')<p class="field__error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="max_score">Điểm tối đa</label>
                <input id="max_score" name="max_score" value="{{ old('max_score', $question->max_score) }}" class="input--short" inputmode="decimal">
                <p class="field__hint">Để trống = điểm cao nhất của thang. Chỉ đổi khi muốn câu này "nặng" hơn hoặc "nhẹ" hơn.</p>
                @error('max_score')<p class="field__error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="sort_order">Thứ tự</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $question->sort_order) }}" class="input--short">
                <p class="field__hint">Số nhỏ hiện trước. Dễ hơn: kéo thả ở danh sách câu.</p>
            </div>

            <div class="field">
                <label for="note">Ghi chú nội bộ</label>
                <input id="note" name="note" value="{{ old('note', $question->note) }}" maxlength="255">
            </div>
        </details>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true))>
            <span>Bật câu này (khách thấy khi làm bài)</span>
        </label>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Lưu</button>
            @unless ($question->exists)
                <button class="btn" type="submit" name="add_another" value="1">Lưu và thêm câu khác</button>
            @endunless
            <a class="btn btn--plain" href="{{ route('admin.question-sets.show', $set) }}">Hủy</a>
        </div>
    </form>

    @if ($question->exists)
        @include('admin.partials.history', ['activities' => $activities])
    @endif
@endsection
