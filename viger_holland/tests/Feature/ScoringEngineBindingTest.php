<?php

namespace Tests\Feature;

use App\Catalogs\JsonMajorCatalog;
use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Engines\RiasecFitEngineV2;
use InvalidArgumentException;
use Tests\Fakes\FakeScoringEngine;
use Tests\TestCase;

/**
 * Thay thuật toán chỉ bằng cấu hình, nơi gọi không phải sửa.
 */
class ScoringEngineBindingTest extends TestCase
{
    public function test_default_engine_is_riasec_fit_v2(): void
    {
        $this->assertInstanceOf(RiasecFitEngineV2::class, app(ScoringEngine::class));
        $this->assertInstanceOf(JsonMajorCatalog::class, app(MajorCatalog::class));
    }

    public function test_engine_can_be_swapped_by_configuration(): void
    {
        config([
            'holland.engines.fake_engine' => FakeScoringEngine::class,
            'holland.engine' => 'fake_engine',
        ]);

        $engine = app(ScoringEngine::class);
        $result = $engine->score([['group' => 'R', 'value' => 1, 'max' => 1]], app(MajorCatalog::class)->all());

        $this->assertInstanceOf(FakeScoringEngine::class, $engine);
        $this->assertSame('SEC', $result->code);
        $this->assertSame('fake_engine', $result->toArray()['engine']);
        $this->assertSame('holland-result/1', $result->toArray()['schema_version']);
    }

    public function test_unknown_engine_gives_clear_error(): void
    {
        config(['holland.engine' => 'khong_co']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Không tìm thấy thuật toán chấm điểm 'khong_co'");

        app(ScoringEngine::class);
    }

    public function test_scoring_config_from_laravel_config_matches_holland_config_json(): void
    {
        $json = json_decode((string) file_get_contents(database_path('seeders/data/holland/holland_config.json')), true);
        $scoring = config('holland.scoring');

        $this->assertSame($json['weight_code_match'], $scoring['weight_code_match']);
        $this->assertSame($json['weight_score_match'], $scoring['weight_score_match']);
        $this->assertSame($json['top_university'], $scoring['top_university']);
        $this->assertSame($json['top_vocational'], $scoring['top_vocational']);
        $this->assertSame($json['di3_threshold'], $scoring['di3_threshold']);
        $this->assertEquals($json['labels'], $scoring['labels']);
    }

    public function test_engine_from_container_scores_hs01_like_reference(): void
    {
        $answers = [];
        foreach (['R' => 3, 'I' => 6, 'A' => 8, 'S' => 5, 'E' => 1, 'C' => 2] as $group => $correct) {
            for ($i = 0; $i < 9; $i++) {
                $answers[] = ['group' => $group, 'value' => $i < $correct ? 1 : 0, 'max' => 1];
            }
        }

        $result = app(ScoringEngine::class)->score($answers, app(MajorCatalog::class)->all())->toArray(withRanking: true);

        $this->assertSame('AIS', $result['code']);
        $this->assertSame('N043', $result['top_university'][0]['major_code']);
        $this->assertEqualsWithDelta(0.8011, $result['top_university'][0]['fit'], 0.001);
        $this->assertCount(75, $result['ranking'], 'Xếp hạng đầy đủ mọi ngành đang bật');
    }
}
