@extends('crm::layouts.app')

@section('title', 'Cấu hình CRM')

@section('content')
    <div class="page-head">
        <div>
            <h1>Cấu hình CRM</h1>
            <p class="muted">Các ngưỡng nhắc việc và cách chia khách mới</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('crm.settings.update') }}" class="card">
        @csrf @method('PUT')

        <h2 class="settings-title">Chăm sóc khách hàng</h2>
        <div class="form-grid">
            <label class="field @error('idle_days') has-error @enderror">
                <span>Nhắc chăm sóc lại sau (ngày) <i>*</i></span>
                <input type="number" name="idle_days" min="1" max="90" value="{{ old('idle_days', $settings['idle_days']) }}" required>
                <small class="muted">Khách đang chăm sóc mà chừng này ngày không có tương tác → hiện ở Dashboard và cột “Bỏ quên” của báo cáo tư vấn viên.</small>
            </label>
            <label class="field @error('closed_days') has-error @enderror">
                <span>Kanban: hiện khách đã chốt trong (ngày) <i>*</i></span>
                <input type="number" name="closed_days" min="1" max="365" value="{{ old('closed_days', $settings['closed_days']) }}" required>
                <small class="muted">Cột “Đã chuyển đổi” và “Không tiềm năng” chỉ hiện khách cập nhật trong khoảng này.</small>
            </label>
        </div>

        <h2 class="settings-title">Khách mới từ Website / Chatbot / Holland</h2>
        <div class="form-grid">
            <div class="field">
                <span>Phân công tự động</span>
                <div class="role-options">
                    <label class="role-option">
                        <input type="radio" name="auto_assign" value="off" @checked(old('auto_assign', $settings['auto_assign']) === 'off')>
                        <span><b>Tắt</b><small class="muted">Khách vào hàng “chưa phân công”, Manager / Admin tự giao.</small></span>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="auto_assign" value="round_robin" @checked(old('auto_assign', $settings['auto_assign']) === 'round_robin')>
                        <span><b>Chia đều lần lượt</b><small class="muted">Mỗi khách mới giao cho người kế tiếp trong danh sách.</small></span>
                    </label>
                </div>
            </div>
            <label class="field @error('auto_assign_role') has-error @enderror">
                <span>Chia cho nhân viên có vai trò</span>
                <select name="auto_assign_role">
                    @foreach ($roles as $key => $name)
                        <option value="{{ $key }}" @selected(old('auto_assign_role', $settings['auto_assign_role']) === $key)>
                            {{ $name }} ({{ $roleCounts[$key] ?? 0 }} người đang hoạt động)
                        </option>
                    @endforeach
                </select>
                <small class="muted">Chỉ tài khoản đang hoạt động. Không có ai phù hợp thì khách để chưa phân công.</small>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
        </div>
    </form>

    <section class="card card-flush">
        <h2 class="settings-title" style="padding: 16px 20px 0">Kênh nhận khách</h2>
        <p class="muted" style="padding: 0 20px">Tình trạng các kênh tự động gửi khách vào CRM. Kênh “Chưa kết nối” hoặc lâu không nhận khách: báo bộ phận kỹ thuật kiểm tra.</p>
        <table class="table">
            <thead><tr><th>Kênh</th><th>Trạng thái</th><th>Kết nối</th><th>Lần nhận gần nhất</th><th class="num">Tổng lượt gửi</th></tr></thead>
            <tbody>
                @foreach ($channels as $c)
                    <tr>
                        <td><b>{{ $c['label'] }}</b></td>
                        <td><span class="badge {{ $c['enabled'] ? 'badge-green' : 'badge-gray' }}">{{ $c['enabled'] ? 'Đang bật' : 'Đã tắt' }}</span></td>
                        <td><span class="badge {{ $c['has_token'] ? 'badge-green' : 'badge-amber' }}">{{ $c['has_token'] ? 'Đã kết nối' : 'Chưa kết nối' }}</span></td>
                        <td>{{ $c['last_at'] ? $c['last_at']->format('d/m/Y H:i').' ('.$c['last_at']->diffForHumans().')' : '—' }}</td>
                        <td class="num">{{ number_format($c['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
