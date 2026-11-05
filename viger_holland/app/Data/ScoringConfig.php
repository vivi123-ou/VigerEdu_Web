<?php

namespace App\Data;

use InvalidArgumentException;

/**
 * Các tham số chấm điểm sửa được (trọng số, ngưỡng nhãn, số ngành hiển thị…).
 * Giá trị mặc định = bản chốt (config/holland.php → 'scoring', khớp holland_config.json).
 * Giai đoạn 3: admin sửa trên trang "Cấu hình chấm điểm".
 */
final readonly class ScoringConfig
{
    /**
     * @param  list<array{min: float, label: string}>  $labels  sắp xếp ngưỡng giảm dần
     */
    public function __construct(
        public float $weightCodeMatch = 0.6,
        public float $weightScoreMatch = 0.4,
        public array $labels = [
            ['min' => 0.80, 'label' => 'Rất phù hợp'],
            ['min' => 0.65, 'label' => 'Phù hợp'],
            ['min' => 0.50, 'label' => 'Có thể cân nhắc'],
            ['min' => 0.0, 'label' => 'Ít phù hợp'],
        ],
        public int $topUniversity = 5,
        public int $topVocational = 3,
        public float $di3Threshold = 0.15,
        public string $reasonSubject = 'bạn',
    ) {
        if ($topUniversity < 0 || $topVocational < 0) {
            throw new InvalidArgumentException('Số ngành hiển thị không được âm.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $labels = collect($data['labels'] ?? [])
            ->map(fn (array $row) => ['min' => (float) $row['min'], 'label' => (string) $row['label']])
            ->sortByDesc('min')
            ->values()
            ->all();

        $defaults = new self;

        return new self(
            weightCodeMatch: (float) ($data['weight_code_match'] ?? $defaults->weightCodeMatch),
            weightScoreMatch: (float) ($data['weight_score_match'] ?? $defaults->weightScoreMatch),
            labels: $labels ?: $defaults->labels,
            topUniversity: (int) ($data['top_university'] ?? $defaults->topUniversity),
            topVocational: (int) ($data['top_vocational'] ?? $defaults->topVocational),
            di3Threshold: (float) ($data['di3_threshold'] ?? $defaults->di3Threshold),
            reasonSubject: (string) ($data['reason_subject'] ?? $defaults->reasonSubject),
        );
    }

    /**
     * Cấu hình đang dùng: giá trị mặc định (config/holland.php) được ghi đè bởi giá trị admin lưu
     * ở trang "Cấu hình chấm điểm" (settings.scoring_config, dạng JSON).
     */
    public static function current(): self
    {
        $saved = json_decode((string) setting(self::SETTING_KEY, ''), true);

        return self::fromArray(array_replace(config('holland.scoring', []), is_array($saved) ? $saved : []));
    }

    public const SETTING_KEY = 'scoring_config';

    /**
     * Bản mặc định (bản chốt), dùng cho nút "Đặt lại như ban đầu".
     */
    public static function defaults(): self
    {
        return self::fromArray(config('holland.scoring', []));
    }

    /**
     * @return array{weight_code_match: float, weight_score_match: float, labels: list<array{min: float, label: string}>, top_university: int, top_vocational: int, di3_threshold: float, reason_subject: string}
     */
    public function toArray(): array
    {
        return [
            'weight_code_match' => $this->weightCodeMatch,
            'weight_score_match' => $this->weightScoreMatch,
            'labels' => $this->labels,
            'top_university' => $this->topUniversity,
            'top_vocational' => $this->topVocational,
            'di3_threshold' => $this->di3Threshold,
            'reason_subject' => $this->reasonSubject,
        ];
    }

    public function labelFor(float $fit): string
    {
        foreach ($this->labels as $row) {
            if ($fit >= $row['min']) {
                return $row['label'];
            }
        }

        return (string) (end($this->labels)['label'] ?? '');
    }
}
