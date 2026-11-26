@extends('crm::layouts.app')

@section('title', 'Lịch hẹn')

@use('App\Http\Controllers\Crm\AppointmentController')

@php
    $tabUrl = fn ($key) => route('crm.appointments.index', array_filter(['tab' => $key === 'upcoming' ? null : $key, 'user' => $owner]));
    $dayLabel = function (string $date) {
        $d = \Illuminate\Support\Carbon::parse($date);
        $prefix = match (true) {
            $d->isToday() => 'Hôm nay · ',
            $d->isTomorrow() => 'Ngày mai · ',
            $d->isYesterday() => 'Hôm qua · ',
            default => '',
        };
        $weekday = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][$d->dayOfWeek];

        return $prefix.$weekday.', '.$d->format('d/m/Y');
    };
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Lịch hẹn</h1>
            <p class="muted">Lịch gọi điện, gặp mặt và họp online với khách hàng</p>
        </div>
        <div class="page-actions">
            @if (auth()->user()->crmCan('appointments.manage'))
            <a href="{{ route('crm.appointments.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Đặt lịch hẹn
            </a>
            @endif
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Sau mỗi buổi hẹn, bấm “Hoàn thành” và ghi kết quả — nội dung tự lưu vào lịch sử tư vấn của khách. Tab “Quá hạn” là lịch đã qua giờ mà chưa cập nhật.
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="card card-flush">
        <nav class="tabs" aria-label="Nhóm lịch hẹn">
            @foreach (AppointmentController::TABS as $key => $label)
                <a href="{{ $tabUrl($key) }}" class="tab {{ $tab === $key ? 'is-active' : '' }} {{ $key === 'overdue' && $counts[$key] ? 'tab-alert' : '' }}"
                   @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span class="tab-count">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('crm.appointments.index') }}" class="toolbar toolbar-row">
            @if ($tab !== 'upcoming')<input type="hidden" name="tab" value="{{ $tab }}">@endif
            <div class="filters">
                <select name="user" onchange="this.form.submit()" aria-label="Tư vấn viên">
                    <option value="">Lịch của mọi người</option>
                    <option value="{{ auth()->id() }}" @selected((string) $owner === (string) auth()->id())>★ Lịch của tôi</option>
                    <option value="none" @selected($owner === 'none')>— Chưa có người phụ trách —</option>
                    @foreach ($consultants as $id => $name)
                        @continue($id === auth()->id())
                        <option value="{{ $id }}" @selected((string) $owner === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="agenda">
            @forelse ($groups as $date => $items)
                <h3 class="agenda-day {{ \Illuminate\Support\Carbon::parse($date)->isToday() ? 'is-today' : '' }}">{{ $dayLabel($date) }}</h3>

                @foreach ($items as $a)
                    <article class="appt appt-{{ $a->status }} {{ $a->isOverdue() ? 'is-overdue' : '' }}">
                        <div class="appt-time">
                            <b>{{ $a->scheduled_at->format('H:i') }}</b>
                            <span class="muted">{{ $a->endsAt()->format('H:i') }}</span>
                        </div>

                        <span class="appt-icon" title="{{ $a->typeLabel() }}"><svg viewBox="0 0 24 24">{!! $a->typeIcon() !!}</svg></span>

                        <div class="appt-body">
                            <p class="appt-title">
                                <a href="{{ route('crm.leads.show', $a->lead_id) }}#appointments" class="link-strong">{{ $a->lead->full_name }}</a>
                                <span class="muted">· {{ $a->typeLabel() }}</span>
                                <span class="badge {{ $a->statusBadge() }}">{{ $a->isOverdue() ? 'Quá giờ, chưa cập nhật' : $a->statusLabel() }}</span>
                            </p>
                            <p class="appt-meta">
                                <a href="tel:{{ $a->lead->phone }}" class="link mono">{{ $a->lead->phone }}</a>
                                @if ($a->lead->student_name) · HS: {{ $a->lead->student_name }}@endif
                                · {{ $a->consultant?->displayName() ?? 'Chưa có người phụ trách' }}
                                @if ($a->location)
                                    · @if (str_starts_with($a->location, 'http'))<a href="{{ $a->location }}" target="_blank" rel="noopener" class="link">Link họp</a>@else{{ $a->location }}@endif
                                @endif
                            </p>
                            @if ($a->note)<p class="appt-note">{{ $a->note }}</p>@endif
                            @if ($a->result)<p class="appt-result"><b>Kết quả:</b> {{ $a->result }}</p>@endif

                            @if ($a->status === 'scheduled' && auth()->user()->crmCan('appointments.manage'))
                                <div class="appt-actions">
                                    <details class="appt-finish">
                                        <summary class="btn btn-primary btn-sm">Hoàn thành</summary>
                                        <form method="POST" action="{{ route('crm.appointments.status', $a) }}" class="appt-finish-form">
                                            @csrf
                                            <input type="hidden" name="status" value="done">
                                            <textarea name="result" rows="2" placeholder="Kết quả buổi hẹn (khách quan tâm gì, bước tiếp theo...)"></textarea>
                                            <button type="submit" class="btn btn-primary btn-sm">Lưu kết quả</button>
                                        </form>
                                    </details>
                                    <a href="{{ route('crm.appointments.edit', $a) }}" class="btn btn-outline btn-sm">Đổi lịch</a>
                                    <form method="POST" action="{{ route('crm.appointments.status', $a) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="no_show">
                                        <button type="submit" class="btn btn-outline btn-sm">Khách vắng mặt</button>
                                    </form>
                                    <form method="POST" action="{{ route('crm.appointments.status', $a) }}" onsubmit="return confirm('Huỷ lịch hẹn này?')">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-danger-outline btn-sm">Huỷ</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            @empty
                <div class="empty">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <p>{{ $tab === 'overdue' ? 'Không có lịch hẹn quá hạn. Tốt lắm!' : 'Không có lịch hẹn nào.' }}</p>
                    @if ($tab === 'upcoming')
                        <a href="{{ route('crm.appointments.create') }}" class="link">+ Đặt lịch hẹn</a>
                    @endif
                </div>
            @endforelse
        </div>

        @if ($appointments->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $appointments->firstItem() }}–{{ $appointments->lastItem() }} / {{ $appointments->total() }}</span>
                {{ $appointments->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection
