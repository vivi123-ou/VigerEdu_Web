<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'banner_image',
        'content',
        'sections',
        'meta_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    protected function bannerImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->banner_image)) {
                    return '';
                }

                if (Str::startsWith($this->banner_image, ['http://', 'https://', '//'])) {
                    return $this->banner_image;
                }

                if (Storage::disk('public')->exists($this->banner_image)) {
                    return Storage::disk('public')->url($this->banner_image);
                }

                return asset($this->banner_image);
            }
        );
    }
}
