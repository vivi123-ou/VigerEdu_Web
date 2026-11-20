@extends('crm::layouts.app')

@section('title', 'Dashboard')

@php
    $icons = [
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
        'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    ];
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Tổng quan hoạt động hệ thống · cập nhật {{ now()->format('H:i d/m/Y') }}</p>
        </div>
        <p class="greeting">Xin chào, <b>{{ auth()->user()->displayName() }}</b></p>
    </div>

    <section class="stat-grid">
        @foreach ($stats as $s)
            <a href="{{ $s['url'] }}" class="card stat stat-{{ $s['color'] }}">
                <span class="stat-icon"><svg viewBox="0 0 24 24">{!! $icons[$s['icon']] !!}</svg></span>
                <div>
                    <p class="stat-label">{{ $s['label'] }}</p>
                    <p class="stat-value">{{ number_format($s['value']) }}</p>
                    <p class="stat-note">{{ $s['note'] }}</p>
                </div>
            </a>
        @endforeach
    </section>

    <section class="grid-2-1">
        <div class="card">
            <div class="card-head">
                <div><h3>Khách hàng theo thời gian</h3><p class="muted">Leads mới mỗi tháng · 6 tháng gần nhất</p></div>
            </div>
            <div class="chart-box"><canvas id="leadsChart" aria-label="Biểu đồ leads mới theo tháng"></canvas></div>
        </div>
        <div class="card">
            <div class="card-head">
                <div><h3>Nguồn khách hàng</h3><p class="muted">Website / Holland / Chatbot</p></div>
            </div>
            @if ($total)
                <div class="chart-box"><canvas id="sourceChart" aria-label="Biểu đồ nguồn khách hàng"></canvas></div>
            @else
                <div class="chart-box chart-empty"><p class="empty-val">Chưa có dữ liệu</p></div>
            @endif
        </div>
    </section>

    <section class="card card-flush">
        <div class="card-head card-head-pad">
            <div><h3>Khách hàng mới</h3><p class="muted">5 leads gần nhất</p></div>
            <a href="{{ route('crm.leads.index') }}" class="btn btn-ghost">Xem tất cả →</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Họ tên</th><th>Nguồn</th><th>Ngày tạo</th><th>Trạng thái</th><th>Phụ trách</th></tr>
                </thead>
                <tbody>
                    @forelse ($newLeads as $lead)
                        <tr>
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm">{{ mb_strtoupper(mb_substr($lead->full_name, 0, 1)) }}</span>
                                    <a href="{{ route('crm.leads.show', $lead) }}" class="link-strong">{{ $lead->full_name }}</a>
                                </div>
                            </td>
                            <td><span class="source source-{{ $lead->source }}">{{ $lead->sourceLabel() }}</span></td>
                            <td class="muted">{{ $lead->created_at->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $lead->statusBadge() }}">{{ $lead->statusLabel() }}</span></td>
                            <td>{!! $lead->assignee ? e($lead->assignee->displayName()) : '<span class="muted fst-italic">Chưa gán</span>' !!}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">
                                <p>Chưa có khách hàng nào.</p>
                                <a href="{{ route('crm.leads.create') }}" class="link">+ Thêm khách hàng đầu tiên</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid-2">
        <div class="card">
            <div class="card-head"><h3>Công việc cần xử lý</h3></div>
            <ul class="task-list">
                @foreach ($tasks as $t)
                    @php $done = $t['count'] === 0; @endphp
                    <li>
                        <a @if ($t['url']) href="{{ $t['url'] }}" @endif class="task task-{{ $done ? 'done' : $t['level'] }}">
                            <b>{{ $t['count'] }}</b> {{ $t['text'] }}
                            @if ($t['url'] && ! $done)<span class="task-go">→</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card">
            <div class="card-head"><h3>Hoạt động gần đây</h3><span class="muted">Lịch sử tư vấn</span></div>
            <ol class="timeline timeline-compact">
                @forelse ($activities as $a)
                    <li class="tl-item tl-{{ $a->type }}">
                        <span class="tl-icon"><svg viewBox="0 0 24 24">{!! $a->typeIcon() !!}</svg></span>
                        <div class="tl-body">
                            <p class="tl-meta">
                                <b>{{ $a->created_at->diffForHumans() }}</b> ·
                                <a href="{{ route('crm.leads.show', $a->lead_id) }}#history" class="link">{{ $a->lead->full_name }}</a>
                                @if ($a->user) · {{ $a->user->displayName() }}@endif
                            </p>
                            <p class="tl-content">{{ \Illuminate\Support\Str::limit($a->content, 110) }}</p>
                        </div>
                    </li>
                @empty
                    <li class="empty-val">Chưa có hoạt động nào.</li>
                @endforelse
            </ol>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.color = '#64748b';

        new Chart(document.getElementById('leadsChart'), {
            type: 'line',
            data: {
                labels: @json($leadsChart['labels']),
                datasets: [{
                    label: 'Leads mới', data: @json($leadsChart['data']),
                    borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)',
                    fill: true, tension: .35, pointRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } }
            }
        });

        var sourceEl = document.getElementById('sourceChart');
        if (sourceEl) new Chart(sourceEl, {
            type: 'doughnut',
            data: {
                labels: @json($sourceChart['labels']),
                datasets: [{ data: @json($sourceChart['data']), backgroundColor: ['#2563eb', '#f59e0b', '#10b981', '#cbd5e1'], borderWidth: 0 }]
            },
            options: {
                maintainAspectRatio: false, cutout: '68%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }
            }
        });
    </script>
@endpush
