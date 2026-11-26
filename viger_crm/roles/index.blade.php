@extends('crm::layouts.app')

@section('title', 'Vai trò & phân quyền')

@php
    $tab = request('tab') === 'teams' ? 'teams' : 'matrix';
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Vai trò & phân quyền</h1>
            <p class="muted">Ai được xem và làm gì trong CRM</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Nguyên tắc: Consultant chỉ xem và thao tác trên khách được phân công; Manager trên khách của nhóm mình.
        Mục không có quyền sẽ ẩn khỏi menu, gõ thẳng đường dẫn sẽ bị chặn.
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="card card-flush">
        <nav class="tabs" aria-label="Phân quyền">
            <a href="{{ route('crm.roles.index') }}" class="tab {{ $tab === 'matrix' ? 'is-active' : '' }}">Ma trận phân quyền</a>
            <a href="{{ route('crm.roles.index', ['tab' => 'teams']) }}" class="tab {{ $tab === 'teams' ? 'is-active' : '' }}">
                Nhóm làm việc <span class="tab-count">{{ $teams->count() }}</span>
            </a>
        </nav>

        @if ($tab === 'matrix')
            <form method="POST" action="{{ route('crm.roles.update') }}">
                @csrf
                @method('PUT')
                <div class="table-wrap">
                    <table class="table perm-matrix">
                        <thead>
                            <tr>
                                <th>Chức năng</th>
                                @foreach ($roles as $r)
                                    <th class="perm-role">
                                        <b>{{ $r->name }}</b>
                                        <small class="muted d-block">{{ $userCounts[$r->key] ?? 0 }} người</small>
                                    </th>
                                @endforeach
                            </tr>
                            <tr class="perm-scope-row">
                                <td><b>Phạm vi dữ liệu khách</b></td>
                                @foreach ($roles as $r)
                                    <td>
                                        @if ($r->is_system)
                                            <span class="muted">{{ $scopes[$r->scope] }}</span>
                                        @else
                                            <select name="scope[{{ $r->key }}]" aria-label="Phạm vi của {{ $r->name }}">
                                                @foreach ($scopes as $key => $label)
                                                    <option value="{{ $key }}" @selected($r->scope === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups as $group => $perms)
                                <tr class="perm-group"><td colspan="{{ $roles->count() + 1 }}">{{ $group }}</td></tr>
                                @foreach ($perms as $p)
                                    <tr>
                                        <td>{{ $p->label }}</td>
                                        @foreach ($roles as $r)
                                            @php $on = $r->is_system || $r->permissions->contains('key', $p->key); @endphp
                                            <td class="perm-cell">
                                                <label class="perm-check {{ $r->is_system ? 'is-locked' : '' }}" title="{{ $r->name }}: {{ $p->label }}">
                                                    <input type="checkbox" name="perms[{{ $r->key }}][]" value="{{ $p->key }}"
                                                           @checked($on) @disabled($r->is_system)>
                                                    <span aria-hidden="true"></span>
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-foot">
                    <span class="muted">Super Admin luôn có toàn quyền. Thay đổi áp dụng ngay ở lần bấm tiếp theo của nhân viên.</span>
                    <button type="submit" class="btn btn-primary">Lưu ma trận</button>
                </div>
            </form>
        @else
            <div class="teams-wrap">
                <form method="POST" action="{{ route('crm.roles.teams.store') }}" class="team-new">
                    @csrf
                    <input type="text" name="name" placeholder="Tên nhóm mới (VD: Nhóm Hà Nội)" required maxlength="100">
                    <select name="manager_id" aria-label="Trưởng nhóm">
                        <option value="">— Trưởng nhóm —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->displayName() }} · {{ $u->crmRoleLabel() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">+ Thêm nhóm</button>
                </form>

                <table class="table">
                    <thead><tr><th>Nhóm</th><th>Trưởng nhóm</th><th>Thành viên</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($teams as $t)
                            <tr>
                                <td colspan="4" class="team-row">
                                    <form method="POST" action="{{ route('crm.roles.teams.update', $t) }}" class="team-edit">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $t->name }}" required maxlength="100" aria-label="Tên nhóm">
                                        <select name="manager_id" aria-label="Trưởng nhóm">
                                            <option value="">— Chưa có —</option>
                                            @foreach ($users as $u)
                                                <option value="{{ $u->id }}" @selected($t->manager_id === $u->id)>{{ $u->displayName() }} · {{ $u->crmRoleLabel() }}</option>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('crm.staff.index') }}" class="link team-members">{{ $t->members_count }} thành viên</a>
                                        <button type="submit" class="btn btn-outline btn-sm">Lưu</button>
                                    </form>
                                    <form method="POST" action="{{ route('crm.roles.teams.destroy', $t) }}" onsubmit="return confirm('Xoá nhóm {{ addslashes($t->name) }}? Thành viên sẽ không còn thuộc nhóm nào.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outline btn-sm">Xoá</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty"><p>Chưa có nhóm nào. Tạo nhóm và chọn trưởng nhóm (Manager), sau đó gán thành viên ở trang Quản lý nhân viên.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
