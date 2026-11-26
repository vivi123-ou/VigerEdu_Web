@extends('crm::layouts.app')

@section('title', 'Nhật ký hệ thống')

@section('content')
    <div class="page-head">
        <div>
            <h1>Nhật ký hệ thống</h1>
            <p class="muted">Ai đã làm thao tác quan trọng gì, lúc nào, từ đâu</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Ghi lại: đăng nhập (cả đăng nhập sai), đổi phân quyền, nhóm, tạo / sửa / khoá nhân viên, đặt lại mật khẩu,
        xoá khách, xuất dữ liệu, sửa / xoá ghi chú, đổi cấu hình. Nhật ký chỉ xem, không ai sửa hay xoá được.
        Khách hàng chỉ ghi theo mã KH, không ghi tên / SĐT.
    </p>

    @if ($failedToday >= 5)
        <div class="alert alert-danger">Hôm nay có {{ $failedToday }} lần đăng nhập sai — kiểm tra cột IP bên dưới.</div>
    @endif

    <section class="card card-flush">
        <form method="GET" action="{{ route('crm.audit.index') }}" class="toolbar toolbar-row" id="auditFilter">
            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nội dung, mã KH, tên đăng nhập hoặc IP...">
            </div>
            <div class="filters">
                <select name="action" data-autosubmit aria-label="Loại thao tác">
                    <option value="">Mọi thao tác</option>
                    @foreach ($actions as $key => [$label])
                        <option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="user" data-autosubmit aria-label="Người thực hiện">
                    <option value="">Mọi người</option>
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['user'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="date" data-autosubmit aria-label="Thời gian">
                    <option value="">Mọi lúc</option>
                    <option value="today" @selected(($filters['date'] ?? '') === 'today')>Hôm nay</option>
                    <option value="7d" @selected(($filters['date'] ?? '') === '7d')>7 ngày qua</option>
                    <option value="30d" @selected(($filters['date'] ?? '') === '30d')>30 ngày qua</option>
                </select>
                @if (array_filter($filters))
                    <a href="{{ route('crm.audit.index') }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Chi tiết</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user?->displayName() ?? '—' }}</td>
                            <td><span class="badge badge-{{ $log->color() }}">{{ $log->label() }}</span></td>
                            <td class="audit-desc">{{ $log->description }}</td>
                            <td class="muted nowrap">{{ $log->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty"><p>Chưa có thao tác nào khớp bộ lọc.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $logs->firstItem() }}–{{ $logs->lastItem() }} / {{ $logs->total() }}</span>
                {{ $logs->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () { document.getElementById('auditFilter').submit(); });
        });
    </script>
@endpush
