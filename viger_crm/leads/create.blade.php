@extends('crm::layouts.app')

@section('title', 'Thêm khách hàng')
@section('breadcrumb', 'Thêm mới')

@use('App\Models\Lead')

@section('content')
    <div class="page-head">
        <div>
            <h1>Thêm khách hàng</h1>
            <p class="muted">Tạo lead mới và phân công tư vấn viên</p>
        </div>
    </div>

    <form method="POST" action="{{ route('crm.leads.store') }}" class="card form-card">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
        @endif

        <div class="form-grid">
            <label class="field @error('full_name') has-error @enderror">
                <span>Họ tên <i>*</i></span>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required autofocus>
                @error('full_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('phone') has-error @enderror">
                <span>Số điện thoại <i>*</i></span>
                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0901234567" required>
                @error('phone')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('email') has-error @enderror">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}">
                @error('email')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('source') has-error @enderror">
                <span>Nguồn <i>*</i></span>
                <select name="source" required>
                    @foreach (Lead::SOURCES as $key => $label)
                        {{-- ?source=other (từ trang Nguồn khác) chọn sẵn nguồn --}}
                        <option value="{{ $key }}" @selected(old('source', array_key_exists(request('source'), Lead::SOURCES) ? request('source') : 'website') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('source')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('status') has-error @enderror">
                <span>Trạng thái <i>*</i></span>
                <select name="status" required>
                    @foreach (Lead::STATUSES as $key => [$label])
                        <option value="{{ $key }}" @selected(old('status', 'new') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            @if (auth()->user()->crmCan('leads.assign'))
            <label class="field @error('assigned_to') has-error @enderror">
                <span>Tư vấn viên phụ trách</span>
                <select name="assigned_to">
                    <option value="">— Chưa gán —</option>
                    @foreach ($consultants as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('assigned_to') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>
            @else
                <div class="field"><span>Tư vấn viên phụ trách</span><p class="muted">Bạn sẽ phụ trách khách hàng này.</p></div>
            @endif

            <label class="field field-full @error('note') has-error @enderror">
                <span>Ghi chú</span>
                <textarea name="note" rows="4" placeholder="Nhu cầu, ngành học quan tâm, thời gian liên hệ phù hợp...">{{ old('note') }}</textarea>
                @error('note')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <div class="form-actions">
            <a href="{{ route('crm.leads.index') }}" class="btn btn-outline">Huỷ</a>
            <button type="submit" class="btn btn-primary">Lưu khách hàng</button>
        </div>
    </form>
@endsection
