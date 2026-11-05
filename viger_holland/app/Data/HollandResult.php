<?php

namespace App\Data;

/**
 * Kết quả chấm Holland theo cấu trúc `holland-result/1` (docs/HOP_DONG_KET_NOI.md mục 4.2).
 */
final readonly class HollandResult
{
    public const SCHEMA_VERSION = 'holland-result/1';

    /**
     * @param  array<string, float>  $percent  ['R' => 0.33, ...] theo thứ tự R I A S E C
     * @param  list<MajorMatch>  $topUniversity
     * @param  list<MajorMatch>  $topVocational
     * @param  list<array{field: string, score: float, best_major_code: string}>  $fields
     * @param  list<array{group: string, score: float}>  $testas
     * @param  list<MajorMatch>  $ranking  toàn bộ ngành đang bật đã xếp hạng
     */
    public function __construct(
        public string $engine,
        public string $engineVersion,
        public array $percent,
        public string $code,
        public float $di3,
        public int $consistency,
        public bool $hasTie,
        public array $topUniversity,
        public array $topVocational,
        public array $fields,
        public array $testas,
        public array $ranking = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $withRanking = false): array
    {
        $result = [
            'schema_version' => self::SCHEMA_VERSION,
            'engine' => $this->engine,
            'engine_version' => $this->engineVersion,
            'percent' => array_map(fn (float $value) => round($value, 4), $this->percent),
            'code' => $this->code,
            'di3' => round($this->di3, 4),
            'consistency' => $this->consistency,
            'has_tie' => $this->hasTie,
            'top_university' => array_map(fn (MajorMatch $match) => $match->toArray(), $this->topUniversity),
            'top_vocational' => array_map(fn (MajorMatch $match) => $match->toArray(), $this->topVocational),
            'fields' => array_map(fn (array $field) => ['field' => $field['field'], 'score' => round($field['score'], 4), 'best_major_code' => $field['best_major_code']], $this->fields),
            'testas' => array_map(fn (array $group) => ['group' => $group['group'], 'score' => round($group['score'], 4)], $this->testas),
        ];

        if ($withRanking) {
            $result['ranking'] = array_map(fn (MajorMatch $match) => $match->toArray(), $this->ranking);
        }

        return $result;
    }
}
