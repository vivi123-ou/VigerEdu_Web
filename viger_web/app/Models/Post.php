<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    /**
     * Các cột đa ngôn ngữ, lưu dạng JSON {"vi": "...", "en": "..."}.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'excerpt', 'content'];

    // Bảng posts chỉ có created_at, không có updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'slug',
        'status',
        'featured_image',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * URL hiển thị của ảnh đại diện:
     * - URL tuyệt đối (http/https) hoặc bắt đầu bằng '/' -> giữ nguyên.
     * - File upload qua Filament (posts/...) -> /storage/posts/...
     * - Ảnh nội bộ khác -> asset(...)
     */
    protected function featuredImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->featured_image)) {
                    return null;
                }

                if (Str::startsWith($this->featured_image, ['http://', 'https://', '//', '/'])) {
                    return $this->featured_image;
                }

                if (Storage::disk('public')->exists($this->featured_image)) {
                    return Storage::disk('public')->url($this->featured_image);
                }

                return asset($this->featured_image);
            }
        );
    }

    /**
     * Chỉ lấy bài viết bằng ngôn ngữ $locale (có tiêu đề và nội dung ở ngôn ngữ đó).
     * Không fallback: trang tiếng Anh chỉ hiện bài tiếng Anh, tiếng Việt chỉ hiện bài tiếng Việt.
     */
    public function scopeWrittenIn(Builder $query, ?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();

        foreach (['title', 'content'] as $column) {
            $query->whereNotNull("{$column}->{$locale}")
                ->where("{$column}->{$locale}", '!=', '');
        }

        return $query;
    }

    /** Chỉ lấy bài đã xuất bản: Post::published()->... */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published);
    }
}
