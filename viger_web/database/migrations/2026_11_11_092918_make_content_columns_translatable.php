<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chuyển các cột nội dung sang dạng đa ngôn ngữ cho spatie/laravel-translatable:
 * "Tiêu đề" => {"vi": "Tiêu đề"}. Dữ liệu cũ được giữ nguyên làm bản tiếng Việt.
 */
return new class extends Migration
{
    private const PRIMARY_LOCALE = 'vi';

    /**
     * Cột đa ngôn ngữ theo bảng: [cột => [kiểu cũ, độ dài cũ, nullable]].
     *
     * @var array<string, array<string, array{0: string, 1: int|null, 2: bool}>>
     */
    private const COLUMNS = [
        'posts' => [
            'title' => ['string', 500, false],
            'excerpt' => ['string', 1000, true],
            'content' => ['longText', null, false],
        ],
        'categories' => [
            'name' => ['string', 255, false],
            'description' => ['text', null, true],
        ],
        'pages' => [
            'title' => ['string', 255, false],
            'subtitle' => ['string', 255, true],
            'content' => ['longText', null, true],
            'sections' => ['json', null, true],
            'meta_description' => ['string', 500, true],
        ],
        'banners' => [
            'title' => ['string', 255, false],
            'eyebrow' => ['string', 255, true],
            'desc' => ['text', null, true],
            'alt' => ['string', 255, true],
            'button_primary_label' => ['string', 255, true],
            'button_secondary_label' => ['string', 255, true],
        ],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            // Cột string bị giới hạn độ dài => đổi sang text để đủ chỗ cho JSON nhiều ngôn ngữ
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => [$type, , $nullable]) {
                    if ($type === 'string') {
                        $blueprint->text($column)->nullable($nullable)->change();
                    }
                }
            });

            $this->convertRows($table, array_keys($columns), function (mixed $value, string $column) use ($table) {
                if ($this->isTranslationJson($value)) {
                    return $value;
                }

                $isJsonColumn = self::COLUMNS[$table][$column][0] === 'json';

                return json_encode(
                    [self::PRIMARY_LOCALE => $isJsonColumn ? json_decode($value, true) : $value],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->convertRows($table, array_keys($columns), function (mixed $value, string $column) use ($table) {
                if (! $this->isTranslationJson($value)) {
                    return $value;
                }

                $translations = json_decode($value, true);
                $primary = $translations[self::PRIMARY_LOCALE] ?? reset($translations);

                if (self::COLUMNS[$table][$column][0] === 'json') {
                    return json_encode($primary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                return $primary;
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => [$type, $length, $nullable]) {
                    if ($type === 'string') {
                        $blueprint->string($column, $length)->nullable($nullable)->change();
                    }
                }
            });
        }
    }

    /**
     * @param  list<string>  $columns
     * @param  callable(mixed, string): mixed  $convert
     */
    private function convertRows(string $table, array $columns, callable $convert): void
    {
        DB::table($table)->select(['id', ...$columns])->lazyById()->each(
            function (object $row) use ($table, $columns, $convert) {
                $changes = [];

                foreach ($columns as $column) {
                    if ($row->{$column} === null) {
                        continue;
                    }

                    $converted = $convert($row->{$column}, $column);

                    if ($converted !== $row->{$column}) {
                        $changes[$column] = $converted;
                    }
                }

                if ($changes !== []) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            }
        );
    }

    /** Giá trị đã ở dạng {"vi": ..., "en": ...} (chạy lại migration không bọc 2 lần). */
    private function isTranslationJson(mixed $value): bool
    {
        if (! is_string($value) || ! str_starts_with(ltrim($value), '{')) {
            return false;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            && $decoded !== []
            && array_diff(array_keys($decoded), config('app.supported_locales')) === [];
    }
};
