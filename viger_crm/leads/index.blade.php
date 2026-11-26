@extends('crm::layouts.app')

@section('title', 'Khách hàng tiềm năng')

@use('App\Models\Lead')

@php
    $activeFilters = collect($filters)->except('q')->filter()->count();
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Leads</h1>
            <p class="muted">Quản lý khách hàng tiềm năng · {{ number_format($leads->total()) }} khách hàng</p>
        </div>
        <div class="page-actions">
            @if (auth()->user()->crmCan('leads.create'))
            <a href="{{ route('crm.leads.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Thêm khách hàng
            </a>
            @endif
            @if (auth()->user()->crmCan('leads.export'))
            <a href="{{ route('crm.leads.export', request()->query()) }}" class="btn btn-outline">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Xuất Excel
            </a>
            @endif
            <button type="button" class="btn btn-outline {{ $activeFilters ? 'is-on' : '' }}" data-filter-toggle>
                <svg viewBox="0 0 24 24"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"/></svg> Bộ lọc
                @if ($activeFilters)<span class="count">{{ $activeFilters }}</span>@endif
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="card card-flush">
        <form method="GET" action="{{ route('crm.leads.index') }}" class="toolbar" id="leadFilter">
            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo tên phụ huynh, học sinh / SĐT / Email...">
            </div>

            <div class="filters" @unless ($activeFilters) hidden @endunless data-filters>
                <select name="status" data-autosubmit>
                    <option value="">Trạng thái</option>
                    @foreach (Lead::STATUSES as $key => [$label])
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="source" data-autosubmit>
                    <option value="">Nguồn</option>
                    @foreach (Lead::SOURCES as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['source'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="assigned_to" data-autosubmit>
                    <option value="">Tư vấn viên</option>
                    <option value="none" @selected(($filters['assigned_to'] ?? '') === 'none')>— Chưa gán —</option>
                    @foreach ($consultants as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="date" data-autosubmit>
                    <option value="">Ngày tạo</option>
                    @foreach (Lead::DATE_RANGES as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['date'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @if (collect($filters)->filter()->isNotEmpty())
                    <a href="{{ route('crm.leads.index') }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-stt">STT</th>
                        <th>Họ tên</th>
                        <th>SĐT</th>
                        <th>Nguồn</th>
                        <th>Trạng thái</th>
                        <th>Phụ trách</th>
                        <th>Ngày tạo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td class="muted">{{ str_pad($leads->firstItem() + $loop->index, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm">{{ mb_strtoupper(mb_substr($lead->full_name, 0, 1)) }}</span>
                                    <div>
                                        <a href="{{ route('crm.leads.show', $lead) }}" class="link-strong">{{ $lead->full_name }}</a>
                                        @if ($lead->email)<small class="muted d-block">{{ $lead->email }}</small>@endif
                                    </div>
                                </div>
                            </td>
                            <td><a href="tel:{{ $lead->phone }}" class="link">{{ $lead->phone }}</a></td>
                            <td><span class="source source-{{ $lead->source }}">{{ $lead->sourceLabel() }}</span></td>
                            <td><span class="badge {{ $lead->statusBadge() }}">{{ $lead->statusLabel() }}</span></td>
                            <td>
                                @if ($lead->assignee)
                                    {{ $lead->assignee->displayName() }}
                                @else
                                    <span class="muted fst-italic">Chưa gán</span>
                                @endif
                            </td>
                            <td class="muted">{{ $lead->created_at->format('d/m/Y') }}</td>
                            <td class="actions">
                                <a href="{{ route('crm.leads.show', $lead) }}" class="icon-link" title="Xem chi tiết">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                @if (auth()->user()->crmCan('leads.update'))
                                <a href="{{ route('crm.leads.edit', [$lead, 'parent']) }}" class="icon-link" title="Chỉnh sửa">
                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty">
                                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                <p>Không tìm thấy khách hàng nào.</p>
                                @if (collect($filters)->filter()->isNotEmpty())
                                    <a href="{{ route('crm.leads.index') }}" class="link">Xoá bộ lọc</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leads->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $leads->firstItem() }}–{{ $leads->lastItem() }} / {{ $leads->total() }}</span>
                {{ $leads->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () { document.getElementById('leadFilter').submit(); });
        });
        document.querySelector('[data-filter-toggle]').addEventListener('click', function () {
            var box = document.querySelector('[data-filters]');
            box.hidden = !box.hidden;
            this.classList.toggle('is-on', !box.hidden);
        });
    </script>
@endpush
