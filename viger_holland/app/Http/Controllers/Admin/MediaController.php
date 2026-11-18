<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Thư viện ảnh: tải lên (tự thu nhỏ, nén), chọn lại ảnh cũ, xóa vào thùng rác.
 * Trả JSON khi được gọi từ hộp "Chọn ảnh" trong các form nội dung.
 */
class MediaController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $media = Media::query()->latest('id')->paginate(48);

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $media->getCollection()->map(fn (Media $item) => $this->toJson($item))->values(),
                'next' => $media->nextPageUrl(),
            ]);
        }

        return view('admin.media.index', [
            'media' => $media,
            'trashed' => Media::onlyTrashed()->latest('deleted_at')->limit(24)->get(),
        ]);
    }

    public function store(Request $request, ImageUploader $uploader): RedirectResponse|JsonResponse
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
                return $request->wantsJson()
                    ? response()->json(['message' => $exception->getMessage()], 422)
                    : back()->withErrors(['file' => $exception->getMessage()]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['items' => array_map(fn (Media $item) => $this->toJson($item), $saved)]);
        }

        return back()->with('status', 'Đã tải lên '.count($saved).' ảnh.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $media->delete();

        return back()->with('status', 'Đã đưa ảnh vào thùng rác. Trang nào đang dùng ảnh này vẫn hiện bình thường.');
    }

    public function restore(int $id): RedirectResponse
    {
        Media::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('status', 'Đã khôi phục ảnh.');
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
