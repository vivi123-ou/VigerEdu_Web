@extends('layouts.admin', ['menuKey' => 'attempts'])

@section('title', 'Lượt làm bài')

@section('content')
    <section class="card">
        <form method="get" class="list-toolbar" role="search">
            <input type="search" name="ma" value="{{ request('ma') }}" maxlength="3" placeholder="Tìm theo mã Holland, ví dụ AIS" aria-label="Mã Holland">
            <select name="bo" aria-label="Bộ câu">
                <option value="">Tất cả bộ câu</option>
                @foreach ($sets as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('bo') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="thoi_gian" aria-label="Thời gian">
                <option value="">Mọi lúc</option>
                <option value="hom-nay" @selected(request('thoi_gian') === 'hom-nay')>Hôm nay</option>
                <option value="7-ngay" @selected(request('thoi_gian') === '7-ngay')>7 ngày qua</option>
                <option value="30-ngay" @selected(request('thoi_gian') === '30-ngay')>30 ngày qua</option>
            </select>
            <button class="btn btn--primary" type="submit">@include('partials.icon', ['name' => 'search', 'size' => 16]) Lọc</button>
            @if ($isFiltered)
                <a class="btn" href="{{ route('admin.attempts.index') }}" title="Bỏ lọc">@include('partials.icon', ['name' => 'refresh', 'size' => 16]) Bỏ lọc</a>
            @endif
            <span class="list-toolbar__right muted">{{ $attempts->total() }} lượt</span>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ngày làm</th>
                        <th class="center">Mã Holland</th>
                        <th>Ngành đứng đầu</th>
                        <th>Bộ câu</th>
                        <th>Nguồn</th>
                        <th class="center">Gửi CRM</th>
                        <th class="center">Xem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>{{ $attempt->completed_at->format('d/m/Y H:i') }}</td>
                            <td class="center"><strong>{{ $attempt->holland_code }}</strong></td>
                            <td>{{ $attempt->result['top_university'][0]['name'] ?? '–' }}</td>
                            <td>{{ $attempt->questionSet?->name ?? '–' }}</td>
                            <td>{{ $attempt->utm['utm_source'] ?? ($attempt->source === 'web' ? 'Web' : $attempt->source) }}</td>
                            <td class="center"><span @class(['status', 'status--ok' => $attempt->crm_sync_status === 'sent', 'status--warn' => $attempt->crm_sync_status !== 'sent'])>{{ $attempt->syncStatusLabel() }}</span></td>
                            <td class="center"><a class="icon-btn icon-btn--view" href="{{ route('admin.attempts.show', $attempt) }}" title="Xem bài làm" aria-label="Xem">@include('partials.icon', ['name' => 'eye', 'size' => 16])</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="center muted">{{ $isFiltered ? 'Không có lượt nào khớp bộ lọc.' : 'Chưa có lượt làm bài nào.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $attempts->links('admin.partials.pagination') }}
        <p class="muted" style="margin:1rem 0 0">Số điện thoại, email và việc chăm sóc khách xem ở CRM.</p>
    </section>
@endsection
