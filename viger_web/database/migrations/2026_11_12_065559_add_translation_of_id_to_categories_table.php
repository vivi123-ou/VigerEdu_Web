<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chuyên mục tiếng Anh riêng (vd. "German education overview") liên kết với chuyên mục
 * tiếng Việt gốc (vd. "Tổng quan") qua translation_of_id.
 *
 * Bài viết nằm trong chuyên mục tiếng Anh được tạo trước khi có đa ngôn ngữ nên đang lưu
 * ở ô "vi" => chuyển sang ô "en" để chỉ hiện ở chế độ tiếng Anh.
 */
return new class extends Migration
{
    /** @var array<string, string> slug chuyên mục tiếng Anh => slug chuyên mục tiếng Việt gốc */
    private const KNOWN_PAIRS = [
        'german-education-overview' => 'tong-quan',
    ];

    private const POST_COLUMNS = ['title', 'excerpt', 'content'];

    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'translation_of_id')) {
            // SQLite dựng lại bảng khi thêm khóa ngoại => tắt ràng buộc để không kích hoạt nullOnDelete của posts
            Schema::withoutForeignKeyConstraints(function () {
                Schema::table('categories', function (Blueprint $table) {
                    $table->foreignId('translation_of_id')->nullable()->after('parent_id')
                        ->constrained('categories')->nullOnDelete();
                });
            });
        }

        foreach (self::KNOWN_PAIRS as $englishSlug => $originalSlug) {
            $originalId = DB::table('categories')->where('slug', $originalSlug)->value('id');

            if ($originalId !== null) {
                DB::table('categories')
                    ->where('slug', $englishSlug)
                    ->whereNull('translation_of_id')
                    ->update(['translation_of_id' => $originalId]);
            }
        }

        $this->moveEnglishCategoryContent(from: 'vi', to: 'en');
    }

    public function down(): void
    {
        $this->moveEnglishCategoryContent(from: 'en', to: 'vi');

        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('translation_of_id');
            });
        });
    }

    /** Chuyển tên chuyên mục tiếng Anh + bài viết của nó từ ô $from sang ô $to (nếu ô $to còn trống). */
    private function moveEnglishCategoryContent(string $from, string $to): void
    {
        $englishCategories = DB::table('categories')->whereNotNull('translation_of_id')->get(['id', 'name']);

        foreach ($englishCategories as $category) {
            $this->moveLocale('categories', $category->id, ['name'], $from, $to);

            DB::table('posts')->where('category_id', $category->id)->pluck('id')
                ->each(fn (int $postId) => $this->moveLocale('posts', $postId, self::POST_COLUMNS, $from, $to));
        }
    }

    /**
     * Chỉ chuyển khi cột tiêu đề/tên chưa có nội dung ở ô $to (không ghi đè bản dịch có sẵn).
     *
     * @param  list<string>  $columns
     */
    private function moveLocale(string $table, int $id, array $columns, string $from, string $to): void
    {
        $row = DB::table($table)->where('id', $id)->first($columns);
        $decoded = array_map(fn ($value) => json_decode((string) $value, true) ?: [], (array) $row);

        if (filled($decoded[$columns[0]][$to] ?? null) || blank($decoded[$columns[0]][$from] ?? null)) {
            return;
        }

        $changes = [];

        foreach ($columns as $column) {
            if ($row->{$column} === null || ! array_key_exists($from, $decoded[$column])) {
                continue;
            }

            $translations = $decoded[$column];
            $translations[$to] = $translations[$from];
            unset($translations[$from]);

            $changes[$column] = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($changes !== []) {
            DB::table($table)->where('id', $id)->update($changes);
        }
    }
};
