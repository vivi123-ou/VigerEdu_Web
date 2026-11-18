<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Làm gì:   Hộp "Chọn ảnh" của chế độ "Sửa trực tiếp trên trang": danh sách ảnh trong kho, tải ảnh mới (tự thu nhỏ, nén). Trả JSON.
 *           Trang quản lý Kho ảnh (xem lưới, xóa, khôi phục) là trang Filament App\Modules\Content\Filament\Pages\MediaLibrary.
 * Ai gọi:   public/js/page-editor.js (resources/views/admin/media/picker.blade.php).
 * Dữ liệu:  bảng media, App\Services\ImageUploader.
 */
class MediaController extends Controller
{
    public function index(): JsonResponse
    {
        $media = Media::query()->latest('id')->paginate(48);

        return response()->json([
            'items' => $media->getCollection()->map(fn (Media $item) => $this->toJson($item))->values(),
            'next' => $media->nextPageUrl(),
        ]);
    }

    public function store(Request $request, ImageUploader $uploader): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ], [
            'files.required' => 'Vui lòng chọn ảnh.',
            'files.*.mimes' => 'Chỉ nhận ảnh JPG, PNG, WebP hoặc GIF.',
            'files.*.max' => 'Mỗi ảnh tối đa 8 MB.',
        ]);

        $saved = [];
        foreach ($request->file('files') as $file) {
            try {
                $saved[] = $uploader->store($file);
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
        }

        return response()->json(['items' => array_map(fn (Media $item) => $this->toJson($item), $saved)]);
    }

    /**
     * @return array{id: int, path: string, url: string, name: string, size: string, width: int|null, height: int|null}
     */
    private function toJson(Media $item): array
    {
        return [
            'id' => $item->id,
            'path' => $item->path,
            'url' => $item->url(),
            'name' => $item->original_name,
            'size' => $item->sizeLabel(),
            'width' => $item->width,
            'height' => $item->height,
        ];
    }
}
