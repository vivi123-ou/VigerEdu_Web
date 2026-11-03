<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
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

    /** Chỉ lấy bài đã xuất bản: Post::published()->... */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published);
    }
}
