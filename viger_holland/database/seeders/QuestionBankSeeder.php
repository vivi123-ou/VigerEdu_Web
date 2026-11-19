<?php

namespace Database\Seeders;

use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\QuestionFacet;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Làm gì:   Nạp thư viện 300 câu và 60 chủ đề từ question_bank_300.csv (bản của Thầy).
 *           Thầy đã chọn bộ này nên câu nạp ở trạng thái "đã duyệt". Câu viết "Em…" đổi thành "Tôi…" (quyết định 14/11).
 * Ai gọi:   DatabaseSeeder. Chạy lại không trùng; câu đã có thì không ghi đè (admin có thể đã sửa).
 * Dữ liệu:  bảng question_facets, questions.
 */
class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $rows = $this->rows();
        $facetIds = [];

        foreach ($rows as $row) {
            $facetIds[$row['facet_id']] ??= QuestionFacet::firstOrCreate(['code' => $row['facet_id']], [
                'group' => $row['riasec_group'], 'name' => $row['facet_name_vi'], 'sort_order' => count($facetIds) + 1,
            ])->id;
        }

        foreach ($rows as $row) {
            Question::withTrashed()->firstOrCreate(['code' => $row['question_id']], [
                'group' => $row['riasec_group'],
                'question_facet_id' => $facetIds[$row['facet_id']],
                'text' => self::firstPerson($row['question_text_vi']),
                'priority_tier' => $row['priority_tier'],
                'selection_weight' => (float) $row['selection_weight'],
                'is_anchor' => strtolower((string) $row['is_anchor']) === 'true',
                'review_status' => Question::STATUS_APPROVED,
                'validation_status' => $row['validation_status'] ?: null,
                'language' => $row['language'] ?: 'vi-VN',
                'target_group' => $row['target_group'] ?: null,
                'item_version' => $row['item_version'] ?: '1.0',
                'review_note' => $row['review_note'] ?: null,
                'source_reference' => $row['source_reference'] ?: null,
            ]);
        }
    }

    /**
     * Câu tự nhận xét viết ở ngôi thứ nhất: "Em thích…" → "Tôi thích…", "…, em muốn…" → "…, tôi muốn…".
     */
    public static function firstPerson(string $text): string
    {
        $text = preg_replace('/(?<!\p{L})Em(?!\p{L})/u', 'Tôi', $text) ?? $text;

        return preg_replace('/(?<!\p{L})em(?!\p{L})/u', 'tôi', $text) ?? $text;
    }

    /**
     * @return list<array<string, string>>
     */
    private function rows(): array
    {
        $path = config('holland.seed_path').'/question_bank_300.csv';
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Không đọc được {$path}");
        }

        $header = fgetcsv($handle);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === count($header)) {
                $rows[] = array_combine($header, $line);
            }
        }
        fclose($handle);

        return $rows;
    }
}
