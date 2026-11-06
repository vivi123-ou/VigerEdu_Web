{{-- Các ô của một câu Holland. Dùng ở trang sửa riêng và ở thẻ câu hỏi (danh sách kiểu Google Form).
     Biến: $question, $set, $uid ('page' = trang riêng, có old() và lỗi; khác = thẻ, lỗi trả về bằng JS) --}}
@php
    use App\Enums\AnswerType;
    use App\Models\Question;
    use App\Support\ChoiceList;

    $uid = $uid ?? 'new';
    $useOld = ($uid === 'page');
    $old = fn (string $key, mixed $default = null) => $useOld ? old($key, $default) : $default;
    $err = fn (string $key) => $useOld ? $errors->first($key) : null;
    $groups = collect(['R', 'I', 'A', 'S', 'E', 'C'])->mapWithKeys(fn ($g) => [$g => setting("group_name_{$g}", $g)]);
    $type = $old('answer_type', $question->answer_type?->value ?? $set->answer_type->value);
    $choices = array_values((array) $old('choices', $question->answer_type?->isChoice() ? $question->choices() : []));
    while (count($choices) < 2) {
        $choices[] = ['label' => '', 'group' => null, 'points' => null];
    }
    $scaleText = $old('options_text', $question->answer_type?->isChoice() ? '' : ChoiceList::format($question->options));
@endphp

<div class="field">
    <label for="text-{{ $uid }}">Câu hỏi</label>
    <textarea id="text-{{ $uid }}" name="text" rows="2" required maxlength="500" placeholder="Ví dụ: Tôi thích sửa chữa đồ dùng trong nhà">{{ $old('text', $question->text) }}</textarea>
    @if ($err('text'))<p class="field__error">{{ $err('text') }}</p>@endif
</div>

