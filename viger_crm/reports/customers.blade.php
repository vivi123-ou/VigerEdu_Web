@extends('crm::layouts.app')

@section('title', 'Tổng quan khách hàng')

@use('App\Http\Controllers\Crm\ReportController')

@php
    // So với kỳ trước: +12% / −5% / "mới" khi kỳ trước bằng 0
    $delta = function (string $key, bool $isRate = false) use ($current, $previous) {
        $cur = $current[$key];
        $prev = $previous[$key];
        if ($isRate) {
            $d = round($cur - $prev, 1);

            return ['text' => ($d > 0 ? '+' : '').$d.' điểm %', 'up' => $d >= 0];
        }
        if (! $prev) {
            return ['text' => $cur ? 'kỳ trước: 0' : '—', 'up' => true];
        }
        $d = round(($cur - $prev) / $prev * 100);

        return ['text' => ($d > 0 ? '+' : '').$d.'% (kỳ trước: '.$prev.')', 'up' => $d >= 0];
    };
    $cards = [
        ['key' => 'new', 'label' => 'Khách mới', 'value' => number_format($current['new']), 'color' => 'blue', 'good' => true],
        ['key' => 'converted', 'label' => 'Khách chốt trong kỳ', 'value' => number_format($current['converted']), 'color' => 'green', 'good' => true],
        ['key' => 'rate', 'label' => 'Tỷ lệ chuyển đổi', 'value' => $current['rate'].'%', 'color' => 'amber', 'good' => true, 'rate' => true],
        ['key' => 'lost', 'label' => 'Chuyển sang không tiềm năng', 'value' => number_format($current['lost']), 'color' => 'sky', 'good' => false],
    ];
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Tổng quan khách hàng</h1>
            <p class="muted">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }} · so với {{ $prevFrom->format('d/m/Y') }} – {{ $prevTo->format('d/m/Y') }}</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        “Khách chốt trong kỳ” tính theo ngày đổi trạng thái sang Đã chuyển đổi (có thể là khách tiếp nhận từ kỳ trước).
        Tỷ lệ chuyển đổi = khách chốt ÷ khách mới trong kỳ.
    </p>

    <form method="GET" action="{{ route('crm.reports.customers') }}" class="card toolbar toolbar-row period-bar" id="periodForm">
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
        @foreach ($cards as $c)
            @php $d = $delta($c['key'], $c['rate'] ?? false); $positive = $d['up'] === $c['good']; @endphp
            <div class="card stat stat-{{ $c['color'] }}">
                <div>
                    <p class="stat-label">{{ $c['label'] }}</p>
                    <p class="stat-value">{{ $c['value'] }}</p>
                    <p class="stat-delta {{ $d['text'] === '—' ? '' : ($positive ? 'is-good' : 'is-bad') }}">{{ $d['text'] }}</p>
                </div>
            </div>
        @endforeach
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h3>Khách mới và khách chốt</h3>
                <p class="muted">Theo {{ $trend['daily'] ? 'ngày' : 'tháng' }} · {{ number_format($current['contacted']) }}/{{ number_format($current['new']) }} khách mới đã được liên hệ</p>
            </div>
        </div>
        <div class="chart-box"><canvas id="trendChart" aria-label="Biểu đồ khách mới và khách chốt"></canvas></div>
    </section>

    <section class="card card-flush">
        <div class="card-head card-head-pad">
            <div><h3>Hiệu quả theo nguồn</h3><p class="muted">Khách mới trong kỳ và số đã chốt</p></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nguồn</th><th>Khách mới</th><th>Tỷ trọng</th><th>Đã chốt</th><th>Tỷ lệ chuyển đổi</th><th></th></tr></thead>
                <tbody>
                    @foreach ($bySource as $key => $s)
                        <tr>
                            <td><span class="source source-{{ $key }}">{{ $s['label'] }}</span></td>
                            <td><b>{{ number_format($s['new']) }}</b></td>
                            <td>
                                <span class="share"><span class="share-bar"><span style="width: {{ $s['share'] }}%"></span></span>{{ $s['share'] }}%</span>
                            </td>
                            <td>{{ number_format($s['converted']) }}</td>
                            <td><span class="days {{ $s['rate'] >= 20 ? 'days-fast' : ($s['rate'] >= 10 ? 'days-mid' : ($s['new'] ? 'days-slow' : '')) }}">{{ $s['rate'] }}%</span></td>
                            <td class="actions"><a href="{{ route('crm.sources.show', $key) }}" class="link">Chi tiết →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <h2 class="section-h">Chân dung khách mới trong kỳ</h2>
    <section class="grid-2">
        @foreach ($profile as $field => $p)
            @php $max = max(1, $p['items']->max() ?? 1); $sum = $p['items']->sum(); @endphp
            <div class="card">
                <div class="card-head"><h3>{{ $p['title'] }}</h3><span class="muted">{{ $sum }}/{{ $current['new'] }} khách có thông tin</span></div>
                <ul class="funnel">
                    @forelse ($p['items'] as $value => $n)
                        <li>
                            <span class="pair-row creator-row">
                                <span class="kcard-owner" title="{{ $value }}">{{ $value }}</span>
                                <span class="funnel-track"><span class="funnel-fill" style="width: {{ round($n / $max * 100) }}%"></span></span>
                                <span class="funnel-val">{{ $n }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="empty-val">Chưa có dữ liệu trong kỳ.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.querySelectorAll('input[name="period"]').forEach(function (r) {
            r.addEventListener('change', function () {
                document.querySelector('.period-custom').hidden = r.value !== 'custom' || !r.checked;
            });
        });

        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.color = '#64748b';
        new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels: @json($trend['labels']),
                datasets: [
                    { label: 'Khách mới', data: @json($trend['new']), backgroundColor: '#93c5fd', borderRadius: 4, maxBarThickness: 28 },
                    { label: 'Khách chốt', data: @json($trend['converted']), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 28 }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } }
            }
        });
    </script>
@endpush
