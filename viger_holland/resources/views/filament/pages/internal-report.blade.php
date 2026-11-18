{{--
    Báo cáo nội bộ (H4b) theo mẫu admin-filament.html màn hình 3. Có dữ liệu cá nhân: chỉ trang đã đăng nhập, noindex.
    Quản trị viên thấy thêm "In / PDF" và "Sao chép cho AI" (JSON không có họ tên, liên lạc).
--}}
@php
    $c = $r['customer'];
    $hex = $r['hexagon'];
    $num = fn ($v) => \App\Modules\Respondent\Support\InternalReport::number((float) $v);
    $badge = fn (?string $rel) => match ($rel) { 'Tin cậy' => 'ok', 'Cần xem lại' => 'warn', default => 'bad' };
@endphp
<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/admin-holland.css') }}">
    <div class="hz">
        @if ($r['crmUrl'])
            <div class="hz-row hz-noprint">
                <x-filament::button tag="a" :href="$r['crmUrl']" target="_blank" rel="noopener" color="gray" icon="heroicon-o-arrow-top-right-on-square">Mở hồ sơ trong CRM</x-filament::button>
            </div>
        @endif
        @if ($isAdmin)
            <div class="hz-row hz-noprint" x-data="{ copied: false }">
                <x-filament::button color="gray" icon="heroicon-o-printer" x-on:click="window.print()">In / PDF</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-clipboard-document"
                    x-on:click="navigator.clipboard.writeText($refs.ai.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                    <span x-text="copied ? 'Đã sao chép' : 'Sao chép cho AI'">Sao chép cho AI</span>
                </x-filament::button>
                <textarea x-ref="ai" hidden readonly>{{ $r['aiJson'] }}</textarea>
            </div>
        @endif

        <x-filament::section>
            <div class="hz-head">
                <div><small>Đối tượng</small><b>{{ $c['role'] ?? '—' }}</b></div>
                <div><small>Ngày sinh</small><b>{{ $c['birth'] ?? '—' }}</b></div>
                @foreach ($c['extra'] as $label => $value)
                    <div><small>{{ $label }}</small><b>{{ $value }}</b></div>
                @endforeach
                <div>
                    <small>Liên lạc</small>
                    @if ($c['parentPhone'])
                        <b><a class="hz-link" href="tel:{{ $c['parentPhone'] }}">{{ $c['parentPhone'] }}</a> <span class="hz-muted">(phụ huynh)</span></b>
                    @endif
                    @if ($c['phone'])
                        <b><a class="hz-link" href="tel:{{ $c['phone'] }}">{{ $c['phone'] }}</a> <span class="hz-muted">(học sinh)</span></b>
                    @endif
                    <span class="hz-muted">{{ $c['email'] }}</span>
                </div>
                <div>
                    <small>Làm bài</small>
                    <b>{{ $r['session']->answered_at?->format('H:i d/m/Y') }}</b>
                    <span class="hz-muted">
                        Lần thứ {{ $r['attempt'] }}@if ($r['duration']) · {{ $r['duration']->h * 60 + $r['duration']->i }} phút {{ $r['duration']->s }} giây @endif
                    </span>
                </div>
                <div><small>Mã báo cáo</small><b>{{ $r['reportCode'] }}</b></div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Đọc trước khi tư vấn">
            <div class="hz-codes">
                <div><small>Khách đang thấy</small><span class="hz-code gray">{{ $r['customerCode'] }}</span></div>
                <div><small>Mã đầy đủ (nội bộ)</small><span class="hz-code">{{ $r['code3'] }}</span></div>
                @if ($r['alternates'])
                    <div><small>Mã thay thế</small><span class="hz-code gray">{{ implode(', ', $r['alternates']) }}</span></div>
                @endif
                <div>
                    <small>Độ tin cậy bài làm</small>
                    <span class="hz-badge {{ $badge($r['reliability']) }}">{{ $r['reliability'] === 'Tin cậy' ? '✓ Làm bài cẩn thận' : $r['reliability'] }}</span>
                </div>
            </div>
            <ul class="hz-sum">
                @foreach ($r['summary'] as [$title, $text])
                    <li><b>{{ $title }}:</b> {{ $text }}</li>
                @endforeach
            </ul>
        </x-filament::section>

        <div class="hz-two">
            <x-filament::section heading="Điểm 6 nhóm" description="Điểm trung bình 1–5 của các câu mỗi nhóm, xếp từ cao xuống.">
                @foreach ($r['scores'] as $s)
                    <div @class(['hz-bar', 'top' => $s['rank'] <= 3])>
                        <span>{{ $s['code'] }} · {{ $s['name'] }}</span>
                        <span class="t"><i style="width: {{ $s['width'] }}%"></i></span>
                        <span>{{ $num($s['mean']) }}</span>
                        <span class="b hz-muted">{{ $s['band'] }}</span>
                    </div>
                @endforeach
                <p class="hz-muted" style="margin:12px 0 0;font-size:13px">
                    Ba cặp trong mã {{ $r['code3'] }}:
                    @foreach ($r['relations'] as $rel)
                        {{ $rel['names'] }} ({{ mb_strtolower($rel['relation']) }}){{ $loop->last ? '.' : ';' }}
                    @endforeach
                    Độ nhất quán: {{ mb_strtolower((string) $r['consistency']) }}.
                </p>
            </x-filament::section>

            <x-filament::section heading="Lục giác Holland" :description="$r['previous'] ? 'Nhóm càng xa tâm càng được thích. So với lần làm trước.' : 'Nhóm càng xa tâm càng được thích.'">
                <svg class="hz-hex" viewBox="-75 0 450 300" width="100%" role="img" aria-label="Lục giác 6 nhóm">
                    <polygon points="{{ $hex['outline'] }}" fill="none" stroke="#cfd6de"/>
                    <polygon points="{{ $hex['mid'] }}" fill="none" stroke="#e3e7ec" stroke-dasharray="3 4"/>
                    @foreach ($hex['axes'] as [$x, $y])
                        <line x1="150" y1="150" x2="{{ $x }}" y2="{{ $y }}" stroke="#eef1f4"/>
                    @endforeach
                    @if ($r['previous'])
                        <polygon points="{{ $r['previous']['polygon'] }}" fill="none" stroke="#d99a00" stroke-width="1.6" stroke-dasharray="5 4"/>
                    @endif
                    <polygon points="{{ $hex['polygon'] }}" style="fill:rgba(120,140,170,.14);stroke:var(--hz-navy)" stroke-width="1.8" stroke-linejoin="round"/>
                    @foreach ($hex['dots'] as [$x, $y])
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#c1121f"/>
                    @endforeach
                    @foreach ($hex['labels'] as [$text, $x, $y, $anchor])
                        <text x="{{ $x }}" y="{{ $y }}" text-anchor="{{ $anchor }}">{{ $text }}</text>
                    @endforeach
                </svg>
                @if ($r['previous'])
                    <div class="hz-legend">
                        <span><i></i>Lần này ({{ $r['session']->verified_at?->format('d/m') }})</span>
                        <span><i class="prev"></i>Lần trước ({{ $r['previous']['date']?->format('d/m') }}, mã {{ $r['previous']['code3'] }})</span>
                    </div>
                @endif
            </x-filament::section>
        </div>

        <x-filament::section heading="Ngành gợi ý"
            description="Ngành nền của cặp 2 chữ đầu đứng trước, sau đó xếp theo mức khớp với mã {{ $r['code3'] }}. Khách chỉ thấy các ngành có nhãn, không thấy con số.">
            <div class="hz-scroll">
                <table class="hz-table">
                    <thead><tr><th>#</th><th>Ngành (Việt / Đức)</th><th>Mã ngành</th><th>Mức khớp (0–18)</th><th>Khách thấy</th><th>Hệ đào tạo</th><th>Nghề liên quan</th></tr></thead>
                    <tbody>
                        @foreach ($r['majors'] as $m)
                            <tr>
                                <td>{{ $m['rank'] }}</td>
                                <td><b>{{ $m['name_vi'] }}</b><small>{{ $m['name_de'] }}</small>
                                    @unless ($m['verified'])<small>Chưa kiểm tra nguồn chính thức</small>@endunless</td>
                                <td>{{ $m['code'] ?? '—' }}</td>
                                <td>{{ $m['c'] ?? '—' }}</td>
                                <td>@if ($m['shown'])<span class="hz-badge ok">{{ $m['label'] }}</span>@else<span class="hz-muted">Khách không thấy</span>@endif</td>
                                <td>{{ $m['routes'] ?? '—' }}</td>
                                <td>{{ $m['occupations'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="hz-two">
            @if ($r['previous'])
                <x-filament::section heading="So sánh với lần làm trước">
                    <table class="hz-table">
                        <thead><tr><th>Nhóm</th><th>{{ $r['previous']['date']?->format('d/m') }}</th><th>{{ $r['session']->verified_at?->format('d/m') }}</th><th>Thay đổi</th></tr></thead>
                        <tbody>
                            @foreach ($r['scores'] as $s)
                                <tr>
                                    <td>{{ $s['code'] }} · {{ $s['name'] }}</td>
                                    <td>{{ $s['previous'] !== null ? $num($s['previous']) : '—' }}</td>
                                    <td>{{ $num($s['mean']) }}</td>
                                    <td>@if ($s['previous'] !== null){{ $s['mean'] >= $s['previous'] ? '+' : '' }}{{ $num($s['mean'] - $s['previous']) }}@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament::section>
            @endif
            <x-filament::section heading="Diễn biến">
                <ul class="hz-tl">
                    @foreach ($r['timeline'] as [$at, $text])
                        <li><b>{{ $at->format('H:i d/m/Y') }}</b>{{ $text }}</li>
                    @endforeach
                </ul>
                <p class="hz-muted" style="margin:12px 0 0;font-size:13px">Ghi chú tư vấn, cuộc gọi, lịch hẹn: xem trong CRM.</p>
            </x-filament::section>
        </div>

        <x-filament::section heading="{{ count($r['answers']) }} câu khách đã trả lời" collapsible collapsed>
            <div class="hz-scroll">
                <table class="hz-table">
                    <thead><tr><th>#</th><th>Mã</th><th>Chủ đề</th><th>Câu hỏi</th><th>Khách chọn</th><th>Giây</th></tr></thead>
                    <tbody>
                        @foreach ($r['answers'] as $a)
                            <tr>
                                <td>{{ $a['order'] }}</td>
                                <td>{{ $a['code'] }}</td>
                                <td>{{ $a['facet'] ?? $a['group'] }}</td>
                                <td>{{ $a['text'] }}</td>
                                <td>@if ($a['value']){{ str_repeat('★', $a['value']) }}<small>{{ $a['label'] }}</small>@else — @endif</td>
                                <td>{{ $a['seconds'] !== null ? str_replace('.', ',', (string) $a['seconds']) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
