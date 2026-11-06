<?php

namespace Tests\Feature;

use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Data\ScoringConfig;
use App\Services\ResultPresenter;
use Tests\TestCase;

/**
 * 100 hồ sơ giả lập giống bài 54 câu Đúng/Không (mỗi nhóm 0–9 câu đúng): 60 ngẫu nhiên, 30 có nhóm nổi trội, 10 điểm đều.
 * Kiểm tra gợi ý ngành bám đúng dữ liệu ngành (không đổi công thức, chỉ kiểm tính chất).
 */
class ScoringHundredProfilesTest extends TestCase
{
    private const GROUPS = ['R', 'I', 'A', 'S', 'E', 'C'];

    /**
     * @return list<array{counts: array<string, int>, main?: string}>
     */
    private function profiles(): array
    {
        mt_srand(20261106);
        $profiles = [];

        for ($i = 0; $i < 60; $i++) {
            $profiles[] = ['counts' => array_combine(self::GROUPS, array_map(fn () => mt_rand(0, 9), self::GROUPS))];
        }
        for ($i = 0; $i < 30; $i++) {
            $counts = array_combine(self::GROUPS, array_map(fn () => mt_rand(0, 3), self::GROUPS));
            $main = self::GROUPS[$i % 6];
            $counts[$main] = mt_rand(8, 9);
            $profiles[] = ['counts' => $counts, 'main' => $main];
        }
        for ($i = 0; $i < 10; $i++) {
            $base = mt_rand(3, 7);
            $profiles[] = ['counts' => array_combine(self::GROUPS, array_map(fn () => max(0, min(9, $base + mt_rand(-1, 1))), self::GROUPS))];
        }

        return $profiles;
    }

    public function test_hundred_profiles_follow_major_data(): void
    {
        $engine = app(ScoringEngine::class);
        $majors = app(MajorCatalog::class)->all();
        $config = app(ScoringConfig::class);
        $profiles = $this->profiles();
        $this->assertCount(100, $profiles);

        $recommended = [];

        foreach ($profiles as $index => $profile) {
            $percent = array_map(fn (int $count) => $count / 9, $profile['counts']);
            $result = $engine->scorePercent($percent, $majors)->toArray();
            $label = "Hồ sơ #{$index} ".json_encode($profile['counts']);

            $this->assertMatchesRegularExpression('/^[RIASEC]{3}$/', $result['code'], $label);
            $this->assertCount(count(array_unique(str_split($result['code']))), str_split($result['code']), $label.': mã không trùng chữ');

            // Mã = 3 nhóm điểm cao nhất
            $sorted = $percent;
            arsort($sorted);
            $this->assertEqualsWithDelta(array_values($sorted)[2], $percent[$result['code'][2]], 1e-9, $label);

            $this->assertCount($config->topUniversity, $result['top_university'], $label);
            $this->assertCount($config->topVocational, $result['top_vocational'], $label);

            foreach (['top_university', 'top_vocational'] as $list) {
                $fits = array_column($result[$list], 'fit');
                $desc = $fits;
                rsort($desc);
                $this->assertSame($desc, $fits, $label.": {$list} sắp theo FIT giảm dần");

                foreach ($result[$list] as $match) {
                    $major = $majors->firstWhere('code', $match['major_code']);
                    $this->assertNotNull($major, $label.': ngành phải có trong danh mục');
                    $this->assertContains($match['code_used'], $major->hollandCodes, $label.': mã dùng phải là mã của ngành');
                    $this->assertSame($config->labelFor($match['fit']), $match['label'], $label.': nhãn đúng ngưỡng');
                    $this->assertGreaterThanOrEqual(0, $match['fit']);
                    $this->assertLessThanOrEqual(1, $match['fit']);
                    $recommended[$match['major_code']] = true;
                }
            }

            // Ngành đứng đầu luôn chung ít nhất một chữ với mã học sinh
            $this->assertNotEmpty(array_intersect(str_split($result['top_university'][0]['code_used']), str_split($result['code'])), $label);

            // Hồ sơ có nhóm nổi trội: nhóm đó có trong mã ngành đứng đầu
            if (isset($profile['main'])) {
                $this->assertStringContainsString($profile['main'], $result['top_university'][0]['code_used'], $label);
                $this->assertSame($profile['main'], $result['code'][0], $label);
            }
        }

        // Gợi ý đa dạng: phần lớn ngành / nghề đều có lúc được gợi ý
        $this->assertGreaterThanOrEqual(60, count($recommended), 'Ít nhất 60 / 75 ngành – nghề xuất hiện trong gợi ý của 100 hồ sơ');
    }

    public function test_tie_note_only_when_third_and_fourth_groups_are_equal(): void
    {
        $this->assertTrue(ResultPresenter::thirdAndFourthTied(['R' => 0.9, 'I' => 0.8, 'A' => 0.5, 'S' => 0.5, 'E' => 0.1, 'C' => 0.0]));
        $this->assertFalse(ResultPresenter::thirdAndFourthTied(['R' => 0.9, 'I' => 0.9, 'A' => 0.5, 'S' => 0.4, 'E' => 0.1, 'C' => 0.0]), 'Bằng điểm ở nhóm 1–2 chỉ đổi thứ tự chữ, không nhắc');
    }
}
