@extends('crm::layouts.app')

@php $editing = $member->exists; $isMe = $editing && $member->id === auth()->id(); @endphp

@section('title', $editing ? 'Sửa nhân viên' : 'Thêm nhân viên')
@section('breadcrumb', $editing ? $member->displayName() : 'Thêm nhân viên')

@use('App\Support\CrmPermissions')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('crm.staff.index') }}" class="back-link">← Quản lý nhân viên</a>
            <h1>{{ $editing ? 'Sửa: '.$member->displayName() : 'Thêm nhân viên' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $editing ? route('crm.staff.update', $member) : route('crm.staff.store') }}" class="card form-card">
        @csrf
        @if ($editing) @method('PUT') @endif

        @if ($errors->any())
            <div class="alert alert-danger">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
        @endif

        <div class="form-grid">
            <label class="field @error('full_name') has-error @enderror">
                <span>Họ tên <i>*</i></span>
                <input type="text" name="full_name" value="{{ old('full_name', $member->full_name) }}" required autofocus>
                @error('full_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('username') has-error @enderror">
                <span>Tên đăng nhập <i>*</i></span>
                <input type="text" name="username" value="{{ old('username', $member->username) }}" required autocomplete="off" placeholder="vd: nguyen.van.a">
                @error('username')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field @error('email') has-error @enderror">
                <span>Email công ty <i>*</i></span>
                <input type="email" name="email" value="{{ old('email', $member->email) }}" required>
                @error('email')<small class="error">{{ $message }}</small>@enderror
            </label>

            <div class="field @error('crm_role') has-error @enderror">
                <span>Vai trò trong CRM <i>*</i></span>
                @if ($isMe)
                    <input type="hidden" name="crm_role" value="{{ $member->crmRole() }}">
                    <input type="text" value="{{ $member->crmRoleLabel() }}" disabled>
                    <small class="muted">Bạn không thể tự đổi vai trò của mình.</small>
                @else
                    <div class="role-options">
                        @foreach ($roles as $r)
                            <label class="role-option">
                                <input type="radio" name="crm_role" value="{{ $r->key }}" @checked(old('crm_role', $member->crmRole()) === $r->key)>
                                <span>
                                    <b>{{ $r->name }}</b>
                                    <small class="muted">{{ $r->description }} · Phạm vi: {{ CrmPermissions::SCOPES[$r->scope] ?? $r->scope }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('crm_role')<small class="error">{{ $message }}</small>@enderror
            </div>
        </div>

        @if ($teams->isNotEmpty() && ! $isMe)
            <label class="field form-team @error('crm_team_id') has-error @enderror">
                <span>Nhóm làm việc</span>
                <select name="crm_team_id">
                    <option value="">— Không thuộc nhóm —</option>
                    @foreach ($teams as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('crm_team_id', $member->crm_team_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <small class="muted">Manager thấy khách của các thành viên trong nhóm mình phụ trách.</small>
            </label>
        @endif

        @unless ($editing)
            <p class="muted" style="margin-bottom: 16px">Mật khẩu tạm sẽ được tạo tự động và hiện một lần sau khi lưu.</p>
        @endunless

        <div class="form-actions">
            <a href="{{ route('crm.staff.index') }}" class="btn btn-outline">Huỷ</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Lưu thay đổi' : 'Tạo tài khoản' }}</button>
        </div>
    </form>
@endsection
