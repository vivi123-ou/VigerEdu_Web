@extends('layouts.admin', ['menuKey' => 'attempts'])

@section('title', 'Lượt làm bài')

@section('content')
    <p class="lead">Mỗi dòng là một lần khách làm bài. Bấm vào dòng để xem bài làm từng câu và bảng xếp hạng ngành. Số điện thoại, email và việc chăm sóc khách xem ở CRM.</p>

    <form method="get" class="filters">
        <div class="field">
            <label for="ma">Lọc theo mã Holland</label>
            <input id="ma" name="ma" value="{{ request('ma') }}" maxlength="3" placeholder="Ví dụ: AIS">
        </div>
        <button class="btn" type="submit">Lọc</button>
        @if (request()->filled('ma'))
            <a class="btn btn--plain" href="{{ route('admin.attempts.index') }}">Bỏ lọc</a>
        @endif
    </form>

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ngày làm</th>
                        <th>Mã Holland</th>
                        <th>Ngành đứng đầu</th>
                        <th>Bộ câu</th>
                        <th>Nguồn</th>
                        <th>Gửi CRM</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>{{ $attempt->completed_at->format('d/m/Y H:i') }}</td>
                            <td><strong>{{ $attempt->holland_code }}</strong></td>
                            <td>{{ $attempt->result['top_university'][0]['name'] ?? '–' }}</td>
                            <td>{{ $attempt->questionSet?->name ?? '–' }}</td>
                            <td>{{ $attempt->utm['utm_source'] ?? ($attempt->source === 'web' ? 'Web' : $attempt->source) }}</td>
                            <td><span @class(['status', 'status--ok' => $attempt->crm_sync_status === 'sent', 'status--warn' => $attempt->crm_sync_status !== 'sent'])>{{ $attempt->syncStatusLabel() }}</span></td>
                            <td><a href="{{ route('admin.attempts.show', $attempt) }}">Xem</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Chưa có lượt làm bài nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $attempts->links('admin.partials.pagination') }}
    </section>
@endsection
