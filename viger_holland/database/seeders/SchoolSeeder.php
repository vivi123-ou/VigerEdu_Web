<?php

namespace Database\Seeders;

use App\Modules\Schools\Models\Country;
use App\Modules\Schools\Models\Region;
use App\Modules\Schools\Models\School;
use Illuminate\Database\Seeder;

/**
 * Làm gì:   Nạp Đức, 16 bang và 390 trường (từ docs/du_lieu_16_bang_duc.xlsx, du_lieu_390_truong_DH_da_phien_dich.xlsx, đã chuyển
 *           sang JSON: bỏ cột thống đốc, bỏ dấu chú thích [8]; 20 trường ĐH Hành chính / Đặc thù ẩn mặc định).
 * Ai gọi:   DatabaseSeeder. Chạy lại không trùng, không ghi đè chỗ admin đã sửa (chỉ thêm dòng chưa có).
 * Dữ liệu:  countries, regions, schools.
 */
class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::firstOrCreate(['code' => 'DE'], ['name' => 'Đức', 'sort_order' => 1]);

        $regions = [];
        foreach ($this->json('regions_de') as $row) {
            $regions[$row['name']] = Region::firstOrCreate(['country_id' => $country->id, 'name' => $row['name']], $row + ['country_id' => $country->id])->id;
        }

        foreach ($this->json('schools_de') as $row) {
            $regionName = $row['region'];
            unset($row['region']);
            School::firstOrCreate(
                ['country_id' => $country->id, 'external_code' => $row['external_code']],
                $row + ['country_id' => $country->id, 'region_id' => $regions[$regionName] ?? null],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function json(string $name): array
    {
        return json_decode((string) file_get_contents(database_path("seeders/data/schools/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
    }
}
