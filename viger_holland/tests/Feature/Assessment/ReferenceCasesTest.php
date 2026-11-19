<?php

namespace Tests\Feature\Assessment;

use App\Modules\Assessment\Services\MajorMatcher;
use App\Modules\Assessment\Services\ScoringEngine;
use App\Modules\Assessment\Support\TestConfig;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 8 ca kiểm thử của Thầy (docs/holland_v3/cases.json, sinh từ bản tham chiếu HollandEngine.php):
 * bộ máy trong repo phải ra đúng điểm, thứ tự, mã 2 / 3 chữ, chữ thứ 3, mã thay thế, quan hệ, độ nhất quán, trục,
 * độ tin cậy, cờ chất lượng và 5 ngành đầu, với cài đặt mặc định và danh mục nạp từ dữ liệu của Thầy.
 */
class ReferenceCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function cases(): array
    {
        $cases = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/docs/holland_v3/cases.json'), true);

        return collect(array_keys($cases))->mapWithKeys(fn (string $name) => [$name => [$name]])->all();
    }

    #[DataProvider('cases')]
    public function test_engine_reproduces_reference_case(string $name): void
    {
        $this->seed(CatalogSeeder::class);
        $case = json_decode((string) file_get_contents(base_path('docs/holland_v3/cases.json')), true)[$name];
        $expected = $case['expected'];
        $settings = TestConfig::DEFAULTS;

        $result = (new ScoringEngine($settings))->score($case['answers']);
        $majors = (new MajorMatcher($settings))->recommend($result['code3'], $result['groups']);

        $this->assertEqualsWithDelta($expected['means'], collect($result['groups'])->map(fn ($g) => $g['mean'])->all(), 0.0001, 'Điểm 6 nhóm');
        $this->assertSame($expected['order'], $result['order'], 'Thứ tự nhóm');
        $this->assertSame($expected['code2'], $result['code2']);
        $this->assertSame($expected['code3'], $result['code3']);
        $this->assertSame($expected['third_detail'], $result['third_detail'], 'Khách có thấy chữ thứ 3 không');
        $this->assertSame($expected['alternates'], $result['alternates'], 'Mã thay thế');
        $this->assertSame($expected['relation_1_2'], $result['relations']['1-2']['relation']);
        $this->assertSame($expected['consistency'], $result['consistency']);
        $this->assertEqualsWithDelta($expected['strength'], $result['profile']['strength'], 0.0001, 'Độ mạnh trục Prediger');
        $this->assertSame($expected['reliability'], $result['quality']['reliability']);
        $this->assertSame($expected['flags'], array_column($result['quality']['flags'], 'code'), 'Cờ chất lượng');

        $top5 = collect($majors)->take(5)->map(fn (array $m) => [$m['id'], $m['name_de'], $m['code'], $m['c'], $m['label']])->all();
        $this->assertSame($expected['majors_top5'], $top5, '5 ngành đầu');
    }
}
