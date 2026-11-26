@extends('crm::layouts.app')

@section('title', 'Hiệu suất tư vấn viên')

@use('App\Models\LeadActivity')
@use('App\Http\Controllers\Crm\ReportController')

@php
    $fmtHours = function (?float $h) {
        if ($h === null) {
            return '—';
        }

        return $h < 1 ? round($h * 60).' phút' : ($h < 48 ? $h.' giờ' : round($h / 24, 1).' ngày');
    };
    $speedClass = fn (?float $h) => $h === null ? '' : ($h <= 2 ? 'days-fast' : ($h <= 24 ? 'days-mid' : 'days-slow'));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Hiệu suất tư vấn viên</h1>
            <p class="muted">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Tỷ lệ chốt = khách chốt trong kỳ ÷ khách mới được giao trong kỳ. “Liên hệ lần đầu” là thời gian trung vị từ lúc khách vào CRM đến cuộc gọi / ghi chú / lịch hẹn đầu tiên.
        “Bỏ quên” = khách đang chăm sóc mà {{ \App\Models\CrmSetting::get('idle_days') }} ngày chưa có tương tác.
    </p>

    <form method="GET" action="{{ route('crm.reports.consultants') }}" class="card toolbar toolbar-row period-bar">
        <div class="period-tabs" role="radiogroup" aria-label="Khoảng thời gian">
            @foreach (ReportController::PERIODS as $key => $label)
                <label class="chip">
                    <input type="radio" name="period" value="{{ $key }}" @checked($period === $key) @if ($key !== 'custom') onchange="this.form.submit()" @endif>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <div class="period-custom" @if ($period !== 'custom') hidden @endif>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" aria-label="Từ ngày">
            <span class="muted">→</span>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" aria-label="Đến ngày">
            <button type="submit" class="btn btn-primary btn-sm">Xem</button>
        </div>
    </form>

    <section class="stat-grid">
        <div class="card stat stat-blue"><div>
            <p class="stat-label">Tổng hoạt động</p>
            <p class="stat-value">{{ number_format($totals['activities']) }}</p>
            <p class="stat-note">Cuộc gọi, ghi chú, gặp mặt, tin nhắn, email</p>
        </div></div>
        <div class="card stat stat-green"><div>
            <p class="stat-label">Khách chốt</p>
            <p class="stat-value">{{ number_format($totals['converted']) }}</p>
            <p class="stat-note">Của khách đang được phân công</p>
        </div></div>
        <a href="{{ route('crm.customers.index', ['sort' => 'idle', 'tab' => 'open']) }}" class="card stat stat-amber"><div>
            <p class="stat-label">Khách bị bỏ quên</p>
            <p class="stat-value">{{ number_format($totals['idle']) }}</p>
            <p class="stat-note">{{ \App\Models\CrmSetting::get('idle_days') }} ngày chưa tương tác — bấm để xem</p>
        </div></a>
        <a href="{{ route('crm.leads.index', ['assigned_to' => 'none']) }}" class="card stat stat-sky"><div>
            <p class="stat-label">Chưa phân công</p>
            <p class="stat-value">{{ number_format($totals['unassigned']) }}</p>
            <p class="stat-note">Khách đang chăm sóc chưa có người phụ trách</p>
        </div></a>
    </section>

    <section class="card card-flush">
        <div class="card-head card-head-pad">
            <div><h3>Bảng hiệu suất</h3><p class="muted">Sắp theo số khách chốt, rồi số hoạt động</p></div>
        </div>
        <div class="table-wrap">
            <table class="table perf-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tư vấn viên</th>
                        <th title="Khách đang chăm sóc">Phụ trách</th>
                        <th title="Khách mới được giao trong kỳ">Giao mới</th>
                        <th>Hoạt động</th>
                        <th title="Lịch hẹn hoàn thành / khách vắng mặt">Lịch hẹn</th>
                        <th>Chốt</th>
                        <th>Tỷ lệ chốt</th>
                        <th>Liên hệ lần đầu</th>
                        <th>Bỏ quên</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $i => $r)
                        <tr>
                            <td class="muted">{{ $i + 1 }}</td>
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm">{{ mb_strtoupper(mb_substr($r['user']->displayName(), 0, 1)) }}</span>
                                    <a href="{{ route('crm.leads.index', ['assigned_to' => $r['user']->id]) }}" class="link-strong">{{ $r['user']->displayName() }}</a>
                                </div>
                            </td>
                            <td>{{ $r['open'] }}</td>
                            <td>{{ $r['new_assigned'] }}</td>
                            <td>
                                <b>{{ $r['activities_total'] }}</b>
                                <small class="muted d-block">
                                    {{ collect($r['activities'])->filter()->map(fn ($n, $t) => LeadActivity::TYPES[$t][0].' '.$n)->implode(' · ') ?: '—' }}
                                </small>
                            </td>
                            <td>{{ $r['appt_done'] }} <span class="muted">/ {{ $r['appt_no_show'] }} vắng</span></td>
                            <td><b>{{ $r['converted'] }}</b></td>
                            <td>
                                @if ($r['rate'] !== null)
                                    <span class="days {{ $r['rate'] >= 20 ? 'days-fast' : ($r['rate'] >= 10 ? 'days-mid' : 'days-slow') }}">{{ $r['rate'] }}%</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td><span class="days {{ $speedClass($r['first_response_h']) }}">{{ $fmtHours($r['first_response_h']) }}</span></td>
                            <td>
                                @if ($r['idle'])
                                    <a href="{{ route('crm.customers.index', ['assigned_to' => $r['user']->id, 'tab' => 'open', 'sort' => 'idle']) }}" class="text-warn">{{ $r['idle'] }} khách</a>
                                @else
                                    <span class="muted">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="empty"><p>Chưa có tư vấn viên nào phụ trách khách hoặc có hoạt động trong kỳ.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($rows->isNotEmpty())
        <section class="card">
            <div class="card-head"><div><h3>Hoạt động theo tư vấn viên</h3><p class="muted">Số hoạt động từng loại trong kỳ</p></div></div>
            <div class="chart-box" style="height: {{ max(220, $rows->count() * 46) }}px"><canvas id="perfChart" aria-label="Biểu đồ hoạt động theo tư vấn viên"></canvas></div>
        </section>
    @endif
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.querySelectorAll('input[name="period"]').forEach(function (r) {
            r.addEventListener('change', function () {
                document.querySelector('.period-custom').hidden = r.value !== 'custom' || !r.checked;
            });
        });

        var el = document.getElementById('perfChart');
        if (el) {
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#64748b';
            var colors = { note: '#2563eb', call: '#10b981', meeting: '#8b5cf6', message: '#0ea5e9', email: '#f59e0b' };
            var labels = @json(\App\Models\LeadActivity::MANUAL_TYPES);
            var names = @json(collect(\App\Models\LeadActivity::TYPES)->map(fn ($t) => $t[0]));
            var rows = @json($rows->map(fn ($r) => ['name' => $r['user']->displayName(), 'activities' => $r['activities']]));

            new Chart(el, {
                type: 'bar',
                data: {
                    labels: rows.map(function (r) { return r.name; }),
                    datasets: labels.map(function (t) {
                        return { label: names[t], data: rows.map(function (r) { return r.activities[t]; }), backgroundColor: colors[t], maxBarThickness: 26 };
                    })
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                    scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, y: { stacked: true, grid: { display: false } } }
                }
            });
        }
    </script>
@endpush
