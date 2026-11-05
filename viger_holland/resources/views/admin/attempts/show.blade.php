@extends('layouts.admin', ['menuKey' => 'attempts'])

@section('title', 'Lượt làm bài '.$attempt->completed_at->format('d/m/Y H:i'))

@section('content')
    @php
        $result = $attempt->result;
        $groupNames = collect(['R', 'I', 'A', 'S', 'E', 'C'])->mapWithKeys(fn ($g) => [$g => setting("group_name_{$g}", $g)]);
    @endphp

    <p><a href="{{ route('admin.attempts.index') }}">← Danh sách lượt làm bài</a></p>

    <div class="stats">
        <div class="stat">
            <p class="stat__value">{{ $result['code'] }}</p>
            <p class="stat__label">Mã Holland</p>
        </div>
        <div class="stat">
            <p class="stat__value">{{ number_format($result['di3'], 2, ',', '') }}</p>
            <p class="stat__label">Độ rõ hồ sơ (di3){{ $result['di3'] < \App\Data\ScoringConfig::current()->di3Threshold ? ' – hồ sơ phẳng' : '' }}</p>
        </div>
        <div class="stat">
            <p class="stat__value">{{ $result['has_tie'] ? 'Có' : 'Không' }}</p>
            <p class="stat__label">Bằng điểm quanh 3 chữ đầu</p>
        </div>
        <div class="stat">
            <p class="stat__value stat__value--small">{{ $attempt->syncStatusLabel() }}</p>
            <p class="stat__label">Gửi CRM{{ $attempt->crm_customer_code ? ' · Mã KH '.$attempt->crm_customer_code : '' }}</p>
        </div>
    </div>

    <section class="card">
        <h2 class="card__title">Điểm 6 nhóm</h2>
        <ul class="bars">
            @foreach ($result['percent'] as $group => $percent)
                <li class="bars__row">
                    <span>{{ $group }} – {{ $groupNames[$group] }}</span>
                    <span class="bars__track"><span class="bars__fill" style="width: {{ round($percent * 100) }}%"></span></span>
                    <span>{{ round($percent * 100) }}%</span>
                </li>
            @endforeach
        </ul>
        <p class="muted">Bộ câu: {{ $attempt->questionSet?->name ?? '–' }} · Thuật toán: {{ $attempt->engine }} {{ $attempt->engine_version }} · Mã lượt: <code>{{ $attempt->uuid }}</code></p>
    </section>

    <section class="card">
        <h2 class="card__title">Xếp hạng đầy đủ các ngành</h2>
        <form method="get" class="filters">
            <div class="field">
                <label for="loai">Loại</label>
                <select id="loai" name="loai">
                    <option value="">Tất cả</option>
                    <option value="dai-hoc" @selected(request('loai') === 'dai-hoc')>Đại học</option>
                    <option value="hoc-nghe" @selected(request('loai') === 'hoc-nghe')>Học nghề</option>
                </select>
            </div>
            <div class="field">
                <label for="linh_vuc">Lĩnh vực</label>
                <select id="linh_vuc" name="linh_vuc">
                    <option value="">Tất cả lĩnh vực</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field }}" @selected(request('linh_vuc') === $field)>{{ $field }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn" type="submit">Lọc</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Ngành</th><th>Lĩnh vực</th><th>Mã dùng</th><th>Khớp mã</th><th>Khớp điểm</th><th>FIT</th><th>Nhãn</th></tr>
                </thead>
                <tbody>
                    @forelse ($ranking as $row)
                        <tr @class(['is-highlight' => $topCodes->contains($row['major_code'])])>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['major_code'] }} · {{ $row['name'] }}</td>
                            <td>{{ $row['field'] }}</td>
                            <td>{{ $row['code_used'] }}</td>
                            <td>{{ number_format($row['code_match'] * 100, 0) }}%</td>
                            <td>{{ number_format($row['score_match'] * 100, 0) }}%</td>
                            <td><strong>{{ number_format($row['fit'], 4, ',', '') }}</strong></td>
                            <td>{{ $row['label'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Không có ngành nào khớp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="muted">Dòng tô nền là ngành đã hiện cho khách (top đại học / học nghề). Điểm FIT chỉ hiện trong trang quản trị, không hiện cho khách.</p>
    </section>

    <section class="card">
        <h2 class="card__title">Bài làm từng câu</h2>
        <div class="table-wrap">
            <table class="table table--wrap">
                <thead><tr><th>Mã</th><th>Câu hỏi</th><th>Khách chọn</th><th>Điểm theo nhóm</th></tr></thead>
                <tbody>
                    @foreach ($attempt->answers as $answer)
                        <tr>
                            <td>{{ $answer['code'] }}</td>
                            <td>{{ $answer['text'] }}</td>
                            <td>{{ $answer['answer'] ?? '–' }}</td>
                            <td>
                                {{-- Lượt làm bài cũ (trước khi có nhiều kiểu câu) chỉ có group / value / max --}}
                                @foreach ($answer['scores'] ?? [['group' => $answer['group'], 'value' => $answer['value'], 'max' => $answer['max']]] as $score)
                                    {{ $score['group'] }}: {{ $score['value'] }} / {{ $score['max'] }}@unless ($loop->last) · @endunless
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($infoRows->isNotEmpty())
        <section class="card">
            <h2 class="card__title">Thông tin khách cung cấp</h2>
            <dl class="info-list">
                @foreach ($infoRows as $row)
                    <dt>{{ $row['label'] }}</dt>
                    <dd>{{ $row['value'] }}</dd>
                @endforeach
            </dl>
            <p class="muted">Họ tên, ngày sinh, số điện thoại, email (của khách và phụ huynh) xem trong CRM.</p>
        </section>
    @endif
@endsection
