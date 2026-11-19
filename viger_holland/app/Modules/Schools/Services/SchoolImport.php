<?php

namespace App\Modules\Schools\Services;

use App\Modules\Schools\Models\Country;
use App\Modules\Schools\Models\Region;
use App\Modules\Schools\Models\School;
use App\Support\SpreadsheetImport;

/**
 * Làm gì:   Nhập danh sách trường từ Excel / CSV của bất kỳ nước nào (khung chung SpreadsheetImport, có bước ghép cột):
 *           thêm / cập nhật trường theo mã (hoặc theo tên khi file không có mã), tự thêm vùng chưa có,
 *           cột không ghép lưu vào extra. Không xóa trường nào.
 *           Ngữ cảnh: 'country' (Country), 'hidden_types' (loại hình mặc định ẩn).
 * Ai gọi:   trang "Trường & vùng" (nút "Nhập từ Excel"), test.
 * Dữ liệu:  countries, regions, schools.
 */
class SchoolImport extends SpreadsheetImport
{
    /** @var array<string, int> tên vùng => id, theo quốc gia đang nhập */
    private array $regions = [];

    public function fields(): array
    {
        return School::IMPORTABLE;
    }

    public function required(): array
    {
        return ['name'];
    }

    public function example(): array
    {
        return ['TUM', 'Technische Universität München', 'TUM', 'TU München', 'ĐH Kỹ thuật', 'Công lập', 'Bayern', '50000', '1868', 'có',
            'München', 'Arcisstraße 21', '+49 89 289 01', 'https://www.tum.de'];
    }

    protected function aliases(): array
    {
        return [
            'external_code' => ['ma_truong', 'ma', 'code', 'id', 'school_code'],
            'name' => ['ten_truong', 'ten', 'name', 'school', 'school_name', 'hochschule'],
            'short_name' => ['ten_viet_tat', 'viet_tat', 'short_name', 'abbreviation'],
            'display_name' => ['ten_thu_tu', 'ten_thuong_dung', 'display_name'],
            'type' => ['loai_hinh', 'loai', 'type', 'hochschultyp'],
            'ownership' => ['hinh_thuc', 'ownership', 'traegerschaft'],
            'region' => ['ten_bang', 'bang', 'vung', 'tinh', 'region', 'state', 'bundesland'],
            'students' => ['so_luong_sinh_vien', 'sinh_vien', 'students'],
            'founded_year' => ['nam_thanh_lap', 'founded', 'founded_year'],
            'phd_rights' => ['quyen_tien_si', 'phd', 'promotionsrecht'],
            'city' => ['thanh_pho', 'city', 'ort'],
            'address' => ['dia_chi', 'duong', 'address', 'strasse'],
            'phone' => ['so_dien_thoai', 'dien_thoai', 'phone', 'telefon'],
            'website' => ['lien_ket', 'website', 'web', 'url', 'homepage'],
        ];
    }

    public function import(string $path, array $mapping, array $context = []): array
    {
        /** @var Country $country */
        $country = $context['country'];
        $this->regions = $country->regions()->pluck('id', 'name')->all();

        return parent::import($path, $mapping, $context);
    }

    protected function importRow(array $values, array $extra, array $context): array
    {
        /** @var Country $country */
        $country = $context['country'];
        $name = $values['name'];
        if ($name === null) {
            return ['skipped', 'không có tên trường'];
        }

        $data = ['name' => $name];
        foreach ($values as $field => $v) {
            if (in_array($field, ['name', 'region'], true) || $v === null) {
                continue;
            }
            $data[$field] = match ($field) {
                'students', 'founded_year' => (int) preg_replace('/\D/', '', $v) ?: null,
                'phd_rights' => self::truthy($v),
                default => $v,
            };
        }
        if (($regionName = $values['region']) !== null) {
            $this->regions[$regionName] ??= Region::query()->create(['country_id' => $country->id, 'name' => $regionName])->id;
            $data['region_id'] = $this->regions[$regionName];
        }
        $data['extra'] = $extra ?: null;

        $school = School::query()->where('country_id', $country->id)
            ->when(isset($data['external_code']), fn ($q) => $q->where('external_code', $data['external_code']), fn ($q) => $q->where('name', $name))
            ->first();

        if ($school === null) {
            School::query()->create($data + ['country_id' => $country->id, 'is_hidden' => in_array($data['type'] ?? null, $context['hidden_types'] ?? [], true)]);

            return ['created'];
        }
        $school->update($data);

        return ['updated'];
    }
}
