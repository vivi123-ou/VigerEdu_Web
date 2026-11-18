{{--
    Form "Cập nhật thông tin để xem báo cáo đầy đủ" (H4) theo mẫu luong-khach.html (màn hình 3, bố cục UEH: tiêu đề giữa, ô 2 cột).
    Các ô dựng từ bảng form_fields (admin sửa chữ, thứ tự, bắt buộc, ẩn hiện). Ô có điều kiện (ví dụ chọn "Phụ huynh" thì ẩn
    SĐT học sinh, ô Email đổi thành "Email của học sinh") đổi ngay khi chọn nhờ khach.js; máy chủ kiểm tra lại theo cùng điều kiện.
    Không có ô tick đồng ý; câu đồng ý chỉ hiện khi admin gõ câu trong cài đặt (report_consent_text). Turnstile chạy ẩn khi có khóa.
--}}
@extends('layouts.public')

@section('title', setting('report_form_title'))
@section('noindex', true)

@section('content')
    <div class="ksec">
        <div class="uf">
            <h1 class="uf__title">{{ setting('report_form_title', 'Cập nhật thông tin để xem báo cáo đầy đủ') }}</h1>

            @error('form')
                <p class="qerr" role="alert" style="margin:0 0 18px">{{ $message }}</p>
            @enderror

            <form method="post" action="{{ route('report.submit') }}" data-report-form novalidate>
                @csrf
                <div class="uf__grid">
                    @foreach ($fields as $field)
                        @php
                            $hidden = $form->isHidden($field, $values);
                            $value = $values[$field->key] ?? '';
                            $id = 'f_'.$field->key;
                            $wide = in_array($field->type, ['radio', 'textarea'], true);
                        @endphp
                        <div @class(['f', 'full' => $wide, 'has-err' => $errors->has($field->key)]) data-field="{{ $field->key }}"
                             data-label="{{ $field->label }}"
                             @if ($field->conditions) data-conditions='@json($field->conditions)' @endif
                             @if ($hidden) hidden @endif>
                            @if ($field->type === 'radio')
                                <span class="lb" id="{{ $id }}_lb">{{ $form->labelFor($field, $values) }}@if ($field->is_required) <span class="req">*</span>@endif</span>
                                <div class="radios" role="radiogroup" aria-labelledby="{{ $id }}_lb">
                                    @foreach ($field->options ?? [] as $option)
                                        <label class="r">
                                            <input type="radio" name="{{ $field->key }}" value="{{ $option['value'] }}" @checked((string) $value === (string) $option['value'])>
                                            <i aria-hidden="true"></i>{{ $option['label'] }}
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <label class="lb" for="{{ $id }}"><span data-label-text>{{ $form->labelFor($field, $values) }}</span>@if ($field->is_required) <span class="req">*</span>@endif</label>
                                @if ($field->type === 'select')
                                    <select class="inp" id="{{ $id }}" name="{{ $field->key }}">
                                        <option value="">{{ $field->placeholder ?: 'Chọn' }}</option>
                                        @foreach ($field->options ?? [] as $option)
                                            <option value="{{ $option['value'] }}" @selected((string) $value === (string) $option['value'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($field->type === 'year')
                                    <select class="inp" id="{{ $id }}" name="{{ $field->key }}">
                                        <option value="">{{ $field->placeholder ?: 'Chọn năm' }}</option>
                                        @for ($y = now()->year - 5; $y >= now()->year - 70; $y--)
                                            <option value="{{ $y }}" @selected((string) $value === (string) $y)>{{ $y }}</option>
                                        @endfor
                                    </select>
                                @elseif ($field->type === 'textarea')
                                    <textarea class="inp" id="{{ $id }}" name="{{ $field->key }}" rows="3" placeholder="{{ $field->placeholder }}">{{ $value }}</textarea>
                                @else
                                    @php
                                        [$type, $autocomplete, $inputmode] = match (true) {
                                            $field->type === 'email' => ['email', 'email', 'email'],
                                            $field->type === 'phone' => ['tel', 'tel', 'tel'],
                                            $field->type === 'number' => ['text', 'off', 'decimal'],
                                            $field->crm_key === 'student_name' => ['text', 'name', 'text'],
                                            default => ['text', 'on', 'text'],
                                        };
                                    @endphp
                                    <input class="inp" id="{{ $id }}" name="{{ $field->key }}" value="{{ $value }}" placeholder="{{ $field->placeholder }}"
                                           type="{{ $type }}" autocomplete="{{ $autocomplete }}" inputmode="{{ $inputmode }}">
                                @endif
                            @endif
                            @error($field->key)
                                <p class="err-msg">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="full">
                        @if ($turnstileKey)
                            <div class="cf-turnstile" data-sitekey="{{ $turnstileKey }}" data-size="invisible" data-language="vi"></div>
                        @endif
                        <button class="kbtn kbtn--red" type="submit">{{ setting('report_form_button', 'Xem báo cáo') }}</button>
                        @if (filled($consent))
                            <p class="consent">{{ $consent }}</p>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($turnstileKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script src="{{ asset('js/khach.js') }}" defer></script>
@endpush
