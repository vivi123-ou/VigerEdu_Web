@extends('crm::layouts.app')

@section('title', 'Lịch sử hoạt động')

@use('App\Models\LeadActivity')
@use('App\Http\Controllers\Crm\ActivityController')

@php
    $params = array_filter(['period' => $period, 'user' => $user, 'q' => $q !== '' ? $q : null]);
    $typeUrl = fn ($t) => route('crm.activities.index', array_filter($params + ['type' => $t]));
    $hasFilter = ! empty($params);
    $dayLabel = function (string $date) {
        $d = \Illuminate\Support\Carbon::parse($date);
        $prefix = $d->isToday() ? 'Hôm nay · ' : ($d->isYesterday() ? 'Hôm qua · ' : '');

        return $prefix.['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][$d->dayOfWeek].', '.$d->format('d/m/Y');
    };
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Lịch sử hoạt động</h1>
            <p class="muted">Mọi tương tác với khách hàng, mới nhất trước</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Trang này gom lịch sử tư vấn của tất cả khách: ghi chú, cuộc gọi, lịch hẹn, form liên hệ và các thay đổi hệ thống tự ghi. Bấm tên khách để mở hồ sơ.
    </p>

    <section class="card card-flush">
        <nav class="tabs" aria-label="Loại hoạt động">
            <a href="{{ $typeUrl(null) }}" class="tab {{ ! $type ? 'is-active' : '' }}">Tất cả <span class="tab-count">{{ number_format($counts['all']) }}</span></a>
            @foreach (LeadActivity::TYPES as $key => [$label])
                @continue(! $counts[$key] && $type !== $key)
                <a href="{{ $typeUrl($key) }}" class="tab {{ $type === $key ? 'is-active' : '' }}" @if ($type === $key) aria-current="page" @endif>
                    {{ $label }} <span class="tab-count">{{ number_format($counts[$key]) }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('crm.activities.index') }}" class="toolbar toolbar-row" id="activityFilter">
            @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Tên khách, học sinh hoặc SĐT...">
            </div>
            <div class="filters">
                <select name="period" data-autosubmit aria-label="Thời gian">
                    <option value="">Mọi thời gian</option>
                    @foreach (ActivityController::PERIODS as $key => $label)
                        <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="user" data-autosubmit aria-label="Người thực hiện">
                    <option value="">Mọi người</option>
                    <option value="{{ auth()->id() }}" @selected((string) $user === (string) auth()->id())>★ Hoạt động của tôi</option>
                    <option value="system" @selected($user === 'system')>Hệ thống / kênh tự động</option>
                    @foreach ($consultants as $id => $name)
                        @continue($id === auth()->id())
                        <option value="{{ $id }}" @selected((string) $user === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @if ($hasFilter)
                    <a href="{{ route('crm.activities.index', array_filter(['type' => $type])) }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="agenda">
            @forelse ($groups as $date => $items)
                <h3 class="agenda-day {{ \Illuminate\Support\Carbon::parse($date)->isToday() ? 'is-today' : '' }}">
                    {{ $dayLabel($date) }} <span class="muted">· {{ $items->count() }} hoạt động</span>
                </h3>
                <ol class="timeline feed">
                    @foreach ($items as $a)
                        <li class="tl-item tl-{{ $a->type }}">
                            <span class="tl-icon"><svg viewBox="0 0 24 24">{!! $a->typeIcon() !!}</svg></span>
                            <div class="tl-body">
                                <p class="tl-meta">
                                    <b>{{ $a->created_at->format('H:i') }}</b>
                                    · <a href="{{ route('crm.leads.show', $a->lead_id) }}#history" class="link-strong">{{ $a->lead?->full_name ?? 'Khách đã xoá' }}</a>
                                    @if ($a->lead?->student_name)<span> (HS: {{ $a->lead->student_name }})</span>@endif
                                    · {{ $a->typeLabel() }}
                                    · {{ $a->user?->displayName() ?? 'Hệ thống' }}
                                </p>
                                <p class="tl-content">{!! nl2br(e(\Illuminate\Support\Str::limit($a->content, 400))) !!}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @empty
                <div class="empty">
                    <svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/></svg>
                    <p>Không có hoạt động nào{{ $hasFilter || $type ? ' khớp bộ lọc' : '' }}.</p>
                    @if ($hasFilter || $type)
                        <a href="{{ route('crm.activities.index') }}" class="link">Xoá bộ lọc</a>
                    @endif
                </div>
            @endforelse
        </div>

        @if ($activities->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $activities->firstItem() }}–{{ $activities->lastItem() }} / {{ $activities->total() }}</span>
                {{ $activities->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () { document.getElementById('activityFilter').submit(); });
        });
    </script>
@endpush
