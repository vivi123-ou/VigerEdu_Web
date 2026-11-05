<?php

namespace Tests\Fakes;

use App\Contracts\ScoringEngine;
use App\Data\HollandResult;
use Illuminate\Support\Collection;

/**
 * Engine giả để chứng minh thay được thuật toán: luôn trả mã "SEC",
 * nhưng vẫn đúng cấu trúc holland-result/1.
 */
final class FakeScoringEngine implements ScoringEngine
{
    public function name(): string
    {
        return 'fake_engine';
    }

    public function version(): string
    {
        return '0.1';
    }

    public function score(array $answers, Collection $majors): HollandResult
    {
        return $this->scorePercent([], $majors);
    }

    public function scorePercent(array $percent, Collection $majors): HollandResult
    {
        return new HollandResult(
            engine: $this->name(),
            engineVersion: $this->version(),
            percent: ['R' => 0.0, 'I' => 0.0, 'A' => 0.0, 'S' => 1.0, 'E' => 1.0, 'C' => 1.0],
            code: 'SEC',
            di3: 0.0,
            consistency: 2,
            hasTie: true,
            topUniversity: [],
            topVocational: [],
            fields: [],
            testas: [],
        );
    }
}
