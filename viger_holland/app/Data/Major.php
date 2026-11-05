<?php

namespace App\Data;

/**
 * Một ngành / nghề trong danh mục. Khóa theo hợp đồng `GET /catalog/majors` của CRM;
 * fromArray() nhận cả khóa tiếng Việt của majors.json (id, loai, ten, linh_vuc, codes…).
 */
final readonly class Major
{
    public const TYPE_UNIVERSITY = 'university';

    public const TYPE_VOCATIONAL = 'vocational';

    /**
     * @param  list<string>  $hollandCodes
     */
    public function __construct(
        public string $code,
        public string $type,
        public string $nameVi,
        public string $field,
        public array $hollandCodes,
        public bool $isActive = true,
        public ?string $nameDe = null,
        public ?string $nameEn = null,
        public ?string $testasPrimary = null,
        public ?string $testasSecondary = null,
        public ?string $description = null,
        public ?string $subjects = null,
        public ?string $careers = null,
        public ?string $suitableFor = null,
        public ?string $schoolTypes = null,
        public ?string $languages = null,
        public ?string $keywords = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $pick = function (string ...$keys) use ($row): ?string {
            foreach ($keys as $key) {
                if (isset($row[$key]) && $row[$key] !== '' && ! is_array($row[$key])) {
                    return (string) $row[$key];
                }
            }

            return null;
        };

        return new self(
            code: (string) $pick('code', 'id'),
            type: self::normalizeType((string) $pick('type', 'loai')),
            nameVi: (string) $pick('name_vi', 'ten', 'name'),
            field: (string) $pick('field', 'linh_vuc'),
            hollandCodes: self::normalizeCodes($row['holland_codes'] ?? $row['codes'] ?? []),
            isActive: (bool) ($row['is_active'] ?? $row['active'] ?? true),
            nameDe: $pick('name_de', 'ten_de'),
            nameEn: $pick('name_en', 'ten_en'),
            testasPrimary: $pick('testas_primary', 'testas1'),
            testasSecondary: $pick('testas_secondary', 'testas2'),
            description: $pick('description', 'mo_ta'),
            subjects: $pick('subjects', 'mon_hoc'),
            careers: $pick('careers', 'nghe'),
            suitableFor: $pick('suitable_for', 'hop_voi'),
            schoolTypes: $pick('school_types', 'truong'),
            languages: $pick('languages', 'ngon_ngu'),
            keywords: $pick('keywords', 'tu_khoa'),
        );
    }

    public function isUniversity(): bool
    {
        return $this->type === self::TYPE_UNIVERSITY;
    }

    /**
     * Các nhóm TestAS của ngành (nhóm chính và nhóm phụ).
     *
     * @return list<string>
     */
    public function testasGroups(): array
    {
        return array_values(array_filter([$this->testasPrimary, $this->testasSecondary]));
    }

    private static function normalizeType(string $type): string
    {
        return match (mb_strtolower(trim($type))) {
            'vocational', 'học nghề', 'hoc nghe' => self::TYPE_VOCATIONAL,
            default => self::TYPE_UNIVERSITY,
        };
    }

    /**
     * Mã Holland hợp lệ: đúng 3 chữ trong R I A S E C, viết hoa.
     *
     * @return list<string>
     */
    private static function normalizeCodes(mixed $codes): array
    {
        if (is_string($codes)) {
            $codes = preg_split('/[\s,;|]+/', $codes) ?: [];
        }

        $normalized = array_map(fn ($code) => strtoupper(trim((string) $code)), (array) $codes);

        return array_values(array_filter($normalized, fn (string $code) => preg_match('/^[RIASEC]{3}$/', $code) === 1));
    }
}
