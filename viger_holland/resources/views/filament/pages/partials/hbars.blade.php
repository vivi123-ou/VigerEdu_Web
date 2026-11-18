{{-- Thanh ngang: $rows = [[nhãn, số], …], $max, $empty. --}}
@if (array_sum(array_column($rows, 1)) === 0)
    <p class="hz-muted" style="margin:0">{{ $empty }}</p>
@else
    @foreach ($rows as [$label, $n])
        <div class="hz-hbar">
            <span class="l">{{ $label }}</span>
            <span class="t"><i style="width: {{ round($n / $max * 100) }}%"></i></span>
            <span class="n">{{ number_format($n, 0, ',', '.') }}</span>
        </div>
    @endforeach
@endif
