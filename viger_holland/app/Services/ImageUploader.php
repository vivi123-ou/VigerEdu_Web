<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lưu ảnh vào thư viện: thu nhỏ nếu rộng hơn MAX_WIDTH, nén JPEG / WebP, giữ PNG trong suốt.
 * Dùng GD có sẵn trong PHP (không thêm package).
 */
final class ImageUploader
{
    public const MAX_WIDTH = 1920;

    private const JPEG_QUALITY = 82;

    public function store(UploadedFile $file): Media
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false) {
            throw new RuntimeException('File không phải ảnh.');
        }

        [$width, $height] = $info;
        $mime = $info['mime'];
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => throw new RuntimeException('Chỉ nhận ảnh JPG, PNG, WebP hoặc GIF.'),
        };

        $relative = 'media/'.now()->format('Y/m').'/'.Str::lower(Str::random(12)).'.'.$extension;
        $disk = Storage::disk('public');
        $disk->makeDirectory(dirname($relative));
        $target = $disk->path($relative);

        // GIF (có thể là ảnh động) giữ nguyên; ảnh khác thu nhỏ + nén.
        if ($mime === 'image/gif' || ! function_exists('imagecreatetruecolor')) {
            copy($file->getRealPath(), $target);
        } else {
            [$width, $height] = $this->resize($file->getRealPath(), $target, $mime, $width, $height);
        }

        return Media::create([
            'path' => 'storage/'.$relative,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 250),
            'mime' => $mime,
            'size' => (int) filesize($target),
            'width' => $width,
            'height' => $height,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resize(string $source, string $target, string $mime, int $width, int $height): array
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($source),
            'image/png' => imagecreatefrompng($source),
            'image/webp' => imagecreatefromwebp($source),
        };

        if ($image === false) {
            throw new RuntimeException('Không đọc được ảnh.');
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyOrientation($image, $source);
            [$width, $height] = [imagesx($image), imagesy($image)];
        }

        if ($width > self::MAX_WIDTH) {
            $newHeight = (int) round($height * self::MAX_WIDTH / $width);
            $resized = imagecreatetruecolor(self::MAX_WIDTH, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, self::MAX_WIDTH, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            [$width, $height] = [self::MAX_WIDTH, $newHeight];
        } else {
            imagesavealpha($image, true);
        }

        match ($mime) {
            'image/jpeg' => imagejpeg($image, $target, self::JPEG_QUALITY),
            'image/png' => imagepng($image, $target, 8),
            'image/webp' => imagewebp($image, $target, self::JPEG_QUALITY),
        };
        imagedestroy($image);

        return [$width, $height];
    }

    /**
     * Ảnh chụp điện thoại: xoay đúng chiều theo EXIF.
     */
    private function applyOrientation(\GdImage $image, string $source): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($source)['Orientation'] ?? 1) : 1;

        $rotated = match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated === false ? $image : $rotated;
    }
}
