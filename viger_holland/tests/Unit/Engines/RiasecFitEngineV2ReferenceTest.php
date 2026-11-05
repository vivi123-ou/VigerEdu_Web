<?php

namespace Tests\Unit\Engines;

use App\Catalogs\JsonMajorCatalog;
use App\Engines\RiasecFitEngineV2;
use PHPUnit\Framework\TestCase;

/**
 * Đối chiếu engine với file tham chiếu holland_engine_reference.php trên nhiều hồ sơ ngẫu nhiên
 * (ngoài 5 học sinh mẫu), để chắc chắn port đúng công thức.
 */
class RiasecFitEngineV2ReferenceTest extends TestCase
{
    private const DATA_DIR = __DIR__.'/../../../database/seeders/data/holland';

    public function test_matches_reference_implementation_on_random_profiles(): void
    {
        // File tham chiếu có đoạn "chạy thử" đọc $argv khi chạy bằng dòng lệnh; đặt sẵn để nó không chạy.
        $argv = ['phpunit'];
        require_once self::DATA_DIR.'/holland_engine_reference.php';

        $rawMajors = json_decode((string) file_get_contents(self::DATA_DIR.'/majors.json'), true);
        $majors = (new JsonMajorCatalog(self::DATA_DIR.'/majors.json'))->all();
        $engine = new RiasecFitEngineV2;
        $cfg = [
            'trong_so_khop_ma' => 0.6, 'trong_so_khop_diem' => 0.4,
            'nguong' => ['rat_phu_hop' => 0.8, 'phu_hop' => 0.65, 'co_the_can_nhac' => 0.5],
            'so_nganh_dai_hoc' => 75, 'so_nghe' => 75,
        ];

        mt_srand(20261105);

        for ($run = 0; $run < 300; $run++) {
            // Thang Đúng/Không 9 câu/nhóm, xen kẽ thang 5 mức để thử nhiều kiểu điểm
            $p = [];
            foreach (RiasecFitEngineV2::GROUPS as $group) {
                $p[$group] = $run % 2 === 0 ? mt_rand(0, 9) / 9 : mt_rand(0, 36) / 36;
            }

            $expectedCode = holland_ma($p);
            $expected = holland_de_xuat($p, $expectedCode['ma'], $rawMajors, $cfg);
            $actual = $engine->scorePercent($p, $majors);

            $this->assertSame($expectedCode['ma'], $actual->code, "Lần {$run}: mã");
            $this->assertEqualsWithDelta($expectedCode['di3'], $actual->di3, 1e-9, "Lần {$run}: di3");
            $this->assertSame($expectedCode['bang_diem'], $actual->hasTie, "Lần {$run}: has_tie");

            $byCode = collect($actual->ranking)->keyBy('majorCode');
            foreach (array_merge($expected['Đại học'], $expected['Học nghề']) as $row) {
                $match = $byCode[$row['id']];
                $this->assertSame($row['ma_nganh'], $match->codeUsed, "Lần {$run}: mã dùng của {$row['id']}");
                $this->assertEqualsWithDelta($row['fit'], $match->fit, 1e-9, "Lần {$run}: FIT của {$row['id']}");
                $this->assertSame($row['nhan'], $match->label, "Lần {$run}: nhãn của {$row['id']}");
            }

            // Thứ tự FIT giảm dần giống bản tham chiếu (ngành bằng FIT có thể đổi chỗ theo luật phụ khớp mã → mã ngành)
            $this->assertEqualsWithDelta(
                array_column($expected['Đại học'], 'fit'),
                array_map(fn ($match) => $match->fit, array_values(array_filter($actual->ranking, fn ($m) => $m->type === 'university'))),
                1e-9,
                "Lần {$run}: thứ tự FIT đại học",
            );

            $this->assertEqualsWithDelta(reset($expected['linh_vuc'])['fit'], $actual->fields[0]['score'], 1e-9, "Lần {$run}: điểm lĩnh vực mạnh nhất");
        }
    }
}
