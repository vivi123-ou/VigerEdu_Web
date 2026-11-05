{{-- Một câu Phần A, hiển thị theo kiểu trả lời. Câu có điều kiện (show_if) được JS ẩn/hiện. --}}
@php
    use App\Enums\AnswerType;
    $key = 'info.'.$question->code;
    $inputName = 'info['.$question->code.']';
    $id = 'f-'.$question->code;
    $value = old($key);
    $options = $question->options ?? [];
    $type = $question->answer_type;
    $hasError = $errors->has($key);
@endphp

<div @class(['field', 'field--wide' => in_array($type, [AnswerType::Consent, AnswerType::Multi], true) || ($type === AnswerType::Single && count($options) <= 4), 'has-error' => $hasError])
     data-field="{{ $question->code }}"
     @if (! empty($question->show_if['question'])) data-show-if='@json($question->show_if)' @endif>

    @if ($type === AnswerType::Consent)
        <label class="check">
            <input type="checkbox" id="{{ $id }}" name="{{ $inputName }}" value="1" @checked(old($key)) required>
            <span>{{ $question->text }}</span>
        </label>
    @elseif ($type === AnswerType::Single && count($options) <= 4)
        <fieldset class="choice">
            <legend class="field__label">{{ $question->text }} @if ($question->is_required)<span class="req" aria-hidden="true">*</span>@endif</legend>
            <div class="choice__list">
                @foreach ($options as $option)
                    <label class="choice__item">
                        <input type="radio" name="{{ $inputName }}" value="{{ $option['value'] }}" @checked((string) $value === (string) $option['value'])>
                        <span>{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @elseif ($type === AnswerType::Multi)
        <fieldset class="choice">
            <legend class="field__label">{{ $question->text }} @if ($question->is_required)<span class="req" aria-hidden="true">*</span>@endif</legend>
            <div class="choice__list">
                @foreach ($options as $option)
                    <label class="choice__item">
                        <input type="checkbox" name="{{ $inputName }}[]" value="{{ $option['value'] }}" @checked(in_array((string) $option['value'], array_map('strval', (array) $value), true))>
                        <span>{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
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
        @else
            <input id="{{ $id }}" name="{{ $inputName }}" value="{{ $value }}"
                   type="{{ match ($type) { AnswerType::Email => 'email', AnswerType::Phone => 'tel', AnswerType::Date => 'date', AnswerType::Number => 'text', default => 'text' } }}"
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
    @error($key)
        <p class="field__error">{{ $message }}</p>
    @enderror
</div>
