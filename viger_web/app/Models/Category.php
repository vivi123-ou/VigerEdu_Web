<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    /**
     * Các cột đa ngôn ngữ, lưu dạng JSON {"vi": "...", "en": "..."}.
     *
     * @var list<string>
     */
    public array $translatable = ['name', 'description'];

    // Bảng categories không có created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'menu_group',
        'parent_id',
        'translation_of_id',
        'url',
        'is_in_menu',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_in_menu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** Mục menu cha (dùng cho dropdown của Menu chính). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Các mục con hiển thị trong dropdown, theo thứ tự sắp xếp. */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->where('is_in_menu', true)
            ->orderBy('sort_order');
    }

    /** Chuyên mục tiếng Việt gốc (khi đây là chuyên mục tiếng Anh, vd. "German education overview" => "Tổng quan"). */
    public function translationOf(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'translation_of_id');
    }

    /** Chuyên mục tiếng Anh riêng của chuyên mục này (nếu đã tạo). */
    public function englishVersion(): HasOne
    {
        return $this->hasOne(Category::class, 'translation_of_id');
    }

    /** Chuyên mục dùng cho ngôn ngữ đang chọn: tiếng Anh => bản tiếng Anh (nếu có), còn lại => chính nó. */
    public function localized(?string $locale = null): Category
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en' && $this->translation_of_id === null && $this->englishVersion !== null) {
            return $this->englishVersion;
        }

        return $this;
    }

    /** Tên hiển thị trên menu / tiêu đề trang theo ngôn ngữ đang chọn. */
    public function displayName(): string
    {
        return (string) $this->localized()->name;
    }

    /**
     * Id của chuyên mục và các bản ngôn ngữ của nó (gốc + bản tiếng Anh).
     * Bài viết được lọc theo ngôn ngữ ở Post::writtenIn() nên mỗi chế độ chỉ hiện đúng bài của nó.
     *
     * @return list<int>
     */
    public function languageFamilyIds(): array
    {
        $original = $this->translation_of_id !== null ? $this->translationOf : $this;

        return array_values(array_unique(array_filter([
            $this->id,
            $original?->id,
            $original?->englishVersion?->id,
        ])));
    }

    public const GROUP_LABELS = [
        'giao-duc-tai-duc' => 'Giáo dục tại Đức',
        'tu-van' => 'Tư vấn',
        'blog' => 'Blog',
        'main' => 'Menu chính',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            // Không thuộc nhóm menu => không hiển thị, không có mục cha
            if (blank($category->menu_group)) {
                $category->is_in_menu = false;
            }
            if ($category->menu_group !== 'main') {
                $category->parent_id = null;
            }

            // Thứ tự trống => xếp cuối nhóm
            if ($category->sort_order === null) {
                $category->sort_order = (int) static::query()
                    ->where('menu_group', $category->menu_group)
                    ->whereKeyNot($category->getKey())
                    ->max('sort_order') + 1;
            }
        });
    }

    /** Nhóm menu => tiền tố URL của trang chuyên mục (xem routes/web.php). */
    public const GROUP_PREFIXES = [
        'giao-duc-tai-duc' => '/giao-duc-tai-duc',
        'tu-van' => '/tu-van',
        'blog' => '/blog',
    ];

    /** Đường dẫn khi hiển thị trên menu: URL tùy chỉnh, nếu trống thì trang chuyên mục. */
    public function menuUrl(): string
    {
        if (filled($this->url)) {
            // URL nội bộ (vd. /chuong-trinh-hoc) => tự đổi sang đường dẫn tiếng Anh ở chế độ tiếng Anh
            return localized_url($this->url);
        }

        return $this->pageUrl();
    }

    /** Trang danh sách bài viết của chuyên mục (bỏ qua URL tùy chỉnh), theo ngôn ngữ đang chọn. */
    public function pageUrl(): string
    {
        // Chuyên mục tiếng Anh dùng chung trang với chuyên mục gốc (trang tự đổi nội dung theo ngôn ngữ)
        if ($this->translation_of_id !== null && $this->translationOf !== null) {
            return $this->translationOf->pageUrl();
        }

        if (Route::has('category.'.$this->slug)) {
            return localized_url(route('category.'.$this->slug, absolute: false));
        }

        if (isset(self::GROUP_PREFIXES[(string) $this->menu_group])) {
            return localized_url(self::GROUP_PREFIXES[(string) $this->menu_group].'/'.$this->urlSlug());
        }

        return localized_url('/blog?category='.$this->id);
    }

    /**
     * Slug trên URL theo ngôn ngữ: tiếng Anh => slug từ tên tiếng Anh (vd. hoc-bong => scholarships),
     * chưa có tên tiếng Anh => slug gốc.
     */
    public function urlSlug(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en') {
            $englishSlug = Str::slug((string) $this->localized('en')->getTranslation('name', 'en', false));

            if ($englishSlug !== '') {
                return $englishSlug;
            }
        }

        return $this->slug;
    }

    /** Tìm chuyên mục của nhóm menu theo slug trên URL (slug gốc hoặc slug tiếng Anh). */
    public static function findByUrlSlug(string $group, string $urlSlug): ?Category
    {
        $categories = static::with('englishVersion')->where('menu_group', $group)->get();

        return $categories->firstWhere('slug', $urlSlug)
            ?? $categories->first(fn (Category $category) => $category->urlSlug('en') === $urlSlug);
    }

    public function scopeInMenu(Builder $query, ?string $group = null): Builder
    {
        $q = $query->where('is_in_menu', true)->orderBy('sort_order');

        if ($group !== null) {
            $q->where('menu_group', $group);
        }

        return $q;
    }
}
