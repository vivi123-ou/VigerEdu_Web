<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Một ảnh trong thư viện. `path` là đường dẫn công khai (storage/media/…), dùng thẳng làm giá trị cài đặt ảnh.
 */
#[Fillable(['path', 'original_name', 'mime', 'size', 'width', 'height', 'user_id'])]
class Media extends Model
{
    use SoftDeletes;

    public function url(): string
    {
        return asset($this->path);
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', '').' MB'
            : max(1, (int) round($this->size / 1024)).' KB';
    }
}
