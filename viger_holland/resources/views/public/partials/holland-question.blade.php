{{-- Một câu Holland, hiển thị theo kiểu (thang mức / chọn 1 / chọn nhiều) và cách hiện (nút, ngôi sao, danh sách). Biến: $question, $set --}}
@php
    use App\Enums\AnswerType;
    use App\Models\Question;

    $name = "answers.{$question->code}";
    $inputName = "answers[{$question->code}]";
    $type = $question->answer_type;
    $display = $question->displayStyle($set);
    $old = old($name);
    $oldValues = array_map('strval', (array) $old);
    $hasError = $errors->has($name) || $errors->has($name.'.*');
@endphp

<fieldset @class(['q', 'has-error' => $hasError]) data-question="{{ $question->code }}" @unless ($question->is_required) data-optional @endunless>
    <legend class="q__text">
        {{ $question->text }}
        @unless ($question->is_required)
            <span class="q__optional">{{ setting('test_optional_label', '(không bắt buộc)') }}</span>
        @endunless
    </legend>

    @if ($type->isChoice())
        @if ($type === AnswerType::Single && $display === Question::DISPLAY_DROPDOWN)
            <select name="{{ $inputName }}" class="q__select" aria-label="{{ $question->text }}">
                <option value="">{{ setting('form_select_placeholder', '— Chọn —') }}</option>
                @foreach ($question->choices() as $choice)
                    <option value="{{ $choice['value'] }}" @selected(in_array((string) $choice['value'], $oldValues, true))>{{ $choice['label'] }}</option>
                @endforeach
            </select>
        @else
            @php $max = (int) $question->configValue('max_select'); $min = (int) $question->configValue('min_select'); @endphp
            @if ($type === AnswerType::Multi && ($min > 0 || $max > 0))
                <p class="q__hint">
                    @if ($min > 0 && $max > 0) Chọn từ {{ $min }} đến {{ $max }} ô.
                    @elseif ($max > 0) Chọn tối đa {{ $max }} ô.
                    @else Chọn ít nhất {{ $min }} ô.
                    @endif
                </p>
            @endif
            <div class="choice-list">
                @foreach ($question->choices() as $choice)
                    <label class="choice__item choice__item--block">
                        <input type="{{ $type === AnswerType::Multi ? 'checkbox' : 'radio' }}"
                               name="{{ $type === AnswerType::Multi ? $inputName.'[]' : $inputName }}"
                               value="{{ $choice['value'] }}"
                               @checked(in_array((string) $choice['value'], $oldValues, true))>
                        <span>{{ $choice['label'] }}</span>
                    </label>
                @endforeach
            </div>
        @endif
    @else
        @php
            $scale = $question->scale($set);
            $isTwoChoice = count($scale) <= 2;
        @endphp

        @if ($display === Question::DISPLAY_STARS && ! $isTwoChoice)
            @php $ascending = collect($scale)->sortBy('value')->values(); @endphp
            <div class="stars" role="radiogroup" aria-label="{{ $question->text }}">
                @foreach ($ascending->reverse() as $option)
                    @php $id = 's-'.$question->code.'-'.$loop->remaining; @endphp
                    <input type="radio" id="{{ $id }}" name="{{ $inputName }}" value="{{ $option['value'] }}" @checked((string) $old === (string) $option['value'])>
                    <label for="{{ $id }}" title="{{ $option['label'] }}"><span aria-hidden="true">★</span><span class="sr-only">{{ $option['label'] }}</span></label>
                @endforeach
            </div>
            <p class="stars__ends"><span>{{ $ascending->first()['label'] }}</span><span>{{ $ascending->last()['label'] }}</span></p>
        @else
            <div @class(['options', 'options--yesno' => $isTwoChoice, 'options--scale' => ! $isTwoChoice]) @unless ($isTwoChoice) style="--cols: {{ count($scale) }}" @endunless>
                @foreach ($scale as $option)
                    <label class="opt">
                        <input type="radio" name="{{ $inputName }}" value="{{ $option['value'] }}" @checked((string) $old === (string) $option['value'])>
                        @unless ($isTwoChoice)
                            <span class="opt__num">{{ $loop->iteration }}</span>
                        @endunless
                        <span class="opt__label">{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
        @endif
    @endif

    @if (filled($question->help_text))
        <p class="field__help">{{ $question->help_text }}</p>
    @endif
    @if ($hasError)
        <p class="field__error">{{ $errors->first($name) ?: $errors->first($name.'.*') }}</p>
    @endif
</fieldset>
