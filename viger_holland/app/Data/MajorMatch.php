<?php

namespace App\Data;

/**
 * Mức độ phù hợp với một ngành (một dòng trong top_university / top_vocational / ranking).
 */
final readonly class MajorMatch
{
    public function __construct(
        public string $majorCode,
        public string $name,
        public string $field,
        public string $type,
        public string $codeUsed,
        public float $codeMatch,
        public float $scoreMatch,
        public float $fit,
        public string $label,
        public string $reason,
    ) {}

    /**
     * @return array{major_code: string, name: string, field: string, code_used: string, code_match: float, score_match: float, fit: float, label: string, reason: string}
     */
    public function toArray(): array
    {
        return [
            'major_code' => $this->majorCode,
            'name' => $this->name,
            'field' => $this->field,
            'code_used' => $this->codeUsed,
            'code_match' => round($this->codeMatch, 4),
            'score_match' => round($this->scoreMatch, 4),
            'fit' => round($this->fit, 4),
            'label' => $this->label,
            'reason' => $this->reason,
        ];
    }
}
