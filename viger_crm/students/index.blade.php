@extends('crm::layouts.app')

@section('title', 'Hồ sơ học viên')

@php
    $hasFilter = collect($filters)->filter()->isNotEmpty();
    $tabs = ['all' => 'Tất cả', 'none' => 'Chưa cập nhật'] + $profileStatuses;
    $tabUrl = fn ($key) => route('crm.students.index', array_merge(request()->except('page', 'tab'), $key === 'all' ? [] : ['tab' => $key]));
    $filterLabels = ['program' => 'Mọi chương trình', 'german' => 'Tiếng Đức: mọi trình độ', 'education' => 'Mọi trình độ học vấn'];
    $german = fn (?string $lv) => match (true) {
        in_array($lv, ['B2', 'C1', 'C2'], true) => 'lv-good',
        in_array($lv, ['A2', 'B1'], true) => 'lv-mid',
        default => 'lv-low',
    };
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Hồ sơ học viên</h1>
            <p class="muted">Học lực, ngoại ngữ, nguyện vọng và tình trạng hồ sơ du học · {{ number_format($counts['all']) }} học viên</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Trang này liệt kê khách đã có thông tin học sinh. Bấm vào tên để xem hồ sơ đầy đủ; cập nhật học lực, nguyện vọng ở mục “II. Thông tin học sinh”.
    </p>

    <section class="card card-flush">
        <nav class="tabs" aria-label="Tình trạng hồ sơ">
            @foreach ($tabs as $key => $label)
                <a href="{{ $tabUrl($key) }}" class="tab {{ $tab === $key ? 'is-active' : '' }}" @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span class="tab-count">{{ number_format($counts[$key]) }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('crm.students.index') }}" class="toolbar toolbar-row" id="studentFilter">
            @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif

            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên học sinh, phụ huynh, trường, ngành...">
            </div>

            <div class="filters">
                @foreach ($options as $name => $opts)
                    <select name="{{ $name }}" data-autosubmit aria-label="{{ $filterLabels[$name] }}">
                        <option value="">{{ $filterLabels[$name] }}</option>
                        @foreach ($opts as $key => $label)
                            <option value="{{ $key }}" @selected(($filters[$name] ?? '') === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endforeach
                <select name="holland" data-autosubmit aria-label="Holland">
                    <option value="">Holland: tất cả</option>
                    <option value="yes" @selected(($filters['holland'] ?? '') === 'yes')>Đã có kết quả Holland</option>
                    <option value="no" @selected(($filters['holland'] ?? '') === 'no')>Chưa có Holland</option>
                </select>
                @if ($hasFilter)
                    <a href="{{ route('crm.students.index', $tab === 'all' ? [] : ['tab' => $tab]) }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Học viên</th>
                        <th>Học lực</th>
                        <th>Ngoại ngữ</th>
                        <th>Nguyện vọng</th>
                        <th>Holland</th>
                        <th>Hồ sơ</th>
                        <th>Phụ huynh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $s)
                        @php $code = $s->hollandCode(); @endphp
                        <tr>
                            <td>
                                <div class="cell-person">
                                    <span class="crm-avatar sm">{{ mb_strtoupper(mb_substr($s->student_name, 0, 1)) }}</span>
                                    <div>
                                        <a href="{{ route('crm.leads.show', $s) }}#student" class="link-strong">{{ $s->student_name }}</a>
                                        <small class="muted d-block">
                                            {{ collect([
                                                $s->student_gender,
                                                $s->student_dob ? $s->student_dob->age.' tuổi' : null,
                                            ])->filter()->implode(' · ') ?: $s->code() }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $s->education_level ?? '—' }}
                                <small class="muted d-block">
                                    {{ $s->gpa !== null ? 'GPA '.$s->displayValue('gpa', ['type' => 'gpa']) : '' }}{{ $s->gpa !== null && $s->student_school ? ' · ' : '' }}{{ \Illuminate\Support\Str::limit($s->student_school, 28) }}
                                </small>
                            </td>
                            <td>
                                @if ($s->german_level)
                                    <span class="lv {{ $german($s->german_level) }}">Đức {{ $s->german_level }}</span>
                                @endif
                                @if ($s->english_level)
                                    <small class="muted d-block">{{ $s->english_level }}</small>
                                @endif
                                @if (! $s->german_level && ! $s->english_level)<span class="empty-val">Chưa có</span>@endif
                            </td>
                            <td>
                                @if ($s->desired_major || $s->program_interest)
                                    {{ $s->desired_major ?? $s->program_interest }}
                                    <small class="muted d-block">
                                        {{ collect([$s->desired_major ? $s->program_interest : null, $s->desired_state])->filter()->implode(' · ') }}
                                    </small>
                                @else
                                    <span class="empty-val">Chưa có</span>
                                @endif
                            </td>
                            <td>
                                @if ($code)
                                    <span class="holland-mini">
                                        @foreach (str_split($code) as $l)<span class="h-{{ strtolower($l) }}">{{ $l }}</span>@endforeach
                                    </span>
                                @else
                                    <span class="empty-val">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($s->profile_status)
                                    <span class="badge badge-{{ ['Đã có visa' => 'green', 'Đã có thư mời' => 'violet', 'Đã nộp hồ sơ' => 'sky', 'Đang chuẩn bị' => 'amber'][$s->profile_status] ?? 'gray' }}">{{ $s->profile_status }}</span>
                                @else
                                    <span class="empty-val">Chưa cập nhật</span>
                                @endif
                                @if ($s->expected_time)<small class="muted d-block">{{ $s->expected_time }}</small>@endif
                            </td>
                            <td>
                                {{ $s->full_name }}
                                <small class="muted d-block">
                                    <a href="tel:{{ $s->phone }}" class="link mono">{{ $s->phone }}</a>
                                    @if ($s->assignee) · {{ $s->assignee->displayName() }}@endif
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty">
                                <svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                                <p>Chưa có học viên nào{{ $hasFilter ? ' khớp bộ lọc' : '' }}.</p>
                                <p class="muted">Học viên xuất hiện ở đây khi hồ sơ khách có “Họ và tên” học sinh.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $students->firstItem() }}–{{ $students->lastItem() }} / {{ $students->total() }}</span>
                {{ $students->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () { document.getElementById('studentFilter').submit(); });
        });
    </script>
@endpush
