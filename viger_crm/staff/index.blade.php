@extends('crm::layouts.app')

@section('title', 'Quản lý nhân viên')

@use('App\Http\Controllers\Crm\StaffController')

@php
    $hasFilter = $q !== '' || $role || $status;
    $roleBadge = ['super_admin' => 'badge-violet', 'admin' => 'badge-violet', 'manager' => 'badge-amber', 'consultant' => 'badge-blue', 'marketing' => 'badge-sky'];
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Quản lý nhân viên</h1>
            <p class="muted">{{ $counts['active'] }} tài khoản đang hoạt động · {{ $counts['locked'] }} bị khoá</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('crm.staff.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Thêm nhân viên
            </a>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Tài khoản mới và tài khoản vừa đặt lại mật khẩu nhận mật khẩu tạm, phải đổi ở lần đăng nhập đầu.
        Khoá tài khoản khi nhân viên nghỉ — có thể chuyển khách họ đang chăm sóc cho người khác cùng lúc.
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($temp = session('temp_password'))
        <div class="alert temp-password" role="alert">
            <b>Mật khẩu tạm cho “{{ $temp['user'] }}”:</b>
            <code class="mono" id="tempPw">{{ $temp['password'] }}</code>
            <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('tempPw').textContent); this.textContent='Đã chép'">Chép</button>
            <p class="muted">Chỉ hiện một lần. Gửi riêng cho nhân viên; họ sẽ phải đổi mật khẩu khi đăng nhập.</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="card card-flush">
        <form method="GET" action="{{ route('crm.staff.index') }}" class="toolbar toolbar-row">
            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Tên, tên đăng nhập, email...">
            </div>
            <div class="filters">
                <select name="role" onchange="this.form.submit()" aria-label="Vai trò">
                    <option value="">Mọi vai trò</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->key }}" @selected($role === $r->key)>{{ $r->name }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()" aria-label="Trạng thái">
                    <option value="">Mọi trạng thái</option>
                    <option value="active" @selected($status === 'active')>Đang hoạt động</option>
                    <option value="locked" @selected($status === 'locked')>Bị khoá</option>
                </select>
                @if ($hasFilter)
                    <a href="{{ route('crm.staff.index') }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Nhân viên</th><th>Vai trò CRM · Nhóm</th><th>Khách đang chăm sóc</th><th>Hoạt động gần nhất</th><th>Trạng thái</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($staff as $m)
                        @php
                            $isMe = $m->id === auth()->id();
                            $open = (int) ($openLeads[$m->id] ?? 0);
                            $last = isset($lastActive[$m->id]) ? \Illuminate\Support\Carbon::parse($lastActive[$m->id]) : null;
                        @endphp
                        <tr class="{{ $m->active ? '' : 'row-muted' }}">
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm">{{ mb_strtoupper(mb_substr($m->displayName(), 0, 1)) }}</span>
                                    <div>
                                        <b>{{ $m->displayName() }}</b> @if ($isMe)<span class="badge badge-gray">Bạn</span>@endif
                                        <small class="muted d-block"><span class="mono">{{ $m->username }}</span> · {{ $m->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $roleBadge[$m->crmRole()] ?? 'badge-gray' }}">{{ $m->crmRoleLabel() }}</span>
                                @if ($m->crmTeam)<small class="muted d-block">Nhóm: {{ $m->crmTeam->name }}</small>@endif
                                @if ($m->role === 'ADMIN')<small class="muted d-block" title="Có quyền vào trang quản trị nội dung của web chính">+ admin web chính</small>@endif
                            </td>
                            <td>
                                @if ($open)
                                    <a href="{{ route('crm.leads.index', ['assigned_to' => $m->id]) }}" class="link">{{ $open }} khách</a>
                                @else
                                    <span class="muted">0</span>
                                @endif
                            </td>
                            <td class="muted">{{ $last ? $last->diffForHumans() : 'Chưa có' }}</td>
                            <td>
                                @if (! $m->active)
                                    <span class="badge badge-gray">Bị khoá</span>
                                @elseif ($m->must_change_password)
                                    <span class="badge badge-amber" title="Chưa đổi mật khẩu tạm">Chờ đổi mật khẩu</span>
                                @else
                                    <span class="badge badge-green">Hoạt động</span>
                                @endif
                            </td>
                            <td class="actions staff-actions">
                                @php $manageable = StaffController::canManage(auth()->user(), $m); @endphp
                                @if ($manageable || $isMe)
                                    <a href="{{ route('crm.staff.edit', $m) }}" class="btn btn-outline btn-sm">Sửa</a>
                                @endif
                                @if ($manageable)
                                <form method="POST" action="{{ route('crm.staff.reset', $m) }}" onsubmit="return confirm('Đặt lại mật khẩu cho {{ addslashes($m->displayName()) }}? Mật khẩu cũ sẽ không dùng được nữa.')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm">Đặt lại mật khẩu</button>
                                </form>
                                    @if ($m->active)
                                        <details class="lock-box">
                                            <summary class="btn btn-danger-outline btn-sm">Khoá</summary>
                                            <form method="POST" action="{{ route('crm.staff.lock', $m) }}" class="lock-form">
                                                @csrf
                                                <p><b>Khoá {{ $m->displayName() }}?</b> Người này sẽ không đăng nhập được nữa.</p>
                                                @if ($open)
                                                    <label class="field">
                                                        <span>Chuyển {{ $open }} khách đang chăm sóc cho</span>
                                                        <select name="reassign_to">
                                                            <option value="">— Giữ nguyên (khách sẽ không có người chăm sóc) —</option>
                                                            @foreach ($staff->where('active', true)->where('id', '!=', $m->id) as $other)
                                                                <option value="{{ $other->id }}">{{ $other->displayName() }} · {{ $other->crmRoleLabel() }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                @endif
                                                <button type="submit" class="btn btn-danger-outline btn-sm">Xác nhận khoá</button>
                                            </form>
                                        </details>
                                    @else
                                        <form method="POST" action="{{ route('crm.staff.unlock', $m) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm">Mở khoá</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty"><p>Không có nhân viên nào khớp bộ lọc.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
