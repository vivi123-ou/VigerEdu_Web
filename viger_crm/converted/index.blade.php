@extends('crm::layouts.app')

@section('title', 'Khách hàng đã chuyển đổi')

@use('App\Models\Lead')
@use('App\Http\Controllers\Crm\ConvertedController')

@php
    $hasFilter = collect($filters)->filter()->isNotEmpty();
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Khách hàng đã chuyển đổi</h1>
            <p class="muted">Khách đã chốt dịch vụ với VIGER EDU</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Khách xuất hiện ở đây khi trạng thái được đổi sang “Đã chuyển đổi”. Ngày chuyển đổi lấy từ lịch sử tư vấn.
    </p>

    <section class="stat-grid">
        <div class="card stat stat-green">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></span>
            <div>
                <p class="stat-label">Đã chuyển đổi{{ $hasFilter ? ' (theo bộ lọc)' : '' }}</p>
                <p class="stat-value">{{ number_format($summary['total']) }}</p>
                <p class="stat-note">
                    @forelse ($summary['by_source'] as $key => $n){{ Lead::SOURCES[$key] }}: {{ $n }}@if (! $loop->last) · @endif
                    @empty Chưa có
                    @endforelse
                </p>
            </div>
        </div>
        <div class="card stat stat-blue">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
            <div>
                <p class="stat-label">Chốt trong tháng này</p>
                <p class="stat-value">{{ number_format($summary['this_month']) }}</p>
                <p class="stat-note">Tháng {{ now()->format('m/Y') }}</p>
            </div>
        </div>
        <div class="card stat stat-amber">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span>
            <div>
                <p class="stat-label">Tỷ lệ chuyển đổi</p>
                <p class="stat-value">{{ $summary['rate'] }}%</p>
                <p class="stat-note">Trên toàn bộ khách hàng</p>
            </div>
        </div>
        <div class="card stat stat-sky">
            <span class="stat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <div>
                <p class="stat-label">Thời gian chốt trung bình</p>
                <p class="stat-value">{{ $summary['avg_days'] !== null ? $summary['avg_days'].' ngày' : '—' }}</p>
                <p class="stat-note">Từ lúc tiếp nhận tới lúc chuyển đổi</p>
            </div>
        </div>
    </section>

    <section class="card card-flush">
        <form method="GET" action="{{ route('crm.converted.index') }}" class="toolbar toolbar-row" id="convertedFilter">
            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên phụ huynh, học sinh, SĐT, email...">
            </div>
            <div class="filters">
                <select name="period" data-autosubmit aria-label="Thời gian chuyển đổi">
                    <option value="">Mọi thời gian</option>
                    @foreach (ConvertedController::PERIODS as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['period'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="source" data-autosubmit aria-label="Nguồn">
                    <option value="">Mọi nguồn</option>
                    @foreach (Lead::SOURCES as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['source'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="assigned_to" data-autosubmit aria-label="Tư vấn viên">
                    <option value="">Mọi tư vấn viên</option>
                    <option value="none" @selected(($filters['assigned_to'] ?? '') === 'none')>— Chưa gán —</option>
                    @foreach ($consultants as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @if ($hasFilter)
                    <a href="{{ route('crm.converted.index') }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Học sinh · Chương trình</th>
                        <th>Nguồn</th>
                        <th>Tư vấn viên</th>
                        <th>Tiếp nhận</th>
                        <th>Chuyển đổi</th>
                        <th>Thời gian chốt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $c)
                        @php
                            $convertedAt = ConvertedController::convertedAt($c);
                            $days = (int) $c->created_at->diffInDays($convertedAt);
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm avatar-green">{{ mb_strtoupper(mb_substr($c->full_name, 0, 1)) }}</span>
                                    <div>
                                        <a href="{{ route('crm.leads.show', $c) }}" class="link-strong">{{ $c->full_name }}</a>
                                        <small class="muted d-block"><span class="mono">{{ $c->code() }}</span> · <a href="tel:{{ $c->phone }}" class="link mono">{{ $c->phone }}</a></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $c->student_name ?? '—' }}
                                @if ($c->program_interest)<small class="muted d-block">{{ $c->program_interest }}</small>@endif
                            </td>
                            <td><span class="source source-{{ $c->source }}">{{ $c->sourceLabel() }}</span></td>
                            <td>
                                @if ($c->assignee)
                                    {{ $c->assignee->displayName() }}
                                @else
                                    <span class="muted fst-italic">Chưa gán</span>
                                @endif
                            </td>
                            <td class="muted">{{ $c->created_at->format('d/m/Y') }}</td>
                            <td>
                                <b>{{ $convertedAt->format('d/m/Y') }}</b>
                                @unless ($c->converted_log_at)<small class="muted d-block" title="Không có dòng đổi trạng thái trong lịch sử">ước tính</small>@endunless
                            </td>
                            <td><span class="days {{ $days <= 14 ? 'days-fast' : ($days <= 45 ? 'days-mid' : 'days-slow') }}">{{ $days }} ngày</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                                <p>Chưa có khách hàng chuyển đổi{{ $hasFilter ? ' khớp bộ lọc' : '' }}.</p>
                                @if ($hasFilter)
                                    <a href="{{ route('crm.converted.index') }}" class="link">Xoá bộ lọc</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $customers->firstItem() }}–{{ $customers->lastItem() }} / {{ $customers->total() }}</span>
                {{ $customers->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () { document.getElementById('convertedFilter').submit(); });
        });
    </script>
@endpush
