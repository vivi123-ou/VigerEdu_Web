<?php

namespace Tests\Unit\Engines;

use App\Data\HollandResult;
use App\Data\Major;
use App\Data\ScoringConfig;
use App\Engines\RiasecFitEngineV2;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RiasecFitEngineV2Test extends TestCase
{
    private const DELTA = 0.001;

    public function test_similarity_on_hexagon(): void
    {
        $this->assertSame(3, RiasecFitEngineV2::similarity('R', 'R'), 'trùng');
        $this->assertSame(2, RiasecFitEngineV2::similarity('R', 'I'), 'kề');
        $this->assertSame(2, RiasecFitEngineV2::similarity('R', 'C'), 'kề (vòng)');
        $this->assertSame(1, RiasecFitEngineV2::similarity('R', 'A'), 'cách một');
        $this->assertSame(0, RiasecFitEngineV2::similarity('R', 'S'), 'đối diện');
        $this->assertSame(0, RiasecFitEngineV2::similarity('I', 'E'), 'đối diện');
    }

    public function test_tie_is_ordered_by_riasec_and_flagged(): void
    {
        $result = $this->engine()->scorePercent(['R' => 0.5, 'I' => 0.5, 'A' => 0.5, 'S' => 0.2, 'E' => 0.1, 'C' => 0.0], $this->majors());

        $this->assertSame('RIA', $result->code);
        $this->assertTrue($result->hasTie);
    }

    public function test_no_tie_when_scores_are_distinct(): void
    {
        $result = $this->engine()->scorePercent(['R' => 0.9, 'I' => 0.7, 'A' => 0.5, 'S' => 0.3, 'E' => 0.2, 'C' => 0.1], $this->majors());

        $this->assertSame('RIA', $result->code);
        $this->assertFalse($result->hasTie);
    }

    public function test_flat_profile_gives_zero_correlation_and_neutral_score_match(): void
    {
        $flat = array_fill_keys(RiasecFitEngineV2::GROUPS, 0.5);

        $result = $this->engine()->scorePercent($flat, $this->majors());

        $this->assertSame('RIA', $result->code);
        $this->assertEqualsWithDelta(0.0, $result->di3, self::DELTA);
        $this->assertTrue($result->hasTie);
        $this->assertEqualsWithDelta(0.0, RiasecFitEngineV2::correlation($flat, 'RIA'), self::DELTA);
        foreach ($result->ranking as $match) {
            $this->assertEqualsWithDelta(0.5, $match->scoreMatch, self::DELTA, 'mẫu số 0 thì r = 0, score_match = 0,5');
        }
    }

    public function test_major_with_three_codes_uses_code_giving_highest_fit(): void
    {
        $majors = collect([
            new Major('N900', Major::TYPE_UNIVERSITY, 'Ngành ba mã', 'Thử', ['SEC', 'RIA', 'AES']),
        ]);

        $result = $this->engine()->scorePercent(['R' => 0.9, 'I' => 0.8, 'A' => 0.6, 'S' => 0.2, 'E' => 0.1, 'C' => 0.3], $majors);

        $this->assertSame('RIA', $result->topUniversity[0]->codeUsed);
        $this->assertEqualsWithDelta(1.0, $result->topUniversity[0]->codeMatch, self::DELTA);
    }

    public function test_inactive_majors_and_majors_without_valid_code_are_skipped(): void
    {
        $majors = collect([
            new Major('N901', Major::TYPE_UNIVERSITY, 'Đang tắt', 'Thử', ['RIA'], isActive: false),
            new Major('N902', Major::TYPE_UNIVERSITY, 'Không có mã', 'Thử', []),
            new Major('N903', Major::TYPE_UNIVERSITY, 'Đang bật', 'Thử', ['RIA']),
        ]);

        $result = $this->engine()->scorePercent(['R' => 0.9, 'I' => 0.8, 'A' => 0.6, 'S' => 0.2, 'E' => 0.1, 'C' => 0.3], $majors);

        $this->assertSame(['N903'], array_map(fn ($match) => $match->majorCode, $result->ranking));
    }

    public function test_likert_scale_gives_same_percent_as_equivalent_answers(): void
    {
        // Thang 5 mức (0–4): nhóm R được 6/12 = 50%
        $answers = [
            ['group' => 'R', 'value' => 4, 'max' => 4],
            ['group' => 'R', 'value' => 2, 'max' => 4],
            ['group' => 'R', 'value' => 0, 'max' => 4],
            ['group' => 'I', 'value' => 3, 'max' => 4],
        ];

        $percent = RiasecFitEngineV2::percentFromAnswers($answers);

        $this->assertEqualsWithDelta(0.5, $percent['R'], self::DELTA);
        $this->assertEqualsWithDelta(0.75, $percent['I'], self::DELTA);
        $this->assertSame(0.0, $percent['A'], 'nhóm không có câu nào thì 0');
    }

    public function test_score_from_answers_equals_score_from_percent(): void
    {
        $answers = [];
        foreach (['R' => 3, 'I' => 6, 'A' => 8, 'S' => 5, 'E' => 1, 'C' => 2] as $group => $correct) {
            for ($i = 0; $i < 9; $i++) {
                $answers[] = ['group' => $group, 'value' => $i < $correct ? 1 : 0, 'max' => 1];
            }
        }

        $fromAnswers = $this->engine()->score($answers, $this->majors());
        $fromPercent = $this->engine()->scorePercent(RiasecFitEngineV2::percentFromAnswers($answers), $this->majors());

        $this->assertSame($fromAnswers->toArray(true), $fromPercent->toArray(true));
    }

    public function test_labels_follow_configured_thresholds(): void
    {
        $config = new ScoringConfig;

        $this->assertSame('Rất phù hợp', $config->labelFor(0.80));
        $this->assertSame('Phù hợp', $config->labelFor(0.7999));
        $this->assertSame('Phù hợp', $config->labelFor(0.65));
        $this->assertSame('Có thể cân nhắc', $config->labelFor(0.5));
        $this->assertSame('Ít phù hợp', $config->labelFor(0.49));
    }

    public function test_weights_and_top_counts_come_from_config(): void
    {
        $config = ScoringConfig::fromArray(['weight_code_match' => 1.0, 'weight_score_match' => 0.0, 'top_university' => 1, 'top_vocational' => 0]);
        $engine = new RiasecFitEngineV2($config);

        $result = $engine->scorePercent(['R' => 0.9, 'I' => 0.8, 'A' => 0.6, 'S' => 0.2, 'E' => 0.1, 'C' => 0.3], $this->majors());

        $this->assertCount(1, $result->topUniversity);
        $this->assertCount(0, $result->topVocational);
        $this->assertEqualsWithDelta($result->topUniversity[0]->codeMatch, $result->topUniversity[0]->fit, self::DELTA);
    }

    public function test_equal_fit_is_ordered_by_code_match_then_major_code(): void
    {
        $majors = collect([
            new Major('N030', Major::TYPE_UNIVERSITY, 'B', 'Thử', ['RIA']),
            new Major('N010', Major::TYPE_UNIVERSITY, 'A', 'Thử', ['RIA']),
        ]);

        $result = $this->engine()->scorePercent(['R' => 0.9, 'I' => 0.8, 'A' => 0.6, 'S' => 0.2, 'E' => 0.1, 'C' => 0.3], $majors);

        $this->assertSame(['N010', 'N030'], array_map(fn ($match) => $match->majorCode, $result->ranking));
    }

    public function test_result_follows_holland_result_1_contract(): void
    {
        $result = $this->engine()->scorePercent(['R' => 0.33, 'I' => 0.67, 'A' => 0.89, 'S' => 0.56, 'E' => 0.11, 'C' => 0.22], $this->majors());
        $array = $result->toArray();

        $this->assertSame(HollandResult::SCHEMA_VERSION, $array['schema_version']);
        $this->assertSame('riasec_fit_v2', $array['engine']);
        $this->assertSame('2.0', $array['engine_version']);
        $this->assertSame(['R', 'I', 'A', 'S', 'E', 'C'], array_keys($array['percent']));
        $this->assertSame(
            ['schema_version', 'engine', 'engine_version', 'percent', 'code', 'di3', 'consistency', 'has_tie', 'top_university', 'top_vocational', 'fields', 'testas'],
            array_keys($array),
        );
        $this->assertSame(
            ['major_code', 'name', 'field', 'code_used', 'code_match', 'score_match', 'fit', 'label', 'reason'],
            array_keys($array['top_university'][0]),
        );
        $this->assertSame(['field', 'score', 'best_major_code'], array_keys($array['fields'][0]));
        $this->assertSame(['group', 'score'], array_keys($array['testas'][0]));
        $this->assertArrayNotHasKey('ranking', $array);
        $this->assertArrayHasKey('ranking', $result->toArray(withRanking: true));
    }

    public function test_testas_counts_primary_and_secondary_groups(): void
    {
        $majors = collect([
            new Major('N001', Major::TYPE_UNIVERSITY, 'Ngành', 'Thử', ['RIA'], testasPrimary: 'G1', testasSecondary: 'G2'),
        ]);

        $result = $this->engine()->scorePercent(['R' => 0.9, 'I' => 0.8, 'A' => 0.6, 'S' => 0.2, 'E' => 0.1, 'C' => 0.3], $majors);

        $this->assertSame(['G1', 'G2'], array_column($result->testas, 'group'));
    }

    public function test_missing_group_in_percent_input_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->engine()->scorePercent(['R' => 0.5], $this->majors());
    }

    private function engine(): RiasecFitEngineV2
    {
        return new RiasecFitEngineV2(new ScoringConfig);
    }

    /**
     * @return Collection<int, Major>
     */
    private function majors(): Collection
    {
        return collect([
            new Major('N001', Major::TYPE_UNIVERSITY, 'Cơ khí', 'Kỹ thuật', ['RIE'], testasPrimary: 'G1'),
            new Major('N043', Major::TYPE_UNIVERSITY, 'Ngôn ngữ Đức', 'Ngôn ngữ', ['IAS'], testasPrimary: 'G4'),
            new Major('H007', Major::TYPE_VOCATIONAL, 'Đầu bếp', 'Dịch vụ', ['ARC']),
        ]);
    }
}
