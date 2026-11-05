@extends('layouts.admin', ['menuKey' => 'info-form'])

@section('title', $question->exists ? 'Sửa câu '.$question->code : 'Thêm câu vào form thông tin')

@php
    use App\Enums\AnswerType;
    use App\Http\Requests\Admin\InfoQuestionRequest;
    use App\Support\ChoiceList;
    use App\Support\LeadFields;

    $isConsent = $question->lead_field === 'consent';
    $currentTarget = $question->lead_field ?? ($question->extra_field_key ? InfoQuestionRequest::CUSTOM : '');
    $target = old('crm_target', $currentTarget);
    $type = old('answer_type', $question->answer_type?->value);
    $conditionQuestion = old('show_if_question', $question->show_if['question'] ?? '');
    $conditionValues = array_map('strval', old('show_if_values', $question->show_if['in'] ?? []));
@endphp

@section('content')
    <p><a href="{{ route('admin.info-questions.index') }}">← Form thông tin khách</a></p>

    <form method="post" action="{{ $question->exists ? route('admin.info-questions.update', $question) : route('admin.info-questions.store') }}" class="card form" data-info-form>
        @csrf
        @if ($question->exists) @method('PUT') @endif

        <div class="field">
            <label for="text">Câu hỏi (khách nhìn thấy)</label>
            <textarea id="text" name="text" rows="2" required maxlength="500" @error('text') aria-invalid="true" @enderror>{{ old('text', $question->text) }}</textarea>
            <p class="field__hint">Ví dụ: "Trường THPT đang học có phải trường chuyên?"</p>
            @error('text')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        @if ($isConsent)
            <input type="hidden" name="answer_type" value="consent">
            <p class="muted">Đây là ô đồng ý lưu thông tin: luôn bật, luôn bắt buộc, luôn ở cuối form. Chỉ sửa được câu chữ.</p>
        @else
            <div class="field">
                <label for="answer_type">Kiểu trả lời</label>
                <select id="answer_type" name="answer_type" data-answer-type>
                    @foreach (AnswerType::partAOptions() as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="field__hint">"Có / Không" hiện ô chọn Có hoặc Không. "Chọn 1": nhiều lựa chọn, chọn một.</p>
            </div>

            <div class="field" data-show-for="single multi rating">
                <label for="options_text">Các lựa chọn (mỗi dòng một lựa chọn)</label>
                <textarea id="options_text" name="options_text" rows="6" placeholder="Lớp 10&#10;Lớp 11&#10;Phụ huynh = PH" @error('options_text') aria-invalid="true" @enderror>{{ old('options_text', ChoiceList::format($question->options)) }}</textarea>
                <p class="field__hint">Mỗi dòng một lựa chọn. Muốn CRM nhận mã khác chữ hiển thị thì ghi "Chữ hiển thị = mã", ví dụ "Phụ huynh = PH". Lựa chọn của trường chuẩn nên giữ đúng mã trong từ điển dữ liệu chung. Kiểu "Đánh giá": mỗi dòng "Nhãn = điểm", để trống = 1 đến 5 sao.</p>
                @error('options_text')<p class="field__error">{{ $message }}</p>@enderror
            </div>

            <div class="field" data-show-for="single">
                <label for="display-single">Hiện dạng</label>
                <select id="display-single" name="display">
                    <option value="">Tự động (ít lựa chọn → nút bấm, nhiều → danh sách sổ xuống)</option>
                    <option value="buttons" @selected(old('display', $question->display) === 'buttons')>Nút bấm</option>
                    <option value="dropdown" @selected(old('display', $question->display) === 'dropdown')>Danh sách sổ xuống</option>
                </select>
            </div>

            <div class="field" data-show-for="rating">
                <label for="display-rating">Hiện dạng</label>
                <select id="display-rating" name="display">
                    <option value="stars" @selected(old('display', $question->display ?? 'stars') === 'stars')>Ngôi sao</option>
                    <option value="buttons" @selected(old('display', $question->display) === 'buttons')>Nút bấm có nhãn</option>
                </select>
            </div>

            <label class="check" data-show-for="single multi">
                <input type="checkbox" name="allow_other" value="1" @checked(old('allow_other', $question->configValue('allow_other')))>
                <span>Thêm lựa chọn "Khác" (khách tự ghi)</span>
            </label>

            <div class="grid-2" data-show-for="number">
                <div class="field">
                    <label for="min">Số nhỏ nhất</label>
                    <input id="min" name="min" value="{{ old('min', $question->configValue('min')) }}" inputmode="decimal" class="input--short">
                </div>
                <div class="field">
                    <label for="max">Số lớn nhất</label>
                    <input id="max" name="max" value="{{ old('max', $question->configValue('max')) }}" inputmode="decimal" class="input--short">
                    <p class="field__hint">Để trống = không giới hạn. Ví dụ điểm trung bình: 0 đến 10.</p>
                    @error('max')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="field" data-show-for="text textarea number phone email date">
                <label for="placeholder">Chữ gợi ý trong ô</label>
                <input id="placeholder" name="placeholder" value="{{ old('placeholder', $question->placeholder) }}" maxlength="255">
                <p class="field__hint">Chữ mờ hiện trong ô khi chưa điền, ví dụ "Ví dụ: 0912 345 678".</p>
            </div>
        @endif

        <div class="field">
            <label for="help_text">Chú thích dưới câu hỏi</label>
            <input id="help_text" name="help_text" value="{{ old('help_text', $question->help_text) }}" maxlength="255">
            <p class="field__hint">Không bắt buộc. Ví dụ: "Kết quả sẽ được gửi vào email này."</p>
        </div>

        @unless ($isConsent)
            <fieldset class="field">
                <legend>Gửi sang CRM vào trường…</legend>
                <select name="crm_target" aria-label="Gửi sang CRM vào trường" data-crm-target>
                    <option value="" @selected($target === '')>Không gửi sang CRM</option>
                    <optgroup label="Trường có sẵn trong CRM">
                        @foreach (LeadFields::LABELS as $field => $label)
                            @continue($field === 'consent')
                            <option value="{{ $field }}" @selected($target === $field)>{{ $label }} ({{ $field }})</option>
                        @endforeach
                    </optgroup>
                    <option value="{{ InfoQuestionRequest::CUSTOM }}" @selected($target === InfoQuestionRequest::CUSTOM)>Trường mới (tự đặt mã)…</option>
                </select>
                @error('crm_target')<p class="field__error">{{ $message }}</p>@enderror

                <div class="field field--nested" data-show-for-target="{{ InfoQuestionRequest::CUSTOM }}">
                    <label for="extra_field_key">Mã trường mới</label>
                    <input id="extra_field_key" name="extra_field_key" value="{{ old('extra_field_key', $question->extra_field_key) }}" maxlength="50" placeholder="truong_chuyen" @error('extra_field_key') aria-invalid="true" @enderror>
                    <p class="field__hint">Chữ thường không dấu và dấu gạch dưới, ví dụ truong_chuyen. CRM nhận trong mục "thông tin thêm" (extra_fields). Báo người làm CRM mã này.</p>
                    @error('extra_field_key')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            <fieldset class="field">
                <legend>Chỉ hiện khi… (không bắt buộc)</legend>
                <select name="show_if_question" aria-label="Câu điều kiện" data-condition-question>
                    <option value="">Luôn hiện</option>
                    @foreach ($conditionQuestions as $condition)
                        <option value="{{ $condition->code }}" @selected($conditionQuestion === $condition->code)>{{ $condition->code }} – {{ $condition->text }}</option>
                    @endforeach
                </select>
                @error('show_if_question')<p class="field__error">{{ $message }}</p>@enderror

                @foreach ($conditionQuestions as $condition)
                    @php
                        $choices = $condition->answer_type === AnswerType::Boolean
                            ? [['value' => '1', 'label' => setting('form_yes', 'Có')], ['value' => '0', 'label' => setting('form_no', 'Không')]]
                            : ($condition->options ?? []);
                    @endphp
                    <div class="field--nested condition-values" data-condition-for="{{ $condition->code }}" @if ($conditionQuestion !== $condition->code) hidden @endif>
                        <p class="field__hint">Hiện câu này khi khách trả lời câu {{ $condition->code }} là:</p>
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
                @error('show_if_values')<p class="field__error">{{ $message }}</p>@enderror
                <p class="field__hint">Ví dụ: chỉ hỏi "Họ tên phụ huynh" khi người làm là học sinh.</p>
            </fieldset>

            <label class="check">
                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $question->is_required))>
                <span>Bắt buộc trả lời</span>
            </label>

            <label class="check">
                <input type="checkbox" name="is_private" value="1" @checked(old('is_private', $question->configValue('private')))>
                <span>Thông tin riêng tư (không hiện ở trang Lượt làm bài, chỉ xem ở CRM)</span>
            </label>
            <p class="field__hint check-hint">Nên tick với họ tên, ngày sinh, thông tin phụ huynh. Số điện thoại và email luôn được ẩn.</p>

            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true))>
                <span>Bật câu này (hiện trên form)</span>
            </label>
        @else
            <input type="hidden" name="is_active" value="1">
            <input type="hidden" name="is_required" value="1">
        @endunless

        <div class="field">
            <label for="note">Ghi chú nội bộ</label>
            <input id="note" name="note" value="{{ old('note', $question->note) }}" maxlength="255">
        </div>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Lưu</button>
            <a class="btn btn--plain" href="{{ route('admin.info-questions.index') }}">Hủy</a>
        </div>
    </form>

    @if ($question->exists)
        @include('admin.partials.history', ['activities' => $activities])
    @endif
@endsection
