@extends('crm::layouts.app')

@section('title', 'Quy trình tư vấn')

@use('App\Models\Lead')

@php
    $hasFilter = collect($filters)->filter()->isNotEmpty();
    $canMove = auth()->user()->crmCan('leads.update');
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Quy trình tư vấn</h1>
            <p class="muted">Kéo thẻ khách sang cột khác để chuyển giai đoạn</p>
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
        Mỗi cột là một giai đoạn tư vấn. Kéo thả thẻ (hoặc dùng ô “Chuyển sang” trên thẻ) để đổi trạng thái — lịch sử tư vấn tự ghi lại.
        Cột “Đã chuyển đổi” và “Không tiềm năng” chỉ hiện khách cập nhật trong {{ $closedDays }} ngày gần nhất.
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('crm.pipeline.index') }}" class="card toolbar toolbar-row" id="pipelineFilter">
        <div class="input-search">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên phụ huynh, học sinh, SĐT...">
        </div>
        <div class="filters">
            <select name="assigned_to" data-autosubmit aria-label="Tư vấn viên">
                <option value="">Mọi tư vấn viên</option>
                <option value="{{ auth()->id() }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) auth()->id())>★ Khách của tôi</option>
                <option value="none" @selected(($filters['assigned_to'] ?? '') === 'none')>— Chưa gán —</option>
                @foreach ($consultants as $id => $name)
                    @continue($id === auth()->id())
                    <option value="{{ $id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="source" data-autosubmit aria-label="Nguồn">
                <option value="">Mọi nguồn</option>
                @foreach (Lead::SOURCES as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['source'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($hasFilter)
                <a href="{{ route('crm.pipeline.index') }}" class="btn btn-ghost">Xoá lọc</a>
            @endif
        </div>
    </form>

    <div class="board" data-move-url="{{ route('crm.pipeline.move', ['lead' => 0]) }}">
        @foreach ($columns as $status => $col)
            <section class="board-col col-{{ $status }}" data-status="{{ $status }}" aria-label="{{ $col['label'] }}">
                <header class="board-col-head">
                    <span class="badge {{ $col['badge'] }}">{{ $col['label'] }}</span>
                    <span class="board-count" data-count>{{ $col['total'] }}</span>
                </header>

                <div class="board-cards" data-dropzone>
                    @foreach ($col['leads'] as $lead)
                        @php $last = $lead->last_activity_at ? \Illuminate\Support\Carbon::parse($lead->last_activity_at) : null; @endphp
                        <article class="kcard" draggable="{{ $canMove ? 'true' : 'false' }}" data-id="{{ $lead->id }}" data-status="{{ $status }}">
                            <div class="kcard-top">
                                <a href="{{ route('crm.leads.show', $lead) }}" class="link-strong kcard-name">{{ $lead->full_name }}</a>
                                <span class="source source-{{ $lead->source }}" title="{{ $lead->sourceLabel() }}"></span>
                            </div>
                            @if ($lead->student_name || $lead->program_interest)
                                <p class="kcard-line">
                                    <svg viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                                    {{ collect([$lead->student_name, $lead->program_interest])->filter()->implode(' · ') }}
                                </p>
                            @endif
                            <p class="kcard-line mono">{{ $lead->phone }}</p>
                            <div class="kcard-foot">
                                <span class="kcard-owner" title="Phụ trách">
                                    @if ($lead->assignee)
                                        <span class="crm-avatar xs">{{ mb_strtoupper(mb_substr($lead->assignee->displayName(), 0, 1)) }}</span>
                                        {{ $lead->assignee->displayName() }}
                                    @else
                                        <span class="muted fst-italic">Chưa gán</span>
                                    @endif
                                </span>
                                <span class="{{ $last && $last->lt(now()->subDays(7)) && ! in_array($status, $closed, true) ? 'text-warn' : 'muted' }}"
                                      title="Tương tác gần nhất">
                                    {{ $last ? $last->locale('vi')->shortRelativeDiffForHumans() : '—' }}
                                </span>
                            </div>
                            @if ($canMove)
                            <form method="POST" action="{{ route('crm.pipeline.move', $lead) }}" class="kcard-move">
                                @csrf
                                @method('PATCH')
                                <select name="status" aria-label="Chuyển {{ $lead->full_name }} sang" onchange="this.form.submit()">
                                    @foreach (Lead::STATUSES as $s => [$label])
                                        <option value="{{ $s }}" @selected($s === $status)>{{ $s === $status ? 'Chuyển sang…' : $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @endif
                        </article>
                    @endforeach

                    <p class="board-empty" @if ($col['leads']->isNotEmpty()) hidden @endif>Kéo thẻ vào đây</p>
                </div>

                @if ($col['total'] > $col['leads']->count())
                    <a href="{{ route('crm.leads.index', array_merge($filters, ['status' => $status])) }}" class="board-more">
                        Xem tất cả {{ $col['total'] }} khách →
                    </a>
                @endif
            </section>
        @endforeach
    </div>

    <div class="toast" id="pipelineToast" role="status" aria-live="polite" hidden></div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () { document.getElementById('pipelineFilter').submit(); });
        });

        (function () {
            var board = document.querySelector('.board');
            var moveUrl = board.dataset.moveUrl;
            var token = document.querySelector('meta[name="csrf-token"]').content;
            var toast = document.getElementById('pipelineToast');
            var dragged = null;

            function showToast(text, isError) {
                toast.textContent = text;
                toast.classList.toggle('is-error', !!isError);
                toast.hidden = false;
                clearTimeout(showToast.t);
                showToast.t = setTimeout(function () { toast.hidden = true; }, 3000);
            }

            function refresh(col) {
                var cards = col.querySelectorAll('.kcard').length;
                col.querySelector('.board-empty').hidden = cards > 0;
            }

            function bump(col, delta) {
                var c = col.querySelector('[data-count]');
                c.textContent = Math.max(0, parseInt(c.textContent, 10) + delta);
            }

            board.addEventListener('dragstart', function (e) {
                var card = e.target.closest('.kcard');
                if (!card) return;
                dragged = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.dataset.id);
            });

            board.addEventListener('dragend', function () {
                if (dragged) dragged.classList.remove('is-dragging');
                board.querySelectorAll('.is-over').forEach(function (z) { z.classList.remove('is-over'); });
                dragged = null;
            });

            board.querySelectorAll('[data-dropzone]').forEach(function (zone) {
                zone.addEventListener('dragover', function (e) {
                    if (!dragged) return;
                    e.preventDefault();
                    zone.classList.add('is-over');
                });
                zone.addEventListener('dragleave', function (e) {
                    if (!zone.contains(e.relatedTarget)) zone.classList.remove('is-over');
                });
                zone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    zone.classList.remove('is-over');
                    if (!dragged) return;

                    var card = dragged;
                    var fromCol = card.closest('.board-col');
                    var toCol = zone.closest('.board-col');
                    var status = toCol.dataset.status;
                    if (fromCol === toCol) return;

                    // Cập nhật giao diện ngay, lỗi thì trả thẻ về chỗ cũ
                    var placeholder = document.createComment('');
                    card.parentNode.insertBefore(placeholder, card);
                    zone.insertBefore(card, zone.firstChild);
                    bump(fromCol, -1); bump(toCol, 1); refresh(fromCol); refresh(toCol);

                    fetch(moveUrl.replace(/\/0$/, '/' + card.dataset.id), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ status: status })
                    }).then(function (r) {
                        if (!r.ok) throw new Error(r.status);
                        return r.json();
                    }).then(function (res) {
                        card.dataset.status = res.status;
                        card.querySelector('.kcard-move select').value = res.status;
                        showToast('Đã chuyển “' + card.querySelector('.kcard-name').textContent.trim() + '” sang “' + res.label + '”.');
                    }).catch(function () {
                        placeholder.parentNode.insertBefore(card, placeholder);
                        bump(fromCol, 1); bump(toCol, -1); refresh(fromCol); refresh(toCol);
                        showToast('Không chuyển được, vui lòng thử lại.', true);
                    }).finally(function () {
                        placeholder.remove();
                    });
                });
            });
        })();
    </script>
@endpush
