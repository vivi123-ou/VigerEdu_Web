<?php

namespace Tests\Unit\Engines;

use App\Catalogs\JsonMajorCatalog;
use App\Data\HollandResult;
use App\Data\MajorMatch;
use App\Engines\RiasecFitEngineV2;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Kiểm tra bắt buộc: kết quả khớp test_cases.json (5 học sinh mẫu), sai số 0,001.
 */
class RiasecFitEngineV2TestCasesTest extends TestCase
{
    private const DELTA = 0.001;

    private const DATA_DIR = __DIR__.'/../../../database/seeders/data/holland';

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function studentCases(): array
    {
        $cases = json_decode((string) file_get_contents(self::DATA_DIR.'/test_cases.json'), true, flags: JSON_THROW_ON_ERROR);

        $provided = [];
        foreach ($cases['holland'] as $case) {
            $provided[$case['name']] = [$case];
        }

        return $provided;
    }

    /**
     * @param  array<string, mixed>  $case
     */
    #[DataProvider('studentCases')]
    public function test_matches_expected_result(array $case): void
    {
        $result = $this->scoreCase($case);
        $expected = $case['expected'];

        foreach ($expected['percent'] as $group => $percent) {
            $this->assertEqualsWithDelta($percent, $result->percent[$group], self::DELTA, "Điểm % nhóm {$group}");
        }

        $this->assertSame($expected['code'], $result->code, 'Mã Holland');
        $this->assertEqualsWithDelta($expected['di3'], $result->di3, self::DELTA, 'di3');
        $this->assertSame($expected['consistency'], $result->consistency, 'consistency');
        $this->assertSame($expected['tie'], $result->hasTie, 'has_tie');

        $this->assertMatches($expected['top_university'], $result->topUniversity, 'Đại học');
        $this->assertMatches($expected['top_vocational'], $result->topVocational, 'Học nghề');

        $this->assertSame($expected['best_field'], $result->fields[0]['field'], 'Lĩnh vực mạnh nhất');
    }

    public function test_hs01_first_major_is_german_studies_with_fit_0_8011(): void
    {
        $result = $this->scoreCase(self::studentCases()['HS01 Lan'][0]);
        $first = $result->topUniversity[0];

        $this->assertSame('AIS', $result->code);
        $this->assertSame('N043', $first->majorCode);
        $this->assertSame('Ngôn ngữ và Văn học Đức', $first->name);
        $this->assertSame('IAS', $first->codeUsed);
        $this->assertEqualsWithDelta(0.8011, $first->fit, self::DELTA);
        $this->assertSame('Rất phù hợp', $first->label);
        $this->assertSame('AIS ↔ IAS · em đạt I 67%, A 89%, S 56%', $first->reason);
    }

    /**
     * @param  list<array<string, mixed>>  $expected
     * @param  list<MajorMatch>  $actual
     */
    private function assertMatches(array $expected, array $actual, string $type): void
    {
        $this->assertCount(count($expected), $actual, "Số ngành {$type}");

        foreach ($expected as $i => $row) {
            $match = $actual[$i];
            $position = $type.' #'.($i + 1);

            $this->assertSame($row['id'], $match->majorCode, "{$position}: mã ngành");
            $this->assertSame($row['code_used'], $match->codeUsed, "{$position}: mã dùng");
            $this->assertEqualsWithDelta($row['fit'], $match->fit, self::DELTA, "{$position}: FIT");

            if (isset($row['code_match'])) {
                $this->assertEqualsWithDelta($row['code_match'], $match->codeMatch, self::DELTA, "{$position}: khớp mã");
                $this->assertEqualsWithDelta($row['score_match'], $match->scoreMatch, self::DELTA, "{$position}: khớp điểm");
            }
        }
    }

    /**
     * Dựng bài làm: mỗi nhóm N câu Đúng/Không, số câu "Đúng" theo correct_per_group.
     *
     * @param  array<string, mixed>  $case
     */
    private function scoreCase(array $case): HollandResult
    {
        $answers = [];
        foreach ($case['correct_per_group'] as $group => $correct) {
            for ($i = 0; $i < $case['questions_per_group']; $i++) {
                $answers[] = ['group' => $group, 'value' => $i < $correct ? 1 : 0, 'max' => 1];
            }
        }

        $catalog = new JsonMajorCatalog(self::DATA_DIR.'/majors.json');

        return (new RiasecFitEngineV2)->score($answers, $catalog->all());
    }
}
