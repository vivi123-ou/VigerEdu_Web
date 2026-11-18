<?php

namespace Database\Seeders;

use App\Modules\Assessment\Models\Environment;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\MajorGroup;
use App\Modules\Assessment\Models\Occupation;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\Subject;
use App\Modules\Assessment\Models\ThirdLetter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Làm gì:   Nạp danh mục Career Map từ database/seeders/data/holland_v3: 6 nhóm, 15 cặp, 6 hướng chuyên sâu,
 *           cụm ngành (kèm tỉ trọng môn), môn học, 46 ngành, nghề, môi trường, liên kết ngành – nghề.
 * Ai gọi:   DatabaseSeeder. Chạy lại không trùng; bản ghi đã có thì không ghi đè (admin có thể đã sửa).
 * Dữ liệu:  các bảng danh mục, không có dữ liệu khách.
 */
class CatalogSeeder extends Seeder
{
    /** Môn học dùng cho chỉ số nền tảng học tập: mã => tên. */
    private const SUBJECTS = [
        'toan' => 'Toán', 'van' => 'Ngữ văn', 'anh' => 'Tiếng Anh', 'ly' => 'Vật lý', 'hoa' => 'Hóa học',
        'sinh' => 'Sinh học', 'tin' => 'Tin học', 'su' => 'Lịch sử', 'dia' => 'Địa lý',
    ];

    /**
     * Tỉ trọng môn mặc định theo cụm ngành (mã cụm trong majors.json => [môn => tỉ trọng, điểm TB]).
     * Đề xuất ban đầu dựa trên cột "môn nền" của file Thầy; chờ Thầy duyệt, sửa được trên admin.
     *
     * @var array<int, array{0: array<string, float>, 1: float}>
     */
    private const SUBJECT_WEIGHTS = [
        1 => [['toan' => 0.35, 'ly' => 0.35], 0.30],
        2 => [['toan' => 0.40, 'tin' => 0.30], 0.30],
        3 => [['toan' => 0.35, 'anh' => 0.30], 0.35],
        4 => [['toan' => 0.35, 'anh' => 0.25], 0.40],
        5 => [['hoa' => 0.25, 'sinh' => 0.25, 'toan' => 0.20], 0.30],
        6 => [['van' => 0.35, 'anh' => 0.25], 0.40],
        7 => [['sinh' => 0.35, 'hoa' => 0.25], 0.40],
    ];

