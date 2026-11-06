{{-- Các ô của một câu thông tin khách. Dùng ở trang sửa riêng và ở thẻ câu hỏi (danh sách kiểu Google Form).
     Biến: $question, $conditionQuestions, $uid (hậu tố id để không trùng khi nhiều thẻ trên một trang) --}}
@php
    use App\Enums\AnswerType;
    use App\Http\Requests\Admin\InfoQuestionRequest;
    use App\Support\ChoiceList;
    use App\Support\LeadFields;

    $uid = $uid ?? 'new';
    $isConsent = $question->lead_field === 'consent';
    $useOld = ($uid === 'page');
    $old = fn (string $key, mixed $default = null) => $useOld ? old($key, $default) : $default;
    $currentTarget = $question->lead_field ?? ($question->extra_field_key ? InfoQuestionRequest::CUSTOM : ($question->exists ? '' : InfoQuestionRequest::AUTO));
    $target = $old('crm_target', $currentTarget);
    $type = $old('answer_type', $question->answer_type?->value);
    $conditionQuestion = $old('show_if_question', $question->show_if['question'] ?? '');
    $conditionValues = array_map('strval', (array) $old('show_if_values', $question->show_if['in'] ?? []));
    $err = fn (string $key) => $useOld ? $errors->first($key) : null;
@endphp

<div class="field">
    <label for="text-{{ $uid }}">Câu hỏi</label>
    <textarea id="text-{{ $uid }}" name="text" rows="2" required maxlength="500">{{ $old('text', $question->text) }}</textarea>
    @if ($err('text'))<p class="field__error">{{ $err('text') }}</p>@endif
</div>

@if ($isConsent)
    <input type="hidden" name="answer_type" value="consent">
    <input type="hidden" name="is_active" value="1">
    <input type="hidden" name="is_required" value="1">
    <p class="muted">Ô đồng ý lưu thông tin: luôn bật, luôn bắt buộc, luôn ở cuối form. Chỉ sửa được câu chữ.</p>
@else
    <div class="field">
        <label for="type-{{ $uid }}">Khách trả lời bằng</label>
        <select id="type-{{ $uid }}" name="answer_type" data-answer-type>
            @foreach (AnswerType::partAOptions() as $value => $label)
                @continue($value === 'consent')
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="field" data-show-for="single multi rating">
        <label for="options-{{ $uid }}">Các lựa chọn – mỗi dòng một lựa chọn</label>
        <textarea id="options-{{ $uid }}" name="options_text" rows="5" placeholder="Lớp 10&#10;Lớp 11&#10;Lớp 12">{{ $old('options_text', ChoiceList::format($question->options)) }}</textarea>
        @if ($err('options_text'))<p class="field__error">{{ $err('options_text') }}</p>@endif
    </div>

    <label class="check" data-show-for="single multi">
        <input type="checkbox" name="allow_other" value="1" @checked($old('allow_other', $question->configValue('allow_other')))>
        <span>Thêm lựa chọn "Khác" để khách tự ghi</span>
    </label>

    <div class="check-row">
        <label class="check">
            <input type="checkbox" name="is_required" value="1" @checked($old('is_required', $question->is_required))>
            <span>Bắt buộc trả lời</span>
        </label>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked($old('is_active', $question->is_active ?? true))>
            <span>Hiện trên form</span>
        </label>
    </div>
@endif

