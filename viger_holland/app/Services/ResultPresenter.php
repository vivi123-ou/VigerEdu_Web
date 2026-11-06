<?php

namespace App\Services;

use App\Contracts\MajorCatalog;
use App\Data\ScoringConfig;
use App\Models\Attempt;
use App\Models\Question;

/**
 * Chuẩn bị dữ liệu kết quả để hiển thị cho khách (trang kết quả, email).
 * Không đưa ra điểm FIT dạng số, không có GREEN/YELLOW/RED.
 */
final class ResultPresenter
{
    public const GROUPS = ['R', 'I', 'A', 'S', 'E', 'C'];

    public function __construct(private readonly MajorCatalog $catalog) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Attempt $attempt): array
    {
        $result = $attempt->result;
        $majors = $this->catalog->all()->keyBy('code');
        $percent = $result['percent'];

        $groups = collect(self::GROUPS)->map(fn (string $letter) => [
            'letter' => $letter,
            'name' => setting("group_name_{$letter}", $letter),
            'description' => setting("group_desc_{$letter}"),
            'percent' => (int) round(($percent[$letter] ?? 0) * 100),
            'in_code' => str_contains($result['code'], $letter),
        ])->all();

        $codeLetters = collect(str_split($result['code']))->map(fn (string $letter) => collect($groups)->firstWhere('letter', $letter))->all();

        $presentMajor = fn (array $row) => [
            'code' => $row['major_code'],
            'name' => $row['name'],
            'label' => $row['label'],
            'description' => $majors->get($row['major_code'])?->description,
            'name_de' => $majors->get($row['major_code'])?->nameDe,
        ];

        $di3Threshold = ScoringConfig::current()->di3Threshold;

        return [
            'code' => $result['code'],
            'codeLetters' => $codeLetters,
            'groups' => $groups,
            'fields' => array_slice($result['fields'] ?? [], 0, 3),
            'university' => array_map($presentMajor, $result['top_university'] ?? []),
            'vocational' => array_map($presentMajor, $result['top_vocational'] ?? []),
            'isFlat' => ($result['di3'] ?? 1) < $di3Threshold,
            'hasTie' => (bool) ($result['has_tie'] ?? false),
            // Xem trước / sửa trên trang: hiện chỗ đặt tên khách thay cho tên thật.
            'name' => $attempt->getAttribute('preview_name') ?? $this->firstName($attempt),
        ];
    }

    /**
     * Tên gọi trong lời chào: chữ cuối của họ tên (ví dụ "Nguyễn Văn An" → "An").
     */
    private function firstName(Attempt $attempt): ?string
    {
        $nameCode = Question::withTrashed()->where('lead_field', 'student_name')->value('code');
        $name = $nameCode ? ($attempt->contact[$nameCode] ?? null) : null;

        if (blank($name)) {
            return null;
        }

        $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];

        return end($parts) ?: null;
    }
}
