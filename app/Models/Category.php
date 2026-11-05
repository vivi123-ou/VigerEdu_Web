<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Category extends Model
{
    // Bảng categories không có created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'menu_group',
        'parent_id',
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
            return Str::startsWith($this->url, ['http://', 'https://', '//', '#', 'mailto:', 'tel:'])
                ? $this->url
                : url($this->url);
        }

        return $this->pageUrl();
    }

    /** Trang danh sách bài viết của chuyên mục (bỏ qua URL tùy chỉnh). */
    public function pageUrl(): string
    {
        if (Route::has('category.'.$this->slug)) {
            return route('category.'.$this->slug);
        }

        if (isset(self::GROUP_PREFIXES[(string) $this->menu_group])) {
            return url(self::GROUP_PREFIXES[(string) $this->menu_group].'/'.$this->slug);
        }

        return url('/blog?category='.$this->id);
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
