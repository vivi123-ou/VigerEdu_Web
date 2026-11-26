@extends('crm::layouts.app')

@section('title', $label)

@php
    $trend = $stats['last_month']
        ? ($stats['this_month'] >= $stats['last_month'] ? '+' : '').round(($stats['this_month'] - $stats['last_month']) / $stats['last_month'] * 100).'% so với tháng trước'
        : 'Tháng trước: 0';
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1><span class="source source-{{ $source }}"></span> {{ $label }}</h1>
            <p class="muted">{{ $description }}</p>
        </div>
        <div class="page-actions">
            @if ($source === 'other')
                <a href="{{ route('crm.leads.create', ['source' => 'other']) }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Thêm khách hàng
                </a>
            @endif
            <a href="{{ route('crm.leads.index', ['source' => $source]) }}" class="btn btn-outline">Xem tất cả khách từ nguồn này →</a>
        </div>
    </div>

    {{-- Tình trạng kết nối --}}
    @if ($channel)
        @php
            $ok = $channel['enabled'] && $channel['configured'];
            $quiet = $ok && (! $channel['last_at'] || $channel['last_at']->lt(now()->subDays(7)));
        @endphp
        <section class="card channel-card {{ ! $ok ? 'is-off' : ($quiet ? 'is-quiet' : 'is-ok') }}">
            <span class="channel-dot"></span>
            <div class="channel-main">
                <b>
                    @if (! $channel['configured']) Kênh chưa được kết nối — báo bộ phận kỹ thuật
                    @elseif (! $channel['enabled']) Kênh đang tắt — CRM từ chối dữ liệu gửi tới
                    @elseif ($quiet) Kênh đang bật nhưng {{ $channel['last_at'] ? '7 ngày nay' : 'chưa' }} nhận được khách nào
                    @else Kết nối hoạt động bình thường
                    @endif
                </b>
                <p class="muted">
                    Lần cuối nhận: {{ $channel['last_at'] ? $channel['last_at']->format('H:i d/m/Y').' ('.$channel['last_at']->diffForHumans().')' : 'chưa có' }}
                    · {{ number_format($channel['count_30d']) }} lượt gửi trong 30 ngày · tổng {{ number_format($channel['count_all']) }}
                </p>
            </div>
        </section>
    @endif

    <section class="stat-grid">
        <div class="card stat stat-blue">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
            <div>
                <p class="stat-label">Tổng khách từ nguồn</p>
                <p class="stat-value">{{ number_format($stats['total']) }}</p>
                <p class="stat-note">{{ $stats['share'] }}% tổng số khách CRM</p>
            </div>
        </div>
        <div class="card stat stat-sky">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg></span>
            <div>
                <p class="stat-label">Khách mới tháng này</p>
                <p class="stat-value">{{ number_format($stats['this_month']) }}</p>
                <p class="stat-note">{{ $trend }}</p>
            </div>
        </div>
        <a href="{{ route('crm.leads.index', ['source' => $source, 'status' => 'new']) }}" class="card stat stat-amber">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg></span>
            <div>
                <p class="stat-label">Chưa liên hệ</p>
                <p class="stat-value">{{ number_format($stats['new']) }}</p>
                <p class="stat-note">Trạng thái “Mới” — bấm để xem</p>
            </div>
        </a>
        <div class="card stat stat-green">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></span>
            <div>
                <p class="stat-label">Đã chuyển đổi</p>
                <p class="stat-value">{{ number_format($stats['converted']) }}</p>
                <p class="stat-note">Tỷ lệ chuyển đổi {{ $stats['rate'] }}%</p>
            </div>
        </div>
    </section>

    <section class="grid-2-1">
        <div class="card">
            <div class="card-head"><div><h3>Khách mới theo tháng</h3><p class="muted">6 tháng gần nhất</p></div></div>
            <div class="chart-box"><canvas id="sourceMonthly" aria-label="Biểu đồ khách mới theo tháng"></canvas></div>
        </div>
        <div class="card">
            <div class="card-head"><div><h3>Phễu trạng thái</h3><p class="muted">Khách từ {{ $label }} đang ở đâu</p></div></div>
            <ul class="funnel">
                @foreach ($funnel as $key => $f)
                    <li>
                        <a href="{{ route('crm.leads.index', ['source' => $source, 'status' => $key]) }}">
                            <span class="funnel-label">{{ $f['label'] }}</span>
                            <span class="funnel-track"><span class="funnel-fill f-{{ $key }}" style="width: {{ max($f['pct'], $f['count'] ? 3 : 0) }}%"></span></span>
                            <span class="funnel-val">{{ $f['count'] }} <small class="muted">{{ $f['pct'] }}%</small></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    @if ($holland)
        <section class="grid-2">
            <div class="card">
                <div class="card-head">
                    <div><h3>Phân bố mã Holland</h3><p class="muted">Cặp 2 nhóm nổi bật · {{ $holland['with_result'] }} khách có kết quả</p></div>
                </div>
                @php $maxPair = max(1, $holland['pairs']->max() ?? 1); @endphp
                <ul class="funnel">
                    @forelse ($holland['pairs'] as $pair => $n)
                        <li>
                            <span class="pair-row">
                                <span class="holland-mini">@foreach (str_split($pair) as $l)<span class="h-{{ strtolower($l) }}">{{ $l }}</span>@endforeach</span>
                                <span class="funnel-track"><span class="funnel-fill f-holland" style="width: {{ round($n / $maxPair * 100) }}%"></span></span>
                                <span class="funnel-val">{{ $n }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="empty-val">Chưa có khách nào có kết quả Holland.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <div class="card-head">
                    <div><h3>Điểm trung bình theo nhóm</h3><p class="muted">Thang 1–5, trên khách nguồn Holland</p></div>
                </div>
                <div class="holland-bars">
                    @foreach (\App\Models\Lead::HOLLAND_TYPES as $l => [$name])
                        @php $avg = $holland['averages'][$l]; @endphp
                        <div class="hbar is-top">
                            <span class="hbar-label"><b>{{ $l }}</b> {{ $name }}</span>
                            <span class="hbar-track"><span class="hbar-fill h-{{ strtolower($l) }}" style="width: {{ $avg ? round(($avg - 1) / 4 * 100) : 0 }}%"></span></span>
                            <span class="hbar-val">{{ $avg ?? '—' }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($holland['reliability']->isNotEmpty())
                    <p class="muted" style="margin-top: 14px; font-size: 13px">
                        Độ tin cậy bài làm:
                        @foreach ($holland['reliability'] as $label => $n)
                            <span class="badge {{ $label === 'Tin cậy' ? 'badge-green' : ($label === 'Cần xem lại' ? 'badge-amber' : 'badge-gray') }}">{{ $label }}: {{ $n }}</span>
                        @endforeach
                    </p>
                @endif
            </div>
        </section>
    @endif

    @if ($creators !== null)
        <section class="card">
            <div class="card-head">
                <div><h3>Người nhập khách vào CRM</h3><p class="muted">Khách nguồn khác do ai thêm (nhập tay, giới thiệu, sự kiện…)</p></div>
            </div>
            @php $maxCreator = max(1, $creators->max() ?? 1); @endphp
            <ul class="funnel">
                @forelse ($creators as $name => $n)
                    <li>
                        <span class="pair-row creator-row">
                            <span class="kcard-owner"><span class="crm-avatar xs">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>{{ $name }}</span>
                            <span class="funnel-track"><span class="funnel-fill" style="width: {{ round($n / $maxCreator * 100) }}%"></span></span>
                            <span class="funnel-val">{{ $n }}</span>
                        </span>
                    </li>
                @empty
                    <li class="empty-val">Chưa có khách nào được nhập vào nguồn này.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($byHour)
        @php $peak = array_search(max($byHour), $byHour); @endphp
        <section class="card">
            <div class="card-head">
                <div>
                    <h3>Khách để lại thông tin theo giờ</h3>
                    <p class="muted">
                        90 ngày gần nhất{{ max($byHour) ? ' · cao điểm '.$peak.'h–'.($peak + 1).'h' : '' }}
                        — nên có người trực để gọi lại nhanh trong khung giờ này
                    </p>
                </div>
            </div>
            <div class="chart-box chart-box-sm"><canvas id="sourceHourly" aria-label="Biểu đồ khách theo giờ trong ngày"></canvas></div>
        </section>
    @endif

    <section class="grid-2-1">
        <div class="card card-flush">
            <div class="card-head card-head-pad">
                <div><h3>Khách mới nhất</h3><p class="muted">Từ {{ $label }}</p></div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Khách hàng</th><th>Ngày tiếp nhận</th><th>Trạng thái</th><th>Phụ trách</th></tr></thead>
                    <tbody>
                        @forelse ($recentLeads as $l)
                            <tr>
                                <td>
                                    <a href="{{ route('crm.leads.show', $l) }}" class="link-strong">{{ $l->full_name }}</a>
                                    <small class="muted d-block mono">{{ $l->phone }}</small>
                                </td>
                                <td class="muted">{{ $l->created_at->format('H:i d/m/Y') }}</td>
                                <td><span class="badge {{ $l->statusBadge() }}">{{ $l->statusLabel() }}</span></td>
                                <td>{!! $l->assignee ? e($l->assignee->displayName()) : '<span class="muted fst-italic">Chưa gán</span>' !!}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty"><p>{{ auth()->user()->crmCan('leads.view') ? 'Chưa có khách nào từ nguồn này.' : 'Bạn chỉ xem được số liệu tổng hợp, không xem danh sách khách.' }}</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><div>
                @if ($source === 'chatbot')
                    <h3>Trích đoạn chat gần đây</h3><p class="muted">Nội dung chatbot gửi kèm khi khách để lại liên hệ</p>
                @else
                    <h3>Lời nhắn gần đây</h3><p class="muted">Nội dung khách gửi qua form</p>
                @endif
            </div></div>
            <ol class="timeline timeline-compact">
                @forelse ($messages as $m)
                    <li class="tl-item tl-form">
                        <span class="tl-icon"><svg viewBox="0 0 24 24">{!! $m->typeIcon() !!}</svg></span>
                        <div class="tl-body">
                            <p class="tl-meta">
                                <b>{{ $m->created_at->diffForHumans() }}</b> ·
                                <a href="{{ route('crm.leads.show', $m->lead_id) }}#history" class="link">{{ $m->lead?->full_name }}</a>
                            </p>
                            <p class="tl-content">{!! nl2br(e(\Illuminate\Support\Str::limit($m->content, 220))) !!}</p>
                        </div>
                    </li>
                @empty
                    <li class="empty-val">Chưa có lời nhắn nào.</li>
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
        new Chart(document.getElementById('sourceMonthly'), {
            type: 'bar',
            data: {
                labels: @json($chart['labels']),
                datasets: [{ label: 'Khách mới', data: @json($chart['data']), backgroundColor: '#2563eb', borderRadius: 6, maxBarThickness: 36 }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } }
            }
        });

        var hourly = document.getElementById('sourceHourly');
        if (hourly) {
            var hours = @json($byHour ?? []);
            var peak = Math.max.apply(null, hours);
            new Chart(hourly, {
                type: 'bar',
                data: {
                    labels: hours.map(function (_, h) { return h + 'h'; }),
                    datasets: [{
                        label: 'Khách', data: hours, borderRadius: 4, maxBarThickness: 22,
                        backgroundColor: hours.map(function (v) { return v === peak && v > 0 ? '#10b981' : '#a7f3d0'; })
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } }
                }
            });
        }
    </script>
@endpush
