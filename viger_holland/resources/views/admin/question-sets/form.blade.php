@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', $set->exists ? 'Sửa bộ câu: '.$set->name : 'Thêm bộ câu')

@section('content')
    <p><a href="{{ $set->exists ? route('admin.question-sets.show', $set) : route('admin.question-sets.index') }}">← Quay lại</a></p>

    <form method="post" action="{{ $set->exists ? route('admin.question-sets.update', $set) : route('admin.question-sets.store') }}" class="card form">
        @csrf
        @if ($set->exists) @method('PUT') @endif

        <div class="field">
            <label for="name">Tên bộ câu</label>
            <input id="name" name="name" value="{{ old('name', $set->name) }}" required maxlength="120" @error('name') aria-invalid="true" @enderror>
            <p class="field__hint">Chỉ người quản trị thấy tên này. Ví dụ: "Sinh viên – 30 câu".</p>
            @error('name')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="code">Mã bộ câu (dùng trong link)</label>
            <input id="code" name="code" value="{{ old('code', $set->code) }}" maxlength="50" @error('code') aria-invalid="true" @enderror>
            <p class="field__hint">Chữ thường không dấu, số, gạch ngang. Link làm bài sẽ là {{ route('test.show') }}?bo=<em>mã</em>. Để trống thì tự tạo từ tên.@if ($set->exists) Đổi mã thì link cũ không dùng được nữa.@endif</p>
            @error('code')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="description">Ghi chú</label>
            <textarea id="description" name="description" rows="2" maxlength="500">{{ old('description', $set->description) }}</textarea>
            <p class="field__hint">Dành cho người quản trị: bộ này dùng cho ai, khi nào.</p>
        </div>

        <div class="field">
            <label for="answer_type">Kiểu câu mặc định</label>
            <select id="answer_type" name="answer_type">
                @foreach (['yes_no', 'likert3', 'likert5'] as $type)
                    @php $answerType = \App\Enums\AnswerType::from($type); @endphp
                    <option value="{{ $type }}" @selected(old('answer_type', $set->answer_type?->value) === $type)>{{ $answerType->label() }} ({{ collect($answerType->defaultScale())->pluck('label')->implode(' / ') }})</option>
                @endforeach
            </select>
            <p class="field__hint">Dùng cho câu "Dán nhiều câu" và câu mới thêm. Từng câu vẫn chọn kiểu riêng được (chọn 1, chọn nhiều, thang tự đặt…). Điểm tính theo % nên trộn nhiều kiểu vẫn chấm đúng.@if ($set->exists) Đổi kiểu thì nhãn các mức được đặt lại như mặc định.@endif</p>
        </div>

        @if ($set->exists)
            <fieldset class="field">
                <legend>Nhãn từng mức (khách nhìn thấy)</legend>
                @foreach ($set->scaleOptions() as $index => $option)
                    <div class="scale-row">
                        <span class="scale-row__value">{{ $option['value'] }} điểm</span>
                        <input name="scale_labels[{{ $index }}]" value="{{ old('scale_labels.'.$index, $option['label']) }}" maxlength="60" aria-label="Nhãn mức {{ $option['value'] }} điểm">
                    </div>
                    @error('scale_labels.'.$index)<p class="field__error">{{ $message }}</p>@enderror
                @endforeach
            </fieldset>
        @endif

        <div class="field">
            <label for="display">Thang mức hiện dạng</label>
            <select id="display" name="display">
                <option value="buttons" @selected(old('display', $set->display ?? 'buttons') === 'buttons')>Ô số có nhãn</option>
                <option value="stars" @selected(old('display', $set->display) === 'stars')>Ngôi sao</option>
            </select>
            <p class="field__hint">Áp dụng cho các câu thang 3 / 5 mức trong bộ (từng câu vẫn đổi riêng được). Câu Đúng / Không luôn hiện 2 nút.</p>
        </div>

        <div class="field">
            <label for="per_page">Số câu mỗi trang</label>
            <input id="per_page" name="per_page" type="number" min="1" max="30" value="{{ old('per_page', $set->per_page) }}" class="input--short">
            <p class="field__hint">Trên điện thoại nên 6–9 câu một trang.</p>
            @error('per_page')<p class="field__error">{{ $message }}</p>@enderror
        </div>

        <label class="check">
            <input type="checkbox" name="shuffle_groups" value="1" @checked(old('shuffle_groups', $set->shuffle_groups))>
            <span>Xáo trộn thứ tự câu (mỗi khách một thứ tự khác nhau)</span>
        </label>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $set->is_active))>
            <span>Bật bộ câu (khách mở được link)</span>
        </label>
        @error('is_active')<p class="field__error">{{ $message }}</p>@enderror

        <label class="check">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $set->is_default)) @disabled($set->is_default)>
            <span>Đặt làm bộ mặc định (link chung {{ route('test.show') }} dùng bộ này)</span>
        </label>
        @if ($set->is_default)
            <input type="hidden" name="is_default" value="1">
        @endif

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Lưu</button>
            <a class="btn btn--plain" href="{{ $set->exists ? route('admin.question-sets.show', $set) : route('admin.question-sets.index') }}">Hủy</a>
        </div>
    </form>

    @if ($set->exists && ! $set->is_default)
        <form method="post" action="{{ route('admin.question-sets.destroy', $set) }}" data-confirm="Đưa bộ &quot;{{ $set->name }}&quot; vào thùng rác? Khách sẽ không mở được link của bộ này. Có thể khôi phục sau.">
            @csrf
            @method('DELETE')
            <button class="btn btn--danger" type="submit">Xóa bộ câu (vào thùng rác)</button>
        </form>
    @endif
@endsection
