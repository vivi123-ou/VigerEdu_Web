@extends('layouts.admin', ['menuKey' => 'intro'])

@php
    use App\Support\IntroBlocks;

    $type = $block['type'];
    $data = $block['data'];
    $value = fn (string $key, mixed $default = '') => old($key, $data[$key] ?? $default);
    $items = array_values((array) old('items', $data['items'] ?? []));
    $items[] = [];
    $rowView = match ($type) {
        'steps' => 'admin.intro-blocks.rows.step',
        'faq' => 'admin.intro-blocks.rows.faq',
        'testimonials' => 'admin.intro-blocks.rows.testimonial',
        default => null,
    };
@endphp

@section('title', 'Sửa khối: '.IntroBlocks::typeLabel($type))

@section('content')
    @include('admin.intro-blocks.tabs', ['active' => 'blocks'])
    <p><a href="{{ route('admin.intro-blocks.index') }}">← Các khối nội dung</a></p>
    <p class="lead">{{ IntroBlocks::TYPES[$type][1] }}</p>

    <form method="post" action="{{ route('admin.intro-blocks.update', $block['id']) }}" class="card form form--wide">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="title">Tiêu đề khối</label>
            <input id="title" name="title" value="{{ $value('title') }}" maxlength="150">
            @error('title')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        @if ($type === 'groups')
            <div class="field">
                <label for="intro">Đoạn giới thiệu</label>
                <textarea id="intro" name="intro" rows="4" maxlength="1500">{{ $value('intro') }}</textarea>
                <p class="field__hint">Tên và mô tả từng nhóm: bấm sửa trực tiếp trên <a href="{{ route('admin.page-editor.show', 'trang-gioi-thieu') }}">trang giới thiệu</a>.</p>
            </div>
        @endif

        @if ($type === 'text_image')
            <div class="field">
                <label for="body">Nội dung</label>
                <textarea id="body" name="body" rows="8" data-rich>{{ $value('body') }}</textarea>
            </div>
            @include('admin.content.field', ['field' => ['key' => 'image', 'label' => 'Ảnh', 'type' => 'image', 'hint' => 'Không bắt buộc. Nên rộng khoảng 1000px.'], 'value' => $value('image')])
            <div class="grid-2">
                <div class="field">
                    <label for="image_side">Vị trí ảnh</label>
                    <select id="image_side" name="image_side">
                        <option value="right" @selected($value('image_side', 'right') === 'right')>Bên phải chữ</option>
                        <option value="left" @selected($value('image_side') === 'left')>Bên trái chữ</option>
                    </select>
                </div>
                <div class="field">
                    <label for="image_alt">Mô tả ảnh (cho người khiếm thị và Google)</label>
                    <input id="image_alt" name="image_alt" value="{{ $value('image_alt') }}" maxlength="150">
                </div>
                <div class="field">
                    <label for="button_text">Chữ trên nút (không bắt buộc)</label>
                    <input id="button_text" name="button_text" value="{{ $value('button_text') }}" maxlength="60">
                </div>
                <div class="field">
                    <label for="button_url">Địa chỉ nút</label>
                    <input id="button_url" name="button_url" type="url" value="{{ $value('button_url') }}" placeholder="https://">
                    @error('button_url')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        @if ($rowView)
            <div class="field">
                <p class="field__label-text">{{ ['steps' => 'Các bước', 'faq' => 'Các câu hỏi', 'testimonials' => 'Các lời cảm nhận'][$type] }}</p>
                @if ($type === 'testimonials')
                    <p class="field__hint">Chỉ đăng lời nhận xét thật, đã được người nói đồng ý. Khối không có lời cảm nhận nào sẽ tự ẩn.</p>
                @endif
                <div class="rows" data-rows>
                    @foreach ($items as $index => $item)
                        @include($rowView, ['index' => $index, 'item' => $item])
                    @endforeach
                </div>
                <template data-row-template>
                    @include($rowView, ['index' => '__i__', 'item' => []])
                </template>
                <button class="btn btn--small" type="button" data-add-row>+ Thêm dòng</button>
                @foreach ($errors->get('items.*') as $messages)
                    <p class="field__error">{{ $messages[0] }}</p>
                @endforeach
            </div>
        @endif

        @if ($type === 'steps')
            <div class="field">
                <label for="note">Dòng ghi chú dưới các bước</label>
                <input id="note" name="note" value="{{ $value('note') }}" maxlength="300">
            </div>
            <label class="check">
                <input type="checkbox" name="in_hero" value="1" @checked($value('in_hero', false))>
                <span>Đặt khối này cạnh banner (khi banner không có ảnh)</span>
            </label>
        @endif

        <label class="check">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $block['enabled']))>
            <span>Hiện khối này trên trang giới thiệu</span>
        </label>

        <div class="form__actions">
            <button class="btn" type="submit" formaction="{{ route('admin.intro-blocks.preview', $block['id']) }}" formtarget="_blank">Xem trước</button>
            <button class="btn btn--primary" type="submit">Lưu</button>
            <a class="btn btn--plain" href="{{ route('admin.intro-blocks.index') }}">Hủy</a>
        </div>
    </form>

    @include('admin.media.picker')
@endsection

@if ($type === 'text_image')
    @push('scripts')
        @include('admin.partials.rich-editor')
    @endpush
@endif
