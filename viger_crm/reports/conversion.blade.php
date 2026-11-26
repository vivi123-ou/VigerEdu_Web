@extends('crm::layouts.app')

@section('title', 'Báo cáo chuyển đổi')

@use('App\Http\Controllers\Crm\ReportController')

@php
    $fmtDays = fn (?float $d) => $d === null ? '—' : ($d < 1 ? round($d * 24).' giờ' : $d.' ngày');
    $rateClass = fn (float $r, int $n) => ! $n ? '' : ($r >= 20 ? 'days-fast' : ($r >= 10 ? 'days-mid' : 'days-slow'));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Báo cáo chuyển đổi</h1>
            <p class="muted">Nhóm khách vào CRM từ {{ $from->format('d/m/Y') }} đến {{ $to->format('d/m/Y') }} · {{ number_format($size) }} khách</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Phễu tính trên nhóm khách vào CRM trong kỳ: mỗi giai đoạn là số khách đã từng đạt tới (kể cả sau đó bị loại).
        “Qua bước” = tỷ lệ từ giai đoạn trước sang giai đoạn này.
    </p>

    <form method="GET" action="{{ route('crm.reports.conversion') }}" class="card toolbar toolbar-row period-bar">
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

    <section class="grid-2-1">
        <div class="card">
            <div class="card-head">
                <div><h3>Phễu chuyển đổi</h3><p class="muted">{{ $lost }} khách đã chuyển sang Không tiềm năng</p></div>
            </div>
            <div class="cfunnel">
                @foreach ($funnel as $stage => $f)
                    <div class="cfunnel-row">
                        <div class="cfunnel-label">
                            <b>{{ $f['label'] }}</b>
                            @if ($f['step'] !== null)
                                <span class="cfunnel-step {{ $f['step'] >= 50 ? 'is-good' : ($f['step'] >= 25 ? 'is-mid' : 'is-bad') }}">↓ qua bước {{ $f['step'] }}%</span>
                            @endif
                        </div>
                        <div class="cfunnel-bar-wrap">
                            <div class="cfunnel-bar f-{{ $stage }}" style="width: {{ max($f['pct'], $f['count'] ? 4 : 0) }}%">
                                <span>{{ number_format($f['count']) }}</span>
                            </div>
                        </div>
                        <span class="cfunnel-pct">{{ $f['pct'] }}%</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-head"><div><h3>Thời gian chuyển đổi</h3><p class="muted">Trung vị, từ lúc khách vào CRM</p></div></div>
            <ul class="timing-list">
                @foreach ($timing as $stage => $t)
                    <li>
                        <span class="muted">Tới “{{ $t['label'] }}”</span>
                        <b>{{ $fmtDays($t['days']) }}</b>
                    </li>
                @endforeach
            </ul>
            <p class="muted" style="font-size: 12.5px; margin-top: 12px">Chỉ tính khách có dòng lịch sử đổi trạng thái (khách nhập sẵn trạng thái không có thời điểm).</p>
        </div>
    </section>

    <section class="grid-2">
        @foreach (['bySource' => 'Theo nguồn', 'byProgram' => 'Theo chương trình quan tâm'] as $var => $title)
            <div class="card card-flush">
                <div class="card-head card-head-pad"><h3>{{ $title }}</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ $var === 'bySource' ? 'Nguồn' : 'Chương trình' }}</th><th>Khách</th><th>Đã chốt</th><th>Đã loại</th><th>Tỷ lệ chốt</th></tr></thead>
                        <tbody>
                            @forelse ($$var as $r)
                                <tr>
                                    <td>{{ $r['label'] }}</td>
                                    <td>{{ $r['size'] }}</td>
                                    <td><b>{{ $r['converted'] }}</b></td>
                                    <td class="muted">{{ $r['lost'] }}</td>
                                    <td><span class="days {{ $rateClass($r['rate'], $r['size']) }}">{{ $r['rate'] }}%</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty"><p>Chưa có khách trong kỳ.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </section>

    <section class="card card-flush">
        <div class="card-head card-head-pad">
            <div><h3>Theo tháng tiếp nhận</h3><p class="muted">6 tháng gần nhất — khách vào trong tháng, tình trạng tới hôm nay</p></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Tháng</th><th>Khách vào</th><th>Đang chăm sóc</th><th>Đã chốt</th><th>Đã loại</th><th>Tỷ lệ chốt</th><th style="width: 30%"></th></tr></thead>
                <tbody>
                    @foreach ($cohorts as $c)
                        @php
                            $w = fn ($n) => $c['size'] ? round($n / $c['size'] * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td><b>{{ $c['label'] }}</b></td>
                            <td>{{ $c['size'] }}</td>
                            <td>{{ $c['open'] }}</td>
                            <td><b>{{ $c['converted'] }}</b></td>
                            <td class="muted">{{ $c['lost'] }}</td>
                            <td><span class="days {{ $rateClass($c['rate'], $c['size']) }}">{{ $c['rate'] }}%</span></td>
                            <td>
                                @if ($c['size'])
                                    <span class="stack-bar" title="Chốt {{ $c['converted'] }} · Đang chăm sóc {{ $c['open'] }} · Loại {{ $c['lost'] }}">
                                        <span class="sb-converted" style="width: {{ $w($c['converted']) }}%"></span>
                                        <span class="sb-open" style="width: {{ $w($c['open']) }}%"></span>
                                        <span class="sb-lost" style="width: {{ $w($c['lost']) }}%"></span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('input[name="period"]').forEach(function (r) {
            r.addEventListener('change', function () {
                document.querySelector('.period-custom').hidden = r.value !== 'custom' || !r.checked;
            });
        });
    </script>
@endpush
