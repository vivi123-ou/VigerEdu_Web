{{-- Một ô của trang nội dung. Biến: $field (key, label, type, hint, required), $value --}}
@php
    $key = $field['key'];
    $id = 'f-'.$key;
    $type = $field['type'];
    $hasError = $errors->has($key) || $errors->has($key.'.*');
@endphp

<div class="field" @if ($type === 'image') data-image-field @endif>
    @if ($type !== 'links')
        <label for="{{ $id }}">{{ $field['label'] }}@if ($field['required'] ?? false) <span class="req">*</span>@endif</label>
    @else
        <p class="field__label-text">{{ $field['label'] }}</p>
    @endif

    @switch($type)
        @case('textarea')
        @case('domains')
            <textarea id="{{ $id }}" name="{{ $key }}" rows="{{ $type === 'domains' ? 4 : 3 }}" @if ($hasError) aria-invalid="true" @endif>{{ $value }}</textarea>
            @break

        @case('rich')
            <textarea id="{{ $id }}" name="{{ $key }}" rows="6" data-rich @if ($hasError) aria-invalid="true" @endif>{{ $value }}</textarea>
            @break

        @case('color')
            <span class="color-field">
                <input id="{{ $id }}" type="color" value="{{ $value ?: '#000000' }}" data-color-picker aria-label="{{ $field['label'] }} (bảng màu)">
                <input name="{{ $key }}" value="{{ $value }}" class="input--short" maxlength="7" pattern="#[0-9a-fA-F]{6}" data-color-text aria-label="{{ $field['label'] }} (mã màu)">
            </span>
            @break

        @case('font')
            <select id="{{ $id }}" name="{{ $key }}">
                @foreach (config('holland.fonts') as $font => [$fallback])
                    <option value="{{ $font }}" @selected($value === $font) style="font-family: '{{ $font }}', {{ $fallback }}">{{ $font }}</option>
                @endforeach
            </select>
            @break

        @case('image')
            <div class="image-field">
                <img class="image-field__preview" src="{{ media_url($value) }}" alt="" @if (blank($value)) hidden @endif data-image-preview>
                <input type="hidden" id="{{ $id }}" name="{{ $key }}" value="{{ $value }}" data-image-input>
                <div class="image-field__actions">
                    <button class="btn btn--small" type="button" data-pick-image>Chọn ảnh</button>
                    <button class="btn btn--small btn--plain" type="button" data-clear-image @if (blank($value)) hidden @endif>Bỏ ảnh</button>
                </div>
            </div>
            @break

        @case('links')
            @php
                $rows = old($key) ?? \App\Support\ContentPages::links($value);
                $rows = array_values((array) $rows);
                $rows[] = ['label' => '', 'url' => '', 'new_tab' => false];
            @endphp
            <table class="table table--form">
                <thead><tr><th>Chữ hiển thị</th><th>Địa chỉ (https://…)</th><th>Tab mới</th><th></th></tr></thead>
                <tbody data-rows>
                    @foreach ($rows as $index => $row)
                        @include('admin.content.link-row', ['key' => $key, 'index' => $index, 'row' => $row])
                    @endforeach
                </tbody>
            </table>
            <template data-row-template>
                @include('admin.content.link-row', ['key' => $key, 'index' => '__i__', 'row' => ['label' => '', 'url' => '', 'new_tab' => false]])
            </template>
            <button class="btn btn--small" type="button" data-add-row>+ Thêm link</button>
            @break

        @default
            <input id="{{ $id }}" name="{{ $key }}" value="{{ $value }}"
                   type="{{ match ($type) { 'url' => 'url', 'email' => 'email', default => 'text' } }}"
                   maxlength="255" @if ($hasError) aria-invalid="true" @endif>
    @endswitch

    @if (filled($field['hint'] ?? null))
        <p class="field__hint">{{ $field['hint'] }}</p>
    @endif
    @error($key)<p class="field__error">{{ $message }}</p>@enderror
    @foreach ($errors->get($key.'.*') as $messages)
        <p class="field__error">{{ $messages[0] }}</p>
    @endforeach
</div>
