@extends('crm::layouts.app')

@section('title', 'Ghi chú tư vấn')

@use('App\Models\LeadActivity')
@use('App\Http\Controllers\Crm\NoteController')

@php
    $params = array_filter(['author' => $author, 'q' => $q !== '' ? $q : null]);
    $typeUrl = fn ($t) => route('crm.notes.index', array_filter($params + ['type' => $t]));
    $hasFilter = ! empty($params);
    $highlight = function (string $text) use ($q) {
        $safe = e($text);
        if ($q === '') {
            return nl2br($safe);
        }

        return nl2br(preg_replace('/('.preg_quote(e($q), '/').')/iu', '<mark>$1</mark>', $safe));
    };
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Ghi chú tư vấn</h1>
            <p class="muted">Nội dung trao đổi với phụ huynh và học sinh do nhân viên ghi lại</p>
        </div>
    </div>

    <p class="page-help">
        <span>?</span>
        Tìm theo nội dung (VD: “Ausbildung”, “visa”) để xem các buổi tư vấn liên quan. Bạn chỉ sửa / xoá được ghi chú do mình viết.
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Thêm ghi chú nhanh --}}
    @if (auth()->user()->crmCan('leads.update'))
    <details class="card quick-note" @if ($errors->any()) open @endif>
        <summary>
            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            <b>Thêm ghi chú</b> <span class="muted">cho bất kỳ khách hàng nào</span>
        </summary>
        <form method="POST" action="{{ route('crm.notes.store') }}" class="note-form" id="quickNote">
            @csrf
            <label class="field @error('lead_id') has-error @enderror">
                <span>Khách hàng <i>*</i></span>
                <input type="text" list="noteLeads" id="noteLeadSearch" placeholder="Gõ tên hoặc SĐT để tìm..." autocomplete="off"
                       value="{{ old('lead_id') ? ($leads[old('lead_id')] ?? '') : '' }}">
                <datalist id="noteLeads">
                    @foreach ($leads as $id => $label)
                        <option value="{{ $label }}" data-id="{{ $id }}"></option>
                    @endforeach
                </datalist>
                <input type="hidden" name="lead_id" id="noteLeadId" value="{{ old('lead_id') }}">
                @error('lead_id')<small class="error">{{ $message }}</small>@enderror
            </label>
            <div class="note-types">
                @foreach (LeadActivity::MANUAL_TYPES as $t)
                    <label class="chip">
                        <input type="radio" name="type" value="{{ $t }}" @checked(old('type', 'note') === $t)>
                        <span><svg viewBox="0 0 24 24">{!! LeadActivity::TYPES[$t][1] !!}</svg>{{ LeadActivity::TYPES[$t][0] }}</span>
                    </label>
                @endforeach
            </div>
            <label class="field @error('content') has-error @enderror">
                <textarea name="content" rows="3" placeholder="Nội dung trao đổi với phụ huynh..." required>{{ old('content') }}</textarea>
                @error('content')<small class="error">{{ $message }}</small>@enderror
            </label>
            <button type="submit" class="btn btn-primary">Lưu ghi chú</button>
        </form>
    </details>
    @endif

    <section class="card card-flush">
        <nav class="tabs" aria-label="Loại ghi chú">
            <a href="{{ $typeUrl(null) }}" class="tab {{ ! $type ? 'is-active' : '' }}">Tất cả <span class="tab-count">{{ number_format($counts['all']) }}</span></a>
            @foreach (LeadActivity::MANUAL_TYPES as $t)
                <a href="{{ $typeUrl($t) }}" class="tab {{ $type === $t ? 'is-active' : '' }}" @if ($type === $t) aria-current="page" @endif>
                    {{ LeadActivity::TYPES[$t][0] }} <span class="tab-count">{{ number_format($counts[$t]) }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('crm.notes.index') }}" class="toolbar toolbar-row">
            @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            <div class="input-search">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Tìm trong nội dung ghi chú, tên khách, SĐT...">
            </div>
            <div class="filters">
                <select name="author" onchange="this.form.submit()" aria-label="Người viết">
                    <option value="">Mọi người viết</option>
                    <option value="{{ auth()->id() }}" @selected((string) $author === (string) auth()->id())>★ Ghi chú của tôi</option>
                    @foreach ($authors as $id => $name)
                        @continue($id === auth()->id())
                        <option value="{{ $id }}" @selected((string) $author === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @if ($hasFilter)
                    <a href="{{ route('crm.notes.index', array_filter(['type' => $type])) }}" class="btn btn-ghost">Xoá lọc</a>
                @endif
            </div>
        </form>

        <div class="note-list">
            @forelse ($notes as $n)
                @php $canManage = NoteController::canManage(auth()->user(), $n); @endphp
                <article class="note-card tl-{{ $n->type }}" id="note-{{ $n->id }}">
                    <span class="tl-icon"><svg viewBox="0 0 24 24">{!! $n->typeIcon() !!}</svg></span>
                    <div class="note-main">
                        <p class="tl-meta">
                            <a href="{{ route('crm.leads.show', $n->lead_id) }}#history" class="link-strong">{{ $n->lead?->full_name ?? 'Khách đã xoá' }}</a>
                            @if ($n->lead?->student_name)<span> (HS: {{ $n->lead->student_name }})</span>@endif
                            @if ($n->lead)<span class="badge {{ $n->lead->statusBadge() }}">{{ $n->lead->statusLabel() }}</span>@endif
                        </p>
                        <p class="note-content">{!! $highlight($n->content) !!}</p>
                        <p class="tl-meta">
                            {{ $n->typeLabel() }} · {{ $n->user?->displayName() ?? 'Không rõ' }}
                            · <span title="{{ $n->created_at->format('H:i d/m/Y') }}">{{ $n->created_at->format('H:i d/m/Y') }}</span>
                            @if ($n->updated_at->gt($n->created_at->copy()->addMinute()))
                                · <i title="Sửa lúc {{ $n->updated_at->format('H:i d/m/Y') }}">đã sửa</i>
                            @endif
                        </p>

                        @if ($canManage)
                            <div class="note-actions">
                                <details class="note-edit">
                                    <summary class="link">Sửa</summary>
                                    <form method="POST" action="{{ route('crm.notes.update', $n) }}" class="note-form">
                                        @csrf
                                        @method('PUT')
                                        <div class="note-types">
                                            @foreach (LeadActivity::MANUAL_TYPES as $t)
                                                <label class="chip">
                                                    <input type="radio" name="type" value="{{ $t }}" @checked($n->type === $t)>
                                                    <span>{{ LeadActivity::TYPES[$t][0] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <textarea name="content" rows="3" required class="note-textarea">{{ $n->content }}</textarea>
                                        <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('crm.notes.destroy', $n) }}" onsubmit="return confirm('Xoá ghi chú này? Không thể hoàn tác.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link link-danger">Xoá</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty">
                    <svg viewBox="0 0 24 24">{!! LeadActivity::TYPES['note'][1] !!}</svg>
                    <p>Không có ghi chú nào{{ $hasFilter || $type ? ' khớp bộ lọc' : '' }}.</p>
                </div>
            @endforelse
        </div>

        @if ($notes->hasPages())
            <div class="card-foot">
                <span class="muted">Hiển thị {{ $notes->firstItem() }}–{{ $notes->lastItem() }} / {{ $notes->total() }}</span>
                {{ $notes->onEachSide(1)->links('crm::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        // Ô tìm khách: chọn từ gợi ý => gán lead_id ẩn
        (function () {
            var input = document.getElementById('noteLeadSearch');
            var hidden = document.getElementById('noteLeadId');
            var options = document.querySelectorAll('#noteLeads option');
            input.addEventListener('input', function () {
                hidden.value = '';
                options.forEach(function (o) { if (o.value === input.value) hidden.value = o.dataset.id; });
                input.setCustomValidity('');
            });
            document.getElementById('quickNote').addEventListener('submit', function (e) {
                if (!hidden.value) { e.preventDefault(); input.setCustomValidity('Chọn khách hàng trong danh sách gợi ý'); input.reportValidity(); }
            });
        })();
    </script>
@endpush
