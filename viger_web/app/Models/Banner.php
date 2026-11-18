<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory, HasTranslations;

    /**
     * Các cột đa ngôn ngữ, lưu dạng JSON {"vi": "...", "en": "..."}.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'eyebrow', 'desc', 'alt', 'button_primary_label', 'button_secondary_label'];

    protected $fillable = [
        'title',
        'eyebrow',
        'desc',
        'image',
        'alt',
        'button_primary_label',
        'button_primary_url',
        'button_secondary_label',
        'button_secondary_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Giữ thứ tự (sort_order) không trùng nhau:
     * - Tạo mới / đổi sang một số đã có banner dùng => chèn vào vị trí đó, các banner khác dịch chuyển.
     * - Xóa => các banner phía sau dồn lên.
     */
    protected static function booted(): void
    {
        static::creating(function (Banner $banner) {
            if ($banner->sort_order === null) {
                $banner->sort_order = (int) static::max('sort_order') + 1;

                return;
            }

            if (static::where('sort_order', $banner->sort_order)->exists()) {
                static::where('sort_order', '>=', $banner->sort_order)->increment('sort_order');
            }
        });

        static::updating(function (Banner $banner) {
            if (! $banner->isDirty('sort_order')) {
                return;
            }

            $old = (int) $banner->getOriginal('sort_order');
            $new = (int) $banner->sort_order;
            $others = static::whereKeyNot($banner->getKey());

            if (! (clone $others)->where('sort_order', $new)->exists()) {
                return;
            }

            if ($new < $old) {
                // Chuyển lên trước: các banner từ vị trí mới đến trước vị trí cũ lùi xuống 1
                $others->whereBetween('sort_order', [$new, $old - 1])->increment('sort_order');
            } else {
                // Chuyển xuống sau: các banner sau vị trí cũ đến vị trí mới dồn lên 1
                $others->whereBetween('sort_order', [$old + 1, $new])->decrement('sort_order');
            }
        });

        static::deleted(function (Banner $banner) {
            static::where('sort_order', '>', $banner->sort_order)->decrement('sort_order');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->image)) {
                    return '';
                }

                if (Str::startsWith($this->image, ['http://', 'https://', '//'])) {
                    return $this->image;
                }

                if (Storage::disk('public')->exists($this->image)) {
                    return Storage::disk('public')->url($this->image);
                }

                return asset($this->image);
            }
        );
    }
}
