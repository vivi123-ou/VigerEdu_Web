<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bổ sung tên tiếng Anh cho các chuyên mục có sẵn trên menu (theo slug).
 * Chỉ điền khi chuyên mục chưa có bản tiếng Anh => không ghi đè tên đã nhập trong admin.
 */
return new class extends Migration
{
    /** @var array<string, string> slug => tên tiếng Anh */
    private const ENGLISH_NAMES = [
        'uncategorized' => 'Uncategorized',
        'tong-quan' => 'Overview',
        'he-thong-giao-duc' => 'Education System',
        'nghien-cuu-va-ung-dung' => 'Research & Applied Sciences',
        'chi-phi-va-co-hoi' => 'Costs & Opportunities',
        'co-hoi-nghe-nghiep' => 'Career Opportunities',
        'du-hoc-dai-hoc' => 'Bachelor Studies',
        'du-hoc-cao-hoc' => 'Master Studies',
        'quy-trinh-cap-visa' => 'Visa Application Process',
        'chuong-trinh-hoc' => 'Study Programs',
        'hieu-ve-duc' => 'Understanding Germany',
        'cuoc-song-o-duc' => 'Life in Germany',
    ];

    public function up(): void
    {
        $this->eachCategory(function (array $names, string $slug): ?array {
            if (filled($names['en'] ?? null)) {
                return null;
            }

            return [...$names, 'en' => self::ENGLISH_NAMES[$slug]];
        });
    }

    public function down(): void
    {
        $this->eachCategory(function (array $names, string $slug): ?array {
            if (($names['en'] ?? null) !== self::ENGLISH_NAMES[$slug]) {
                return null;
            }

            unset($names['en']);

            return $names;
        });
    }

    /**
     * @param  callable(array<string, string|null>, string): (array<string, string|null>|null)  $change
     */
    private function eachCategory(callable $change): void
    {
        $categories = DB::table('categories')
            ->whereIn('slug', array_keys(self::ENGLISH_NAMES))
            ->get(['id', 'slug', 'name']);

        foreach ($categories as $category) {
            $names = json_decode((string) $category->name, true);

            if (! is_array($names)) {
                continue;
            }

            $newNames = $change($names, $category->slug);

            if ($newNames !== null) {
                DB::table('categories')->where('id', $category->id)->update([
                    'name' => json_encode($newNames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        }
    }
};