    public function run(): void
    {
        foreach ($this->json('riasec_types') as $row) {
            $type = RiasecType::firstOrCreate(['code' => $row['code']], [
                'name' => $row['name'], 'label' => $row['label'] ?? null, 'hex_index' => $row['hex_index'],
                'description' => $row['description'] ?? null,
            ]);
            $this->fillIfEmpty($type, 'description', $row['description'] ?? null);
        }

        foreach ($this->json('third_letters') as $row) {
            $letter = ThirdLetter::firstOrCreate(['code' => $row['code']], [
                'specialty_vi' => $row['specialty_vi'] ?? null, 'specialty_de' => $row['specialty_de'] ?? null,
                'specialty_en' => $row['specialty_en'] ?? null, 'skills' => $row['skills'] ?? null, 'description' => $row['description'] ?? null,
            ]);
            $this->fillIfEmpty($letter, 'specialty_vi', $row['specialty_vi'] ?? null);
            $this->fillIfEmpty($letter, 'description', $row['description'] ?? null);
        }

        $sort = 0;
        foreach (self::SUBJECTS as $code => $name) {
            Subject::firstOrCreate(['code' => $code], ['name' => $name, 'sort_order' => ++$sort]);
        }

        $majors = $this->json('majors');
        $groups = [];
        foreach ($majors as $row) {
            $groupId = (int) $row['group_id'];
            [$weights, $gpa] = self::SUBJECT_WEIGHTS[$groupId] ?? [[], 0.4];
            $groups[$groupId] ??= MajorGroup::firstOrCreate(['name' => $row['group']], [
                'subject_weights' => $weights, 'gpa_weight' => $gpa, 'sort_order' => $groupId,
            ]);
        }

        foreach ($majors as $index => $row) {
            Major::withTrashed()->firstOrCreate(['code' => $row['id']], [
                'name_vi' => $row['name_vi'], 'name_de' => $row['name_de'] ?? null, 'name_en' => $row['name_en'] ?? null,
                'major_group_id' => $groups[(int) $row['group_id']]->id,
                'holland_codes' => array_values($row['codes'] ?? []) ?: null,
                'code_source' => $row['code_source'] ?? null, 'routes' => $row['routes'] ?? null,
                'validation_status' => $row['status'] ?? null, 'is_active' => (bool) ($row['active'] ?? true), 'sort_order' => $index + 1,
            ]);
        }
        $majorIds = Major::withTrashed()->pluck('id', 'code');

        foreach ($this->json('riasec_pairs') as $row) {
            $pair = RiasecPair::firstOrCreate(['pair' => $row['pair']], [
                'name' => $row['name'], 'hex_distance' => $row['hex_distance'], 'relation' => $row['relation'],
                'occupations' => $row['occupations'] ?? [], 'foundation' => $row['foundation'] ?? null,
                'description' => $row['description'] ?? null,
            ]);
            $this->fillIfEmpty($pair, 'description', $row['description'] ?? null);

            if ($pair->wasRecentlyCreated) {
                foreach (array_values($row['majors'] ?? []) as $i => $code) {
                    if (isset($majorIds[$code])) {
                        $pair->majors()->attach($majorIds[$code], ['sort_order' => $i + 1]);
                    }
                }
            }
        }

        foreach ($this->json('environments') as $row) {
            Environment::firstOrCreate(['code' => $row['code']], [
                'name' => $row['name'], 'activities' => $row['activities'] ?? null, 'interest_hint' => $row['hint'] ?? null,
            ]);
        }
        $environmentIds = Environment::query()->pluck('id', 'code');

        foreach ($this->json('occupations') as $row) {
            $occupation = Occupation::withTrashed()->firstOrCreate(['code' => $row['id']], [
                'name_en' => $row['name_en'] ?? null, 'name_de' => $row['name_de'] ?? null, 'sector' => $row['field'] ?? null,
                'tasks' => $row['tasks'] ?? null, 'skills' => $row['skills'] ?? null, 'qualification' => $row['level'] ?? null,
                'regulation' => $row['regulation'] ?? null, 'riasec_hypothesis' => $row['riasec'] ?? null,
                'source' => $row['source'] ?? null, 'validation_status' => $row['status'] ?? null,
            ]);

            if ($occupation->wasRecentlyCreated) {
                $codes = array_filter(array_map('trim', explode(',', (string) ($row['environments'] ?? ''))));
                $occupation->environments()->attach(collect($codes)->map(fn ($code) => $environmentIds[$code] ?? null)->filter()->all());
            }
        }
        $occupationIds = Occupation::withTrashed()->pluck('id', 'code');

        foreach ($this->json('major_occupation') as $row) {
            if (DB::table('major_occupation')->where('code', $row['id'])->exists()
                || ! isset($majorIds[$row['major_id']], $occupationIds[$row['occupation_id']])) {
                continue;
            }

            DB::table('major_occupation')->insert([
                'code' => $row['id'], 'major_id' => $majorIds[$row['major_id']], 'occupation_id' => $occupationIds[$row['occupation_id']],
                'link_type' => $row['type'] ?? null, 'routes' => $row['routes'] ?? null, 'source' => $row['source'] ?? null,
                'validation_status' => $row['status'] ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * Database đã có dòng này nhưng ô còn trống (ví dụ đoạn giải thích thêm sau) thì điền; ô đã có chữ thì giữ nguyên (admin có thể đã sửa).
     */
    private function fillIfEmpty(Model $model, string $column, ?string $value): void
    {
        if (blank($model->{$column}) && filled($value)) {
            $model->update([$column => $value]);
        }
    }

    private function json(string $name): array
    {
        $path = config('holland.seed_path').'/'.$name.'.json';
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("Không đọc được dữ liệu {$path}");
        }

        return $data;
    }
}
