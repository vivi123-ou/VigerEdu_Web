@extends('crm::layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
    <div class="page-head">
        <div>
            <h1>Đổi mật khẩu</h1>
            <p class="muted">Tối thiểu 8 ký tự, gồm cả chữ và số</p>
        </div>
    </div>

    @if (session('warning') || $forced)
        <div class="alert alert-warning">{{ session('warning') ?? 'Bạn đang dùng mật khẩu tạm. Vui lòng đặt mật khẩu mới để tiếp tục.' }}</div>
    @endif

    <form method="POST" action="{{ route('crm.account.password.update') }}" class="card form-card form-narrow">
        @csrf
        @method('PUT')

        <label class="field @error('current_password') has-error @enderror">
            <span>{{ $forced ? 'Mật khẩu tạm' : 'Mật khẩu hiện tại' }} <i>*</i></span>
            <input type="password" name="current_password" required autocomplete="current-password" autofocus>
            @error('current_password')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field @error('password') has-error @enderror">
            <span>Mật khẩu mới <i>*</i></span>
            <input type="password" name="password" required autocomplete="new-password">
            @error('password')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span>Nhập lại mật khẩu mới <i>*</i></span>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>

        <div class="form-actions">
            @unless ($forced)<a href="{{ route('crm.dashboard') }}" class="btn btn-outline">Huỷ</a>@endunless
            <button type="submit" class="btn btn-primary">Lưu mật khẩu</button>
        </div>
    </form>
@endsection
