<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

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
