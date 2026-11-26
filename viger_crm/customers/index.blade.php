@extends('crm::layouts.app')

@section('title', 'Danh sách khách hàng')

@use('App\Models\Lead')
@use('App\Http\Controllers\Crm\CustomerController')

@php
    $hasFilter = collect($filters)->filter()->isNotEmpty();
    $tabUrl = fn ($key) => route('crm.customers.index', array_merge(request()->except('page', 'tab'), $key === 'all' ? [] : ['tab' => $key]));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Danh sách khách hàng</h1>
            <p class="muted">Danh bạ toàn bộ khách hàng · {{ number_format($counts['all']) }} khách</p>
        </div>
        <div class="page-actions">
            @if (auth()->user()->crmCan('leads.create'))
            <a href="{{ route('crm.leads.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Thêm khách hàng
            </a>
            @endif
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Trang này liệt kê mọi khách hàng, kể cả đã chuyển đổi hoặc không tiềm năng. Sắp xếp “Lâu chưa liên hệ” để tìm khách cần chăm sóc lại.
    </p>

    <section class="card card-flush">
        <nav class="tabs" aria-label="Nhóm khách hàng">
            @foreach (CustomerController::TABS as $key => [$label])
                <a href="{{ $tabUrl($key) }}" class="tab {{ $tab === $key ? 'is-active' : '' }}" @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span class="tab-count">{{ number_format($counts[$key]) }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('crm.customers.index') }}" class="toolbar toolbar-row" id="customerFilter">
            @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif

            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên phụ huynh, học sinh, SĐT, email...">
            </div>

            <div class="filters">
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
                <select name="sort" data-autosubmit aria-label="Sắp xếp">
                    @foreach (CustomerController::SORTS as $key => $label)
                        <option value="{{ $key }}" @selected($sort === $key)>Sắp xếp: {{ $label }}</option>
                    @endforeach
                </select>
                @if ($hasFilter)
                    <a href="{{ route('crm.customers.index', $tab === 'all' ? [] : ['tab' => $tab]) }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Liên hệ</th>
                        <th>Học sinh</th>
                        <th>Trạng thái</th>
                        <th>Phụ trách</th>
                        <th>Tương tác gần nhất</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $c)
                        @php $last = $c->last_activity_at ? \Illuminate\Support\Carbon::parse($c->last_activity_at) : null; @endphp
                        <tr>
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm">{{ mb_strtoupper(mb_substr($c->full_name, 0, 1)) }}</span>
                                    <div>
                                        <a href="{{ route('crm.leads.show', $c) }}" class="link-strong">{{ $c->full_name }}</a>
                                        <small class="muted d-block mono">{{ $c->code() }}{{ $c->relationship ? ' · '.$c->relationship : '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="tel:{{ $c->phone }}" class="link mono">{{ $c->phone }}</a>
                                @if ($c->email)<small class="muted d-block">{{ $c->email }}</small>@endif
                            </td>
                            <td>
                                @if ($c->student_name)
                                    {{ $c->student_name }}
                                    @if ($c->program_interest)<small class="muted d-block">{{ $c->program_interest }}</small>@endif
                                @else
                                    <span class="empty-val">Chưa có</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $c->statusBadge() }}">{{ $c->statusLabel() }}</span>
                                <small class="muted d-block"><span class="source source-{{ $c->source }}">{{ $c->sourceLabel() }}</span></small>
                            </td>
                            <td>
                                @if ($c->assignee)
                                    {{ $c->assignee->displayName() }}
                                @else
                                    <span class="muted fst-italic">Chưa gán</span>
                                @endif
                            </td>
                            <td>
                                @if ($last)
                                    <span class="{{ $last->lt(now()->subDays(7)) && $tab !== 'lost' ? 'text-warn' : '' }}" title="{{ $last->format('d/m/Y H:i') }}">
                                        {{ $last->diffForHumans() }}
                                    </span>
                                    <small class="muted d-block">{{ $c->activities_count }} hoạt động</small>
                                @else
                                    <span class="empty-val">Chưa có</span>
                                @endif
                            </td>
                            <td class="actions">
                                <a href="tel:{{ $c->phone }}" class="icon-link" title="Gọi {{ $c->phone }}">
                                    <svg viewBox="0 0 24 24">{!! \App\Models\LeadActivity::TYPES['call'][1] !!}</svg>
                                </a>
                                <a href="{{ route('crm.leads.show', $c) }}#notes" class="icon-link" title="Thêm ghi chú">
                                    <svg viewBox="0 0 24 24">{!! \App\Models\LeadActivity::TYPES['note'][1] !!}</svg>
                                </a>
                                <a href="{{ route('crm.leads.show', $c) }}" class="icon-link" title="Xem hồ sơ">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                <p>Không có khách hàng nào{{ $hasFilter ? ' khớp bộ lọc' : '' }}.</p>
                                @if ($hasFilter)
                                    <a href="{{ route('crm.customers.index') }}" class="link">Xoá bộ lọc</a>
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
            el.addEventListener('change', function () { document.getElementById('customerFilter').submit(); });
        });
    </script>
@endpush
