@extends('crm::layouts.app')

@section('title', $lead->full_name)
@section('breadcrumb', $lead->full_name)

@use('App\Models\Lead')
@use('App\Models\LeadActivity')

@php
    $editLabels = [
        'parent' => 'Chỉnh sửa thông tin phụ huynh',
        'student' => 'Chỉnh sửa thông tin học sinh',
        'consult' => 'Chỉnh sửa nhu cầu tư vấn',
    ];
    $editIcon = '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
    $hollandCode = $lead->hollandCode();
    $scores = $lead->hollandScores();
@endphp

@section('content')
    <a href="{{ route('crm.leads.index') }}" class="back-link">← Danh sách khách hàng tiềm năng</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ===== THÔNG TIN KHÁCH HÀNG ===== --}}
    <section class="card lead-hero" id="general">
        <div class="lead-hero-top">
            <span class="crm-avatar lg">{{ mb_strtoupper(mb_substr($lead->full_name, 0, 1)) }}</span>
            <div class="lead-hero-name">
                <h1>{{ $lead->full_name }}</h1>
                <p class="muted">
                    {{ $lead->code() }}
                    @if ($lead->student_name) · Học sinh: <b>{{ $lead->student_name }}</b>@endif
                </p>
            </div>
            <div class="page-actions">
                <a href="tel:{{ $lead->phone }}" class="btn btn-outline">
                    <svg viewBox="0 0 24 24">{!! LeadActivity::TYPES['call'][1] !!}</svg> {{ $lead->phone }}
                </a>
                @if (auth()->user()->crmCan('leads.update'))
                    <a href="{{ route('crm.leads.edit', [$lead, 'general']) }}" class="btn btn-primary">{!! $editIcon !!} Cập nhật trạng thái</a>
                @endif
                @if (auth()->user()->crmCan('leads.delete'))
                    <form method="POST" action="{{ route('crm.leads.destroy', $lead) }}"
                          onsubmit="return confirm('Xoá khách hàng {{ addslashes($lead->full_name) }} và toàn bộ lịch sử tư vấn? Không thể hoàn tác.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger-outline" title="Xoá khách hàng">
                            <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg> Xoá
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <dl class="info-strip">
            <div><dt>Mã khách hàng</dt><dd>{{ $lead->code() }}</dd></div>
            <div><dt>Nguồn</dt><dd><span class="source source-{{ $lead->source }}">{{ $lead->sourceLabel() }}</span></dd></div>
            <div><dt>Ngày tiếp nhận</dt><dd>{{ $lead->created_at->format('d/m/Y') }}</dd></div>
            <div><dt>Trạng thái</dt><dd><span class="badge {{ $lead->statusBadge() }}">{{ $lead->statusLabel() }}</span></dd></div>
            <div><dt>Tư vấn viên</dt><dd>{!! $lead->assignee ? e($lead->assignee->displayName()) : '<span class="muted fst-italic">Chưa gán</span>' !!}</dd></div>
        </dl>
    </section>

    <div class="lead-layout">
        {{-- ===== CỘT TRÁI: I – IV ===== --}}
        <div class="lead-col">
            @foreach (['parent', 'student', 'consult'] as $key)
                @php $sec = $sections[$key]; @endphp
                <section class="card" id="{{ $key }}">
                    <div class="card-head">
                        <h3 class="section-title"><span>{{ $sec['no'] }}</span>{{ $sec['title'] }}</h3>
                    </div>
                    <dl class="info-grid">
                        @foreach ($sec['fields'] as $field => $def)
                            @php $v = $lead->displayValue($field, $def); @endphp
                            <div class="{{ ! empty($def['full']) ? 'full' : '' }}">
                                <dt>{{ $def['label'] }}</dt>
                                <dd>
                                    @if ($v === null)
                                        <span class="empty-val">Chưa cập nhật</span>
                                    @elseif ($def['type'] === 'tel')
                                        <a href="tel:{{ $v }}" class="link">{{ $v }}</a>
                                    @elseif ($def['type'] === 'email')
                                        <a href="mailto:{{ $v }}" class="link">{{ $v }}</a>
                                    @elseif ($def['type'] === 'textarea')
                                        {!! nl2br(e($v)) !!}
                                    @else
                                        {{ $v }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                    @if (auth()->user()->crmCan('leads.update'))
                        <div class="card-actions">
                            <a href="{{ route('crm.leads.edit', [$lead, $key]) }}" class="btn btn-outline btn-sm">{!! $editIcon !!} {{ $editLabels[$key] }}</a>
                        </div>
                    @endif
                </section>
            @endforeach

            {{-- IV. HOLLAND --}}
            <section class="card" id="holland">
                <div class="card-head">
                    <h3 class="section-title"><span>IV</span>Thông tin Holland <small class="muted">(nếu có)</small></h3>
                    <div class="holland-links">
                        @if ($lead->holland_report_url)
                            <a href="{{ $lead->holland_report_url }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Xem bài làm trên Holland ↗</a>
                        @endif
                        <button type="button" class="btn btn-outline btn-sm" data-copy="{{ $lead->hollandInviteUrl() }}"
                                title="Gửi link này cho khách qua Zalo / email. Khách làm xong, kết quả tự về hồ sơ này.">
                            Chép link trắc nghiệm cho khách
                        </button>
                    </div>
                </div>

                @if ($hollandCode)
                    <div class="holland-head">
                        <div>
                            <p class="muted">Mã Holland</p>
                            <p class="holland-code">
                                @foreach (str_split($hollandCode) as $l)<span class="h-{{ strtolower($l) }}">{{ $l }}</span>@endforeach
                            </p>
                        </div>
                        @if ($hr = $lead->holland_result)
                            <div class="holland-meta">
                                <p class="muted">Từ hệ thống Holland{{ ! empty($hr['code_public']) ? ' · khách thấy mã '.$hr['code_public'] : '' }}</p>
                                <p>
                                    @if (! empty($hr['reliability']))
                                        <span class="badge {{ $hr['reliability'] === 'Tin cậy' ? 'badge-green' : ($hr['reliability'] === 'Cần xem lại' ? 'badge-amber' : 'badge-gray') }}">Độ tin cậy: {{ $hr['reliability'] }}</span>
                                    @endif
                                    @if (! empty($hr['consistency']))
                                        <span class="badge badge-sky">Nhất quán: {{ $hr['consistency'] }}</span>
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="holland-bars">
                        @foreach (Lead::HOLLAND_TYPES as $l => [$name])
                            @php $s = $scores[$l] ?? null; @endphp
                            <div class="hbar {{ str_contains($hollandCode, $l) ? 'is-top' : '' }}">
                                <span class="hbar-label"><b>{{ $l }}</b> {{ $name }}</span>
                                <span class="hbar-track"><span class="hbar-fill h-{{ strtolower($l) }}" style="width: {{ (int) $s }}%"></span></span>
                                {{-- Kết quả từ hệ thống Holland: hiện điểm trung bình gốc x/5; nhập tay: điểm 0–100 --}}
                                @php $avg = $lead->hollandAverage($l); @endphp
                                <span class="hbar-val">{{ $avg !== null ? $avg.'/5' : ($s ?? '—') }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if (! empty($lead->holland_result['majors']))
                        <div class="holland-majors">
                            <p class="muted">Ngành gợi ý từ Holland (tư vấn viên chọn lại khi tư vấn):</p>
                            <p>
                                @foreach ($lead->holland_result['majors'] as $m)
                                    <span class="chip-static {{ ! empty($m['shown_to_customer']) ? 'is-shown' : '' }}"
                                          title="{{ ! empty($m['shown_to_customer']) ? 'Khách đã thấy ngành này' : 'Chỉ hiện nội bộ' }}">
                                        {{ $m['name_vi'] ?? $m['id'] ?? '' }}{{ ! empty($m['name_de']) ? ' · '.$m['name_de'] : '' }}
                                    </span>
                                @endforeach
                            </p>
                        </div>
                    @endif

                    <div class="holland-detail" id="hollandDetail" hidden>
                        @foreach (str_split($hollandCode) as $i => $l)
                            <div class="holland-type">
                                <span class="holland-badge h-{{ strtolower($l) }}">{{ $l }}</span>
                                <div>
                                    <b>{{ $i + 1 }}. {{ Lead::HOLLAND_TYPES[$l][0] }}</b> · {{ $scores[$l] }} điểm
                                    <p class="muted">{{ Lead::HOLLAND_TYPES[$l][1] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="empty-val">Khách hàng chưa làm hoặc chưa có kết quả trắc nghiệm Holland.</p>
                @endif

                <div class="card-actions">
                    @if ($hollandCode)
                        <button type="button" class="btn btn-outline btn-sm" data-holland-toggle>Xem kết quả Holland</button>
                    @endif
                    @if (auth()->user()->crmCan('leads.update'))
                    <a href="{{ route('crm.leads.edit', [$lead, 'holland']) }}" class="btn btn-outline btn-sm">{!! $editIcon !!} {{ $hollandCode ? 'Cập nhật điểm' : 'Nhập điểm Holland' }}</a>
                    @endif
                </div>
            </section>
        </div>

        {{-- ===== CỘT PHẢI: V – VI ===== --}}
        <div class="lead-col lead-side">
            {{-- Lịch hẹn của khách --}}
            @php
                $upcomingAppts = $lead->appointments->where('status', 'scheduled');
                $pastAppts = $lead->appointments->where('status', '!=', 'scheduled')->sortByDesc('scheduled_at')->take(3);
            @endphp
            <section class="card" id="appointments">
                <div class="card-head">
                    <h3 class="section-title"><span>
                        <svg viewBox="0 0 24 24" style="width:14px;height:14px"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </span>Lịch hẹn</h3>
                    @if (auth()->user()->crmCan('appointments.manage'))
                        <a href="{{ route('crm.appointments.create', ['lead' => $lead->id, 'return' => 'lead']) }}" class="btn btn-outline btn-sm">+ Đặt lịch hẹn</a>
                    @endif
                </div>
                <ul class="mini-appts">
                    @forelse ($upcomingAppts->concat($pastAppts) as $a)
                        <li class="{{ $a->isOverdue() ? 'is-overdue' : '' }} appt-{{ $a->status }}">
                            <span class="appt-icon sm"><svg viewBox="0 0 24 24">{!! $a->typeIcon() !!}</svg></span>
                            <div>
                                <b>{{ $a->scheduled_at->format('H:i d/m/Y') }}</b> · {{ $a->typeLabel() }}
                                <span class="badge {{ $a->statusBadge() }}">{{ $a->isOverdue() ? 'Quá giờ' : $a->statusLabel() }}</span>
                                <p class="muted">{{ $a->consultant?->displayName() ?? 'Chưa có người phụ trách' }}{{ $a->note ? ' · '.\Illuminate\Support\Str::limit($a->note, 60) : '' }}</p>
                                @if ($a->status === 'scheduled' && auth()->user()->crmCan('appointments.manage'))
                                    <a href="{{ route('crm.appointments.edit', [$a, 'return' => 'lead']) }}" class="link">Đổi lịch</a>
                                    · <a href="{{ route('crm.appointments.index', ['tab' => $a->isOverdue() ? 'overdue' : ($a->scheduled_at->isToday() ? 'today' : 'upcoming')]) }}" class="link">Cập nhật kết quả</a>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="empty-val">Chưa có lịch hẹn.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card" id="history">
                <div class="card-head">
                    <h3 class="section-title"><span>V</span>Lịch sử tư vấn</h3>
                    <span class="muted">{{ $lead->activities->count() }} hoạt động</span>
                </div>

                <ol class="timeline">
                    @forelse ($lead->activities as $a)
                        <li class="tl-item tl-{{ $a->type }}">
                            <span class="tl-icon"><svg viewBox="0 0 24 24">{!! $a->typeIcon() !!}</svg></span>
                            <div class="tl-body">
                                <p class="tl-meta">
                                    <b>{{ $a->created_at->format('d/m/Y H:i') }}</b>
                                    · {{ $a->typeLabel() }}
                                    @if ($a->user) · {{ $a->user->displayName() }}@endif
                                </p>
                                <p class="tl-content">{!! nl2br(e($a->content)) !!}</p>
                            </div>
                        </li>
                    @empty
                        <li class="empty-val">Chưa có hoạt động nào.</li>
                    @endforelse
                </ol>
            </section>

            @if (auth()->user()->crmCan('leads.update'))
            <section class="card" id="notes">
                <div class="card-head">
                    <h3 class="section-title"><span>VI</span>Ghi chú và hoạt động</h3>
                </div>
                <form method="POST" action="{{ route('crm.leads.notes.store', $lead) }}" class="note-form">
                    @csrf
                    <div class="note-types">
                        @foreach (LeadActivity::MANUAL_TYPES as $t)
                            <label class="chip">
                                <input type="radio" name="type" value="{{ $t }}" @checked(old('type', 'note') === $t)>
                                <span><svg viewBox="0 0 24 24">{!! LeadActivity::TYPES[$t][1] !!}</svg>{{ LeadActivity::TYPES[$t][0] }}</span>
                            </label>
                        @endforeach
                    </div>
                    <label class="field @error('content') has-error @enderror">
                        <textarea name="content" rows="4" placeholder="Nhập nội dung trao đổi với phụ huynh..." required>{{ old('content') }}</textarea>
                        @error('content')<small class="error">{{ $message }}</small>@enderror
                    </label>
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Thêm ghi chú
                    </button>
                </form>
            </section>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy'), old = btn.textContent;
                var done = function () { btn.textContent = 'Đã chép ✓'; setTimeout(function () { btn.textContent = old; }, 1800); };
                if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, function () { window.prompt('Chép link:', text); }); }
                else { window.prompt('Chép link:', text); }
            });
        });
        var hBtn = document.querySelector('[data-holland-toggle]');
        if (hBtn) hBtn.addEventListener('click', function () {
            var box = document.getElementById('hollandDetail');
            box.hidden = !box.hidden;
            this.textContent = box.hidden ? 'Xem kết quả Holland' : 'Ẩn kết quả Holland';
        });
    </script>
@endpush
