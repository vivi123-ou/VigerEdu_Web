{{-- Một câu thông tin khách, hiển thị theo kiểu trả lời. Câu có điều kiện (show_if) được JS ẩn/hiện. --}}
@php
    use App\Enums\AnswerType;
    use App\Models\Question;

    $key = 'info.'.$question->code;
    $inputName = 'info['.$question->code.']';
    $id = 'f-'.$question->code;
    $value = old($key);
    $oldValues = array_map('strval', (array) $value);
    $options = $question->choices();
    $type = $question->answer_type;
    $hasError = $errors->has($key) || $errors->has($key.'.*');
    // Chọn 1: admin chọn nút / danh sách; không chọn thì tự động (≤ 4 lựa chọn hiện nút).
    $singleAsButtons = $type === AnswerType::Single && ($question->display === Question::DISPLAY_BUTTONS
        || (blank($question->display) && count($options) <= 4 && ! $question->allowsOther()));
    $otherName = 'info_other['.$question->code.']';
@endphp

<div @class(['field', 'field--wide' => in_array($type, [AnswerType::Consent, AnswerType::Multi, AnswerType::Textarea, AnswerType::Rating], true) || $singleAsButtons || $question->allowsOther(), 'has-error' => $hasError])
     data-field="{{ $question->code }}"
     @if (! empty($question->show_if['question'])) data-show-if='@json($question->show_if)' @endif>

    @if ($type === AnswerType::Consent)
        <label class="check">
            <input type="checkbox" id="{{ $id }}" name="{{ $inputName }}" value="1" @checked(old($key)) required>
            <span>{{ $question->text }}</span>
        </label>
    @elseif ($singleAsButtons || $type === AnswerType::Multi || ($type === AnswerType::Single && $question->allowsOther()))
        <fieldset class="choice">
            <legend class="field__label">{{ $question->text }} @if ($question->is_required)<span class="req" aria-hidden="true">*</span>@endif</legend>
            <div class="choice__list">
                @foreach ($options as $option)
                    <label class="choice__item">
                        <input type="{{ $type === AnswerType::Multi ? 'checkbox' : 'radio' }}" name="{{ $type === AnswerType::Multi ? $inputName.'[]' : $inputName }}"
                               value="{{ $option['value'] }}" @checked(in_array((string) $option['value'], $oldValues, true))>
                        <span>{{ $option['label'] }}</span>
                    </label>
                @endforeach
                @if ($question->allowsOther())
                    <label class="choice__item">
                        <input type="{{ $type === AnswerType::Multi ? 'checkbox' : 'radio' }}" name="{{ $type === AnswerType::Multi ? $inputName.'[]' : $inputName }}"
                               value="{{ Question::OTHER }}" @checked(in_array(Question::OTHER, $oldValues, true))>
                        <span>{{ setting('form_other_label', 'Khác') }}</span>
                    </label>
                @endif
            </div>
            @if ($question->allowsOther())
                <input class="other-input" name="{{ $otherName }}" value="{{ old('info_other.'.$question->code) }}" maxlength="255"
                       placeholder="{{ setting('form_other_placeholder', 'Ghi rõ nếu chọn "Khác"') }}" aria-label="{{ $question->text }} – {{ setting('form_other_label', 'Khác') }}">
            @endif
        </fieldset>
    @elseif ($type === AnswerType::Rating)
        @php $ascending = collect($question->scale())->sortBy('value')->values(); @endphp
        <fieldset class="choice">
            <legend class="field__label">{{ $question->text }} @if ($question->is_required)<span class="req" aria-hidden="true">*</span>@endif</legend>
            @if ($question->displayStyle() === Question::DISPLAY_STARS)
                <div class="stars">
                    @foreach ($ascending->reverse() as $option)
                        @php $starId = $id.'-'.$loop->remaining; @endphp
                        <input type="radio" id="{{ $starId }}" name="{{ $inputName }}" value="{{ $option['value'] }}" @checked((string) $value === (string) $option['value'])>
                        <label for="{{ $starId }}" title="{{ $option['label'] }}"><span aria-hidden="true">★</span><span class="sr-only">{{ $option['label'] }}</span></label>
                    @endforeach
                </div>
            @else
                <div class="choice__list">
                    @foreach ($ascending as $option)
                        <label class="choice__item">
                            <input type="radio" name="{{ $inputName }}" value="{{ $option['value'] }}" @checked((string) $value === (string) $option['value'])>
                            <span>{{ $option['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </fieldset>
    @else
        <label class="field__label" for="{{ $id }}">{{ $question->text }} @if ($question->is_required)<span class="req" aria-hidden="true">*</span>@endif</label>

        @if ($type === AnswerType::Single)
            <select id="{{ $id }}" name="{{ $inputName }}" @if ($question->is_required) required @endif>
                <option value="">{{ setting('form_select_placeholder', '— Chọn —') }}</option>
                @foreach ($options as $option)
                    <option value="{{ $option['value'] }}" @selected((string) $value === (string) $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @elseif ($type === AnswerType::Boolean)
            <select id="{{ $id }}" name="{{ $inputName }}">
                <option value="">{{ setting('form_select_placeholder', '— Chọn —') }}</option>
                <option value="1" @selected((string) $value === '1')>{{ setting('form_yes', 'Có') }}</option>
                <option value="0" @selected((string) $value === '0')>{{ setting('form_no', 'Không') }}</option>
            </select>
        @elseif ($type === AnswerType::Textarea)
            <textarea id="{{ $id }}" name="{{ $inputName }}" rows="4" maxlength="2000"
                      @if (filled($question->placeholder)) placeholder="{{ $question->placeholder }}" @endif
                      @if ($question->is_required) required @endif
                      @if ($hasError) aria-invalid="true" @endif>{{ $value }}</textarea>
        @else
            <input id="{{ $id }}" name="{{ $inputName }}" value="{{ $value }}"
                   type="{{ match ($type) { AnswerType::Email => 'email', AnswerType::Phone => 'tel', AnswerType::Date => 'date', default => 'text' } }}"
                   @if ($type === AnswerType::Number) inputmode="decimal" @endif
                   @if ($type === AnswerType::Phone) inputmode="tel" autocomplete="tel" @endif
                   @if ($type === AnswerType::Email) autocomplete="email" @endif
                   @if ($question->lead_field === 'student_name') autocomplete="name" @endif
                   @if ($type === AnswerType::Date) max="{{ now()->subDay()->toDateString() }}" @endif
                   @if (filled($question->placeholder)) placeholder="{{ $question->placeholder }}" @endif
                   @if ($question->is_required) required @endif
                   @if ($hasError) aria-invalid="true" @endif>
        @endif
    @endif

    @if (filled($question->help_text))
        <p class="field__help">{{ $question->help_text }}</p>
    @endif
    @if ($hasError)
        <p class="field__error">{{ $errors->first($key) ?: $errors->first($key.'.*') }}</p>
    @endif
</div>
