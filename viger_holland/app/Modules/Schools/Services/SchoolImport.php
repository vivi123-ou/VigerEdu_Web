<?php

namespace App\Modules\Schools\Services;

use App\Modules\Schools\Models\Country;
use App\Modules\Schools\Models\Region;
use App\Modules\Schools\Models\School;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Làm gì:   Nhập danh sách trường từ Excel / CSV của bất kỳ nước nào, có bước ghép cột:
 *           headers() đọc dòng tiêu đề; guess() tự đoán cột nào ứng với ô nào (theo tên cột, ví dụ "ten_truong", "Name");
 *           import() thêm / cập nhật trường theo mã (hoặc theo tên khi file không có mã), tự thêm vùng chưa có,
 *           cột không ghép lưu vào extra. Không xóa trường nào.
 * Ai gọi:   trang "Trường & vùng" (nút "Nhập từ Excel"), test.
 * Dữ liệu:  countries, regions, schools.
 */
class SchoolImport
{
    /** Tên cột hay gặp (đã bỏ dấu, chữ thường) cho từng ô. */
    private const ALIASES = [
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

    /**
     * @return list<string>
     */
    public function headers(string $path): array
    {
        foreach ($this->rows($path) as $row) {
            return array_values(array_map(fn ($v) => trim((string) $v), $row));
        }

        return [];
    }

    /**
     * Ô của trường => tên cột trong file (đoán theo tên cột).
     *
     * @param  list<string>  $headers
     * @return array<string, string|null>
     */
    public function guess(array $headers): array
    {
        $normalized = collect($headers)->mapWithKeys(fn (string $h) => [$h => Str::of(Str::ascii($h))->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString()]);

        return collect(self::ALIASES)->map(fn (array $aliases) => $normalized->search(fn (string $n) => in_array($n, $aliases, true)) ?: null)->all();
    }

    /**
     * @param  array<string, string|null>  $mapping  ô của trường => tên cột
     * @param  list<string>  $hiddenTypes  loại hình mặc định ẩn
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(string $path, Country $country, array $mapping, array $hiddenTypes = []): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $headers = null;
        $regions = $country->regions()->pluck('id', 'name')->all();

        foreach ($this->rows($path) as $row) {
            if ($headers === null) {
                $headers = array_map(fn ($v) => trim((string) $v), $row);

                continue;
            }
            $record = array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null));
            $value = fn (string $field) => filled($mapping[$field] ?? null) ? $this->clean($record[$mapping[$field]] ?? null) : null;

            $name = $value('name');
            if ($name === null) {
                $stats['skipped']++;

                continue;
            }

            $data = ['name' => $name];
            foreach (array_keys(School::IMPORTABLE) as $field) {
                if (in_array($field, ['name', 'region'], true) || ($v = $value($field)) === null) {
                    continue;
                }
                $data[$field] = match ($field) {
                    'students', 'founded_year' => (int) preg_replace('/\D/', '', $v) ?: null,
                    'phd_rights' => in_array(mb_strtolower($v), ['có', 'co', 'yes', 'ja', '1', 'true'], true),
                    default => $v,
                };
            }
            if (($regionName = $value('region')) !== null) {
                $regions[$regionName] ??= Region::query()->create(['country_id' => $country->id, 'name' => $regionName])->id;
                $data['region_id'] = $regions[$regionName];
            }
            $used = array_filter($mapping);
            $data['extra'] = collect($record)->except($used)->reject(fn ($v, $k) => $k === '')->map(fn ($v) => $this->clean($v))->filter(fn ($v) => $v !== null)->all() ?: null;

            $school = School::query()->where('country_id', $country->id)
                ->when(isset($data['external_code']), fn ($q) => $q->where('external_code', $data['external_code']), fn ($q) => $q->where('name', $name))
                ->first();

            if ($school === null) {
                School::query()->create($data + ['country_id' => $country->id, 'is_hidden' => in_array($data['type'] ?? null, $hiddenTypes, true)]);
                $stats['created']++;
            } else {
                $school->update($data);
                $stats['updated']++;
            }
        }

        return $stats;
    }

    private function clean(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return \Generator<int, list<mixed>>
     */
    private function rows(string $path): \Generator
    {
        $reader = str_ends_with(strtolower($path), '.csv') ? new CsvReader : new XlsxReader;
        $reader->open($path);
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    yield $row->toArray();
                }
                break;
            }
        } finally {
            $reader->close();
        }
    }
}