<details class="advanced" @if ($useOld && $errors->hasAny(['crm_target', 'extra_field_key', 'show_if_question', 'show_if_values', 'max'])) open @endif>
    <summary>Tùy chọn nâng cao (thường không cần sửa)</summary>

    <div class="field">
        <label for="help-{{ $uid }}">Chú thích nhỏ dưới câu hỏi</label>
        <input id="help-{{ $uid }}" name="help_text" value="{{ $old('help_text', $question->help_text) }}" maxlength="255" placeholder="Ví dụ: Kết quả sẽ được gửi vào email này.">
    </div>

    @unless ($isConsent)
        <div class="field" data-show-for="text textarea number phone email date">
            <label for="placeholder-{{ $uid }}">Chữ mờ gợi ý trong ô</label>
            <input id="placeholder-{{ $uid }}" name="placeholder" value="{{ $old('placeholder', $question->placeholder) }}" maxlength="255" placeholder="Ví dụ: 0912 345 678">
        </div>

        <div class="field" data-show-for="single">
            <label for="display-single-{{ $uid }}">Cách hiện các lựa chọn</label>
            <select id="display-single-{{ $uid }}" name="display">
                <option value="">Tự động (ít lựa chọn thì hiện nút, nhiều thì hiện danh sách)</option>
                <option value="buttons" @selected($old('display', $question->display) === 'buttons')>Nút bấm</option>
                <option value="dropdown" @selected($old('display', $question->display) === 'dropdown')>Danh sách sổ xuống</option>
            </select>
        </div>

        <div class="field" data-show-for="rating">
            <label for="display-rating-{{ $uid }}">Cách hiện</label>
            <select id="display-rating-{{ $uid }}" name="display">
                <option value="stars" @selected($old('display', $question->display ?? 'stars') === 'stars')>Ngôi sao</option>
                <option value="buttons" @selected($old('display', $question->display) === 'buttons')>Nút bấm có chữ</option>
            </select>
        </div>

        <div class="grid-2" data-show-for="number">
            <div class="field">
                <label for="min-{{ $uid }}">Số nhỏ nhất</label>
                <input id="min-{{ $uid }}" name="min" value="{{ $old('min', $question->configValue('min')) }}" inputmode="decimal" class="input--short">
            </div>
            <div class="field">
                <label for="max-{{ $uid }}">Số lớn nhất</label>
                <input id="max-{{ $uid }}" name="max" value="{{ $old('max', $question->configValue('max')) }}" inputmode="decimal" class="input--short">
                @if ($err('max'))<p class="field__error">{{ $err('max') }}</p>@endif
            </div>
        </div>

        <div class="field">
            <label for="target-{{ $uid }}">Lưu câu trả lời vào hồ sơ khách (CRM) ở mục</label>
            <select id="target-{{ $uid }}" name="crm_target" data-crm-target>
                @unless ($question->exists && $currentTarget === InfoQuestionRequest::CUSTOM)
                    <option value="{{ InfoQuestionRequest::AUTO }}" @selected($target === InfoQuestionRequest::AUTO)>Tự động tạo mục mới theo câu hỏi (nên chọn)</option>
                @endunless
                <optgroup label="Mục có sẵn trong hồ sơ khách">
                    @foreach (LeadFields::LABELS as $field => $label)
                        @continue($field === 'consent')
                        <option value="{{ $field }}" @selected($target === $field)>{{ $label }}</option>
                    @endforeach
                </optgroup>
                <option value="{{ InfoQuestionRequest::CUSTOM }}" @selected($target === InfoQuestionRequest::CUSTOM)>Mục riêng, tự đặt mã…</option>
                <option value="" @selected($target === '')>Không lưu vào hồ sơ khách</option>
            </select>
            @if ($err('crm_target'))<p class="field__error">{{ $err('crm_target') }}</p>@endif

            <div class="field--nested" data-show-for-target="{{ InfoQuestionRequest::CUSTOM }}">
                <label for="key-{{ $uid }}">Mã mục (chữ thường không dấu, ví dụ truong_chuyen)</label>
                <input id="key-{{ $uid }}" name="extra_field_key" value="{{ $old('extra_field_key', $question->extra_field_key) }}" maxlength="50">
                @if ($err('extra_field_key'))<p class="field__error">{{ $err('extra_field_key') }}</p>@endif
            </div>
        </div>

        <div class="field">
            <label for="cond-{{ $uid }}">Chỉ hỏi khi…</label>
            <select id="cond-{{ $uid }}" name="show_if_question" data-condition-question>
                <option value="">Luôn hỏi</option>
                @foreach ($conditionQuestions as $condition)
                    @continue($condition->id === $question->id)
                    <option value="{{ $condition->code }}" @selected($conditionQuestion === $condition->code)>{{ $condition->text }}</option>
                @endforeach
            </select>
            @foreach ($conditionQuestions as $condition)
                @continue($condition->id === $question->id)
                @php
                    $choices = $condition->answer_type === AnswerType::Boolean
                        ? [['value' => '1', 'label' => setting('form_yes', 'Có')], ['value' => '0', 'label' => setting('form_no', 'Không')]]
                        : ($condition->options ?? []);
                @endphp
                <div class="field--nested condition-values" data-condition-for="{{ $condition->code }}" @if ($conditionQuestion !== $condition->code) hidden @endif>
                    <p class="field__hint">…khách trả lời là:</p>
                    @foreach ($choices as $choice)
                        <label class="check">
                            <input type="checkbox" name="show_if_values[]" value="{{ $choice['value'] }}"
                                   @checked($conditionQuestion === $condition->code && in_array((string) $choice['value'], $conditionValues, true))
                                   @disabled($conditionQuestion !== $condition->code)>
                            <span>{{ $choice['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
            <p class="field__hint">Ví dụ: chỉ hỏi "Họ tên phụ huynh" khi người làm là học sinh.</p>
        </div>

        <label class="check">
            <input type="checkbox" name="is_private" value="1" @checked($old('is_private', $question->configValue('private')))>
            <span>Thông tin riêng tư – chỉ xem ở CRM, không hiện ở trang Lượt làm bài (số điện thoại, email luôn riêng tư)</span>
        </label>
    @endunless

    <div class="field">
        <label for="note-{{ $uid }}">Ghi chú nội bộ</label>
        <input id="note-{{ $uid }}" name="note" value="{{ $old('note', $question->note) }}" maxlength="255">
    </div>
</details>
