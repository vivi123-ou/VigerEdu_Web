@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', $question->exists ? 'Sửa câu '.$question->code : 'Thêm câu vào bộ "'.$set->name.'"')

@php
    use App\Enums\AnswerType;
    use App\Models\Question;
    use App\Support\ChoiceList;

    $groups = collect(['R', 'I', 'A', 'S', 'E', 'C'])->mapWithKeys(fn ($g) => [$g => setting("group_name_{$g}", $g)]);
    $type = old('answer_type', $question->answer_type?->value ?? $set->answer_type->value);
    $choices = old('choices', $question->answer_type?->isChoice() ? $question->choices() : []);
    $choices = array_values($choices);
    while (count($choices) < 3) {
        $choices[] = ['label' => '', 'group' => null, 'points' => null];
    }
    $scaleText = old('options_text', $question->answer_type?->isChoice() ? '' : ChoiceList::format($question->options));
@endphp

@section('content')
    <p><a href="{{ route('admin.question-sets.show', $set) }}">← Bộ "{{ $set->name }}"</a></p>

    <form method="post" action="{{ $question->exists ? route('admin.holland-questions.update', $question) : route('admin.question-sets.questions.store', $set) }}" class="card form" data-type-form>
        @csrf
        @if ($question->exists) @method('PUT') @endif

        <div class="field">
            <label for="text">Câu hỏi</label>
            <textarea id="text" name="text" rows="2" required maxlength="500" @error('text') aria-invalid="true" @enderror>{{ old('text', $question->text) }}</textarea>
            <p class="field__hint">Viết ngắn, một ý. Ví dụ: "Tôi thích sửa chữa đồ dùng trong nhà" hoặc "Bạn thích hoạt động nào nhất?"</p>
            @error('text')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="answer_type">Kiểu câu</label>
            <select id="answer_type" name="answer_type" data-answer-type>
                @foreach (AnswerType::hollandOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="field__hint">
                Đúng / Không, thang mức: câu cộng điểm cho <strong>một nhóm</strong>.
                Chọn 1 / Chọn nhiều: <strong>mỗi lựa chọn</strong> cộng điểm cho nhóm của nó (ví dụ "Sửa xe" → R, "Vẽ tranh" → A).
            </p>
            @error('answer_type')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        {{-- Câu một nhóm: Đúng/Không và thang mức --}}
        <fieldset class="field" data-show-for="yes_no likert3 likert5 scale">
            <legend>Nhóm Holland</legend>
            <div class="radio-grid">
                @foreach ($groups as $letter => $name)
                    <label class="check">
                        <input type="radio" name="riasec" value="{{ $letter }}" @checked(old('riasec', $question->riasec) === $letter)>
                        <span><strong>{{ $letter }}</strong> – {{ $name }}</span>
                    </label>
                @endforeach
            </div>
            <p class="field__hint">Câu trả lời càng "đúng" càng cộng nhiều điểm cho nhóm này.</p>
            @error('riasec')<p class="field__error">{{ $message }}</p>@enderror
        </fieldset>

        <div class="field" data-show-for="likert3 likert5 scale">
            <label for="display-scale">Hiện dạng</label>
            <select id="display-scale" name="display" data-display-for="likert3 likert5 scale">
                <option value="">Theo bộ câu ({{ ($set->display ?? 'buttons') === 'stars' ? 'ngôi sao' : 'ô số có nhãn' }})</option>
                <option value="{{ Question::DISPLAY_BUTTONS }}" @selected(old('display', $question->display) === Question::DISPLAY_BUTTONS)>Ô số có nhãn</option>
                <option value="{{ Question::DISPLAY_STARS }}" @selected(old('display', $question->display) === Question::DISPLAY_STARS)>Ngôi sao</option>
            </select>
        </div>

        <div class="field" data-show-for="likert3 likert5 scale">
            <label for="options_text">Các mức (mỗi dòng "Nhãn = điểm")</label>
            <textarea id="options_text" name="options_text" rows="5" placeholder="Không thích = 0&#10;Bình thường = 1&#10;Rất thích = 2" @error('options_text') aria-invalid="true" @enderror>{{ $scaleText }}</textarea>
            <p class="field__hint">Thang 3 / 5 mức: để trống = theo thang của bộ ({{ collect($set->scaleOptions())->map(fn ($o) => $o['label'].' = '.$o['value'])->implode(', ') }}). "Thang mức tự đặt" thì bắt buộc nhập, bao nhiêu mức cũng được.</p>
            @error('options_text')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        {{-- Câu chọn 1 / chọn nhiều --}}
        <fieldset class="field" data-show-for="single multi">
            <legend>Các lựa chọn</legend>
            <p class="field__hint">Mỗi lựa chọn gắn một nhóm Holland và số điểm (để trống điểm = 1). Lựa chọn không gắn nhóm thì không tính điểm. Để trống ô chữ để bỏ dòng.</p>
            @error('choices')<p class="field__error">{{ $message }}</p>@enderror
            <table class="table table--form choices">
                <thead><tr><th>Lựa chọn khách thấy</th><th>Cộng cho nhóm</th><th>Điểm</th><th></th></tr></thead>
                <tbody data-choices>
                    @foreach ($choices as $index => $choice)
                        @include('admin.holland-questions.choice-row', ['index' => $index, 'choice' => $choice, 'groups' => $groups])
                    @endforeach
                </tbody>
            </table>
            <template data-choice-template>
                @include('admin.holland-questions.choice-row', ['index' => '__i__', 'choice' => ['label' => '', 'group' => null, 'points' => null], 'groups' => $groups])
            </template>
            <button class="btn btn--small" type="button" data-add-choice>+ Thêm lựa chọn</button>
        </fieldset>

        <div class="field" data-show-for="single">
            <label for="display-single">Hiện dạng</label>
            <select id="display-single" name="display" data-display-for="single">
                <option value="">Danh sách nút bấm</option>
                <option value="{{ Question::DISPLAY_DROPDOWN }}" @selected(old('display', $question->display) === Question::DISPLAY_DROPDOWN)>Danh sách sổ xuống (khi có nhiều lựa chọn)</option>
            </select>
        </div>

        <div class="grid-2" data-show-for="multi">
            <div class="field">
                <label for="min_select">Chọn ít nhất (ô)</label>
                <input id="min_select" name="min_select" type="number" min="0" max="30" value="{{ old('min_select', $question->configValue('min_select')) }}" class="input--short">
                <p class="field__hint">Để trống = không giới hạn.</p>
            </div>
            <div class="field">
                <label for="max_select">Chọn tối đa (ô)</label>
                <input id="max_select" name="max_select" type="number" min="0" max="30" value="{{ old('max_select', $question->configValue('max_select')) }}" class="input--short">
                @error('max_select')<p class="field__error">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="check">
            <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $question->is_required ?? true))>
            <span>Bắt buộc trả lời</span>
        </label>

        <details class="advanced" @if ($errors->hasAny(['max_score', 'form', 'sort_order', 'help_text'])) open @endif>
            <summary>Tùy chọn nâng cao (thường không cần sửa)</summary>

            <div class="field">
                <label for="help_text">Chú thích dưới câu hỏi</label>
                <input id="help_text" name="help_text" value="{{ old('help_text', $question->help_text) }}" maxlength="255">
            </div>

            <div class="field" data-show-for="yes_no likert3 likert5 scale">
                <label for="max_score">Điểm tối đa</label>
                <input id="max_score" name="max_score" value="{{ old('max_score', $question->max_score) }}" class="input--short" inputmode="decimal">
                <p class="field__hint">Để trống = điểm cao nhất của thang. Chỉ đổi khi muốn câu này "nặng" hơn hoặc "nhẹ" hơn.</p>
                @error('max_score')<p class="field__error">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="form">Đầu câu (không bắt buộc)</label>
                <input id="form" name="form" value="{{ old('form', $question->form) }}" maxlength="50">
                <p class="field__hint">Ví dụ: "Tôi là người…". Dùng để nhóm câu khi soạn, khách không thấy.</p>
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
