@extends('crm::layouts.app')

@php
    $editing = $appointment->exists;
    $lead = $appointment->lead_id ? \App\Models\Lead::find($appointment->lead_id) : null;
@endphp

@section('title', $editing ? 'Đổi lịch hẹn' : 'Đặt lịch hẹn')
@section('breadcrumb', $editing ? 'Đổi lịch hẹn' : 'Đặt lịch hẹn')

@use('App\Models\Appointment')

@section('content')
    <div class="page-head">
        <div>
            @if ($returnTo === 'lead' && $lead)
                <a href="{{ route('crm.leads.show', $lead) }}#appointments" class="back-link">← {{ $lead->full_name }} · {{ $lead->code() }}</a>
            @else
                <a href="{{ route('crm.appointments.index') }}" class="back-link">← Lịch hẹn</a>
            @endif
            <h1>{{ $editing ? 'Đổi lịch hẹn' : 'Đặt lịch hẹn' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $editing ? route('crm.appointments.update', $appointment) : route('crm.appointments.store') }}" class="card form-card">
        @csrf
        @if ($editing) @method('PUT') @endif
        @if ($returnTo)<input type="hidden" name="return" value="{{ $returnTo }}">@endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first('scheduled_at') ?: 'Vui lòng kiểm tra lại các trường được đánh dấu.' }}</div>
        @endif

        <div class="form-grid">
            <label class="field field-full @error('lead_id') has-error @enderror">
                <span>Khách hàng <i>*</i></span>
                @if ($editing || ($returnTo === 'lead' && $lead))
                    <input type="hidden" name="lead_id" value="{{ $appointment->lead_id }}">
                    <input type="text" value="{{ $leads[$appointment->lead_id] ?? '' }}" disabled>
                @else
                    <input type="text" list="leadList" id="leadSearch" placeholder="Gõ tên hoặc SĐT để tìm..." autocomplete="off"
                           value="{{ old('lead_id', $appointment->lead_id) ? ($leads[old('lead_id', $appointment->lead_id)] ?? '') : '' }}">
                    <datalist id="leadList">
                        @foreach ($leads as $id => $label)
                            <option value="{{ $label }}" data-id="{{ $id }}"></option>
                        @endforeach
                    </datalist>
                    <input type="hidden" name="lead_id" id="leadId" value="{{ old('lead_id', $appointment->lead_id) }}">
                @endif
                @error('lead_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('scheduled_at') has-error @enderror">
                <span>Thời gian <i>*</i></span>
                <input type="datetime-local" name="scheduled_at" required step="300"
                       value="{{ old('scheduled_at', $appointment->scheduled_at?->format('Y-m-d\TH:i')) }}"
                       min="{{ now()->format('Y-m-d\TH:i') }}">
                @error('scheduled_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('duration_minutes') has-error @enderror">
                <span>Thời lượng <i>*</i></span>
                <select name="duration_minutes" required>
                    @foreach (Appointment::DURATIONS as $m => $label)
                        <option value="{{ $m }}" @selected((int) old('duration_minutes', $appointment->duration_minutes) === $m)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="field field-full">
                <span>Hình thức <i>*</i></span>
                <div class="note-types">
                    @foreach (Appointment::TYPES as $key => [$label, $icon])
                        <label class="chip">
                            <input type="radio" name="type" value="{{ $key }}" @checked(old('type', $appointment->type) === $key)>
                            <span><svg viewBox="0 0 24 24">{!! $icon !!}</svg>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="field @error('user_id') has-error @enderror">
                <span>Tư vấn viên phụ trách</span>
                <select name="user_id">
                    <option value="">— Chưa có —</option>
                    @foreach ($consultants as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('user_id', $appointment->user_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('user_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('location') has-error @enderror">
                <span>Địa điểm / link họp</span>
                <input type="text" name="location" value="{{ old('location', $appointment->location) }}" placeholder="VD: Văn phòng Q.3 hoặc https://meet.google.com/...">
                @error('location')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full @error('note') has-error @enderror">
                <span>Nội dung cần trao đổi</span>
                <textarea name="note" rows="3" placeholder="VD: Tư vấn chương trình Ausbildung, chuẩn bị hồ sơ tiếng Đức...">{{ old('note', $appointment->note) }}</textarea>
                @error('note')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <div class="form-actions">
            <a href="{{ $returnTo === 'lead' && $lead ? route('crm.leads.show', $lead).'#appointments' : route('crm.appointments.index') }}" class="btn btn-outline">Huỷ</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Lưu thay đổi' : 'Đặt lịch' }}</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Ô tìm khách: chọn từ danh sách gợi ý => gán lead_id ẩn
        (function () {
            var input = document.getElementById('leadSearch');
            if (!input) return;
            var hidden = document.getElementById('leadId');
            var options = document.querySelectorAll('#leadList option');
            input.addEventListener('input', function () {
                hidden.value = '';
                options.forEach(function (o) { if (o.value === input.value) hidden.value = o.dataset.id; });
                input.setCustomValidity(hidden.value ? '' : 'Chọn khách hàng trong danh sách gợi ý');
            });
            input.form.addEventListener('submit', function (e) {
                if (!hidden.value) { e.preventDefault(); input.setCustomValidity('Chọn khách hàng trong danh sách gợi ý'); input.reportValidity(); }
            });
        })();
    </script>
@endpush
