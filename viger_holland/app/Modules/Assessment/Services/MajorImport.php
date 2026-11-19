<?php

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\Major;
use App\Support\SpreadsheetImport;

/**
 * Làm gì:   Nhập / cập nhật ngành học từ Excel (khung chung SpreadsheetImport, có bước ghép cột).
 *           Tìm ngành có sẵn theo mã, rồi tên Đức, rồi tên Việt; chưa có thì thêm với mã MAJ… kế tiếp.
 *           Mã Holland: nhiều mã cách nhau dấu phẩy, mỗi mã 3 chữ khác nhau trong RIASEC (sai thì bỏ mã đó, vẫn nhập dòng).
 *           "Đã kiểm tra" chỉ nhận khi dòng có link nguồn chính thức. Không xóa ngành nào.
 * Ai gọi:   trang "Ngành học & nghề gợi ý" (nút "Nhập từ Excel"), test.
 * Dữ liệu:  majors.
 */
class MajorImport extends SpreadsheetImport
{
    public const FIELDS = [
        'code' => 'Mã ngành (để trống khi thêm mới)', 'name_vi' => 'Tên tiếng Việt', 'name_de' => 'Tên tiếng Đức', 'name_en' => 'Tên tiếng Anh',
        'holland_codes' => 'Mã Holland (ví dụ ISC, IRC)', 'routes' => 'Hệ đào tạo (Uni, HAW…)', 'source_url' => 'Link nguồn chính thức',
        'is_verified' => 'Đã kiểm tra (có / không)', 'is_active' => 'Đang dùng (có / không)',
    ];

    public function fields(): array
    {
        return self::FIELDS;
    }

    public function required(): array
    {
        return ['name_vi'];
    }

    public function example(): array
    {
        return ['', 'Khoa học máy tính', 'Informatik', 'Computer Science', 'IRC, ICR', 'Uni, HAW',
            'https://www.hochschulkompass.de/studium/studiengangsuche.html', 'có', 'có'];
    }

    protected function aliases(): array
    {
        return [
            'code' => ['ma', 'ma_nganh', 'code'],
            'name_vi' => ['ten', 'ten_nganh', 'nganh', 'ten_viet', 'name_vi', 'ten_tieng_viet'],
            'name_de' => ['ten_duc', 'name_de', 'studiengang', 'ten_tieng_duc'],
            'name_en' => ['ten_anh', 'name_en', 'english', 'ten_tieng_anh'],
            'holland_codes' => ['ma_holland', 'holland', 'riasec', 'holland_codes'],
            'routes' => ['he', 'he_dao_tao', 'routes'],
            'source_url' => ['link', 'nguon', 'link_nguon', 'source', 'source_url'],
            'is_verified' => ['da_kiem_tra', 'kiem_tra', 'verified'],
            'is_active' => ['dang_dung', 'active', 'is_active'],
        ];
    }

    protected function importRow(array $values, array $extra, array $context): array
    {
        if ($values['name_vi'] === null && $values['name_de'] === null) {
            return ['skipped', 'không có tên ngành'];
        }

        $major = null;
        foreach (['code', 'name_de', 'name_vi'] as $key) {
            if ($major === null && $values[$key] !== null) {
                $major = Major::withTrashed()->where($key, $values[$key])->first();
            }
        }

        $source = $values['source_url'];
        if ($source !== null && ! preg_match('#^https?://#i', $source)) {
            $source = 'https://'.$source;
        }
        $codes = collect(preg_split('/[\s,;\/]+/', strtoupper((string) $values['holland_codes'])) ?: [])
            ->filter(fn (string $c) => preg_match('/^(?!.*(.).*\1)[RIASEC]{3}$/', $c) === 1)->unique()->take(3)->values()->all();

        $data = array_filter([
            'name_vi' => $values['name_vi'], 'name_de' => $values['name_de'], 'name_en' => $values['name_en'],
            'routes' => $values['routes'], 'source_url' => $source,
        ], fn ($v) => $v !== null);
        if ($codes !== []) {
            $data['holland_codes'] = $codes;
            $data['code_source'] = 'Nhập Excel';
        }
        if ($values['is_verified'] !== null) {
            $data['is_verified'] = self::truthy($values['is_verified']) && filled($source ?? $major?->source_url);
        }
        if ($values['is_active'] !== null) {
            $data['is_active'] = self::truthy($values['is_active']);
        }

        if ($major === null) {
            Major::query()->create($data + [
                'name_vi' => $values['name_vi'] ?? $values['name_de'],
                'code' => self::nextCode(),
                'sort_order' => (int) Major::withTrashed()->max('sort_order') + 1,
            ]);

            return ['created'];
        }
        $major->update($data);

        return ['updated'];
    }

    /**
     * Mã kế tiếp, ví dụ MAJ047.
     */
    public static function nextCode(): string
    {
        $max = Major::withTrashed()->where('code', 'like', 'MAJ%')->pluck('code')->map(fn (string $code) => (int) substr($code, 3))->max() ?? 0;

        return 'MAJ'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
