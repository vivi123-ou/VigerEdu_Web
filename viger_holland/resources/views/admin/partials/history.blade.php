{{-- Lịch sử thay đổi: ai sửa gì lúc nào, nút "Quay lại bản này". Biến: $activities --}}
<section class="card">
    <h2 class="card__title">Lịch sử thay đổi</h2>
    @if ($activities->isEmpty())
        <p class="muted">Chưa có thay đổi nào được ghi lại.</p>
    @else
        <ul class="history">
            @foreach ($activities as $activity)
                <li class="history__item">
                    <div>
                        <strong>{{ $activity->actionLabel() }}</strong> · {{ $activity->description }}
                        <p class="muted history__meta">{{ $activity->created_at->format('H:i d/m/Y') }} · {{ $activity->user?->name ?? 'Hệ thống' }}</p>
                    </div>
                    @if ($activity->canRevert())
                        <form method="post" action="{{ route('admin.history.revert', $activity) }}" data-confirm="Quay lại nội dung trước lần sửa này?">
                            @csrf
                            <button class="btn btn--small" type="submit">Quay lại bản này</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
