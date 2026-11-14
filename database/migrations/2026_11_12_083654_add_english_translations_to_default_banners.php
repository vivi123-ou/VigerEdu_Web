<?php

use App\Support\DefaultBanners;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bổ sung bản tiếng Anh cho các banner mặc định (nhận diện theo ảnh).
 * Banner đã có tiêu đề tiếng Anh thì bỏ qua => không ghi đè nội dung nhập trong admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->eachDefaultBanner(function (array $translations, array $slide): ?array {
            if (filled($translations['title']['en'] ?? null)) {
                return null;
            }

            foreach (DefaultBanners::TEXT_FIELDS as $field) {
                if (filled($slide['en'][$field])) {
                    $translations[$field]['en'] = $slide['en'][$field];
                }
            }

            return $translations;
        });
    }

    public function down(): void
    {
        $this->eachDefaultBanner(function (array $translations, array $slide): ?array {
            if (($translations['title']['en'] ?? null) !== $slide['en']['title']) {
                return null;
            }

            foreach (DefaultBanners::TEXT_FIELDS as $field) {
                unset($translations[$field]['en']);
            }

            return $translations;
        });
    }

    /**
     * @param  callable(array<string, array<string, string|null>>, array<string, mixed>): (array<string, array<string, string|null>>|null)  $change
     */
    private function eachDefaultBanner(callable $change): void
    {
        foreach (DefaultBanners::SLIDES as $slide) {
            $banners = DB::table('banners')->where('image', $slide['image'])->get(['id', ...DefaultBanners::TEXT_FIELDS]);

            foreach ($banners as $banner) {
                $translations = [];

                foreach (DefaultBanners::TEXT_FIELDS as $field) {
                    $decoded = json_decode((string) $banner->{$field}, true);
                    $translations[$field] = is_array($decoded) ? $decoded : [];
                }

                $newTranslations = $change($translations, $slide);

                if ($newTranslations === null) {
                    continue;
                }

                DB::table('banners')->where('id', $banner->id)->update(array_map(
                    fn (array $values) => $values === [] ? null : json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $newTranslations,
                ));
            }
        }
    }
};