<div class="grid-2">
    <div class="field">
        <label for="type-{{ $uid }}">Khách trả lời bằng</label>
        <select id="type-{{ $uid }}" name="answer_type" data-answer-type>
            @foreach (AnswerType::hollandOptions() as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field" data-show-for="yes_no likert3 likert5 scale">
        <label for="group-{{ $uid }}">Câu này cộng điểm cho nhóm</label>
        <select id="group-{{ $uid }}" name="riasec">
            <option value="">— Chọn nhóm —</option>
            @foreach ($groups as $letter => $name)
                <option value="{{ $letter }}" @selected($old('riasec', $question->riasec) === $letter)>{{ $name }} ({{ $letter }})</option>
            @endforeach
        </select>
        @if ($err('riasec'))<p class="field__error">{{ $err('riasec') }}</p>@endif
    </div>
</div>

<div class="field" data-show-for="likert3 likert5 scale">
    <label for="display-{{ $uid }}">Hiện các mức dạng</label>
    <select id="display-{{ $uid }}" name="display">
        <option value="">Theo bộ câu ({{ ($set->display ?? 'buttons') === 'stars' ? 'ngôi sao' : 'ô số có chữ' }})</option>
        <option value="{{ Question::DISPLAY_BUTTONS }}" @selected($old('display', $question->display) === Question::DISPLAY_BUTTONS)>Ô số có chữ</option>
        <option value="{{ Question::DISPLAY_STARS }}" @selected($old('display', $question->display) === Question::DISPLAY_STARS)>Ngôi sao</option>
    </select>
</div>

<div class="field" data-show-for="scale">
    <label for="scale-{{ $uid }}">Các mức – mỗi dòng "Chữ = điểm"</label>
    <textarea id="scale-{{ $uid }}" name="options_text" rows="4" placeholder="Không thích = 0&#10;Bình thường = 1&#10;Rất thích = 2" data-scale-text>{{ $scaleText }}</textarea>
    @if ($err('options_text'))<p class="field__error">{{ $err('options_text') }}</p>@endif
</div>

<div class="field" data-show-for="single multi">
    <p class="field__label-text">Các lựa chọn</p>
    <p class="field__hint">Mỗi lựa chọn cộng điểm cho một nhóm (để trống điểm = 1). Chọn "Không tính điểm" nếu lựa chọn đó không thuộc nhóm nào.</p>
    @if ($err('choices'))<p class="field__error">{{ $err('choices') }}</p>@endif
    <table class="table table--form choices">
        <thead><tr><th>Lựa chọn khách thấy</th><th>Cộng điểm cho</th><th>Điểm</th><th></th></tr></thead>
        <tbody data-rows>
            @foreach ($choices as $index => $choice)
                @include('admin.holland-questions.choice-row', ['index' => $index, 'choice' => $choice, 'groups' => $groups])
            @endforeach
        </tbody>
    </table>
    <template data-row-template>
        @include('admin.holland-questions.choice-row', ['index' => '__i__', 'choice' => ['label' => '', 'group' => null, 'points' => null], 'groups' => $groups])
    </template>
    <button class="btn btn--small" type="button" data-add-row>+ Thêm lựa chọn</button>
</div>

<div class="grid-2" data-show-for="multi">
    <div class="field">
        <label for="min-{{ $uid }}">Chọn ít nhất (ô)</label>
        <input id="min-{{ $uid }}" name="min_select" type="number" min="0" max="30" value="{{ $old('min_select', $question->configValue('min_select')) }}" class="input--short" placeholder="Không giới hạn">
    </div>
    <div class="field">
        <label for="max-{{ $uid }}">Chọn nhiều nhất (ô)</label>
        <input id="max-{{ $uid }}" name="max_select" type="number" min="0" max="30" value="{{ $old('max_select', $question->configValue('max_select')) }}" class="input--short" placeholder="Không giới hạn">
        @if ($err('max_select'))<p class="field__error">{{ $err('max_select') }}</p>@endif
    </div>
</div>

<div class="check-row">
    <label class="check">
        <input type="checkbox" name="is_required" value="1" @checked($old('is_required', $question->is_required ?? true))>
        <span>Bắt buộc trả lời</span>
    </label>
    <label class="check">
        <input type="checkbox" name="is_active" value="1" @checked($old('is_active', $question->is_active ?? true))>
        <span>Hiện khi khách làm bài</span>
    </label>
</div>

<details class="advanced">
    <summary>Tùy chọn nâng cao (thường không cần sửa)</summary>

    <div class="field">
        <label for="help-{{ $uid }}">Chú thích nhỏ dưới câu hỏi</label>
        <input id="help-{{ $uid }}" name="help_text" value="{{ $old('help_text', $question->help_text) }}" maxlength="255">
    </div>

    <div class="field" data-show-for="likert3 likert5">
        <label for="scale-own-{{ $uid }}">Đổi chữ các mức cho riêng câu này – mỗi dòng "Chữ = điểm"</label>
        <textarea id="scale-own-{{ $uid }}" name="options_text" rows="4" placeholder="{{ ChoiceList::format($set->scaleOptions()) }}" data-scale-text>{{ $scaleText }}</textarea>
        <p class="field__hint">Để trống = dùng chữ chung của bộ ({{ collect($set->scaleOptions())->pluck('label')->implode(' / ') }}).</p>
    </div>

    <div class="field" data-show-for="yes_no likert3 likert5 scale">
        <label for="maxscore-{{ $uid }}">Điểm tối đa</label>
        <input id="maxscore-{{ $uid }}" name="max_score" value="{{ $old('max_score', $question->max_score) }}" class="input--short" inputmode="decimal">
        <p class="field__hint">Để trống = mức cao nhất. Chỉ đổi khi muốn câu này "nặng" hơn hoặc "nhẹ" hơn câu khác.</p>
        @if ($err('max_score'))<p class="field__error">{{ $err('max_score') }}</p>@endif
    </div>

    <div class="field">
        <label for="note-{{ $uid }}">Ghi chú nội bộ</label>
        <input id="note-{{ $uid }}" name="note" value="{{ $old('note', $question->note) }}" maxlength="255">
    </div>
</details>
