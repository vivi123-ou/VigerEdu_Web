<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /** GET /admin/posts : danh sách bài viết */
    public function index()
    {
        return view('admin.posts.index', [
            'posts' => Post::with('category')->latest()->get(),
        ]);
    }

    /** GET /admin/posts/new : form soạn bài mới */
    public function create()
    {
        return view('admin.posts.form', [
            'post' => null,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /** GET /admin/posts/edit/{post} */
    public function edit(Post $post)
    {
        return view('admin.posts.form', [
            'post' => $post,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /** GET /admin/posts/view/{post} : xem trước (kể cả bài nháp) */
    public function show(Post $post)
    {
        return view('admin.posts.show', ['post' => $post->load('category')]);
    }

    /** POST /admin/posts/save : thêm mới hoặc cập nhật (có id thì là cập nhật) */
    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:posts,id'],
            'title' => ['required', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'slug' => [
                'nullable',
                'string',
                'max:500',
                Rule::unique('posts', 'slug')->ignore($request->input('id')),
            ],
            'featured_image' => ['nullable', 'string', 'max:1000'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'slug.unique' => 'Slug này đã được dùng cho bài viết khác.',
        ]);

        $post = filled($data['id'] ?? null) ? Post::findOrFail($data['id']) : new Post;

        // Slug: dùng slug người nhập, nếu trống thì tạo từ tiêu đề (không dấu, duy nhất)
        $slug = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : $this->uniqueSlug(Str::slug($data['title']), $post->id);

        $content = function_exists('clean') ? \clean($data['content']) : $data['content'];

        $post->fill([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $content,
            'status' => $data['status'],
            'category_id' => $data['category_id'] ?? null,
            'slug' => $slug,
            'featured_image' => $data['featured_image'] ?? null,
        ])->save();

        return redirect('/admin/posts')->with('success', 'Đã lưu bài viết thành công!');
    }

    /** POST /admin/posts/delete/{post} */
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect('/admin/posts')->with('success', 'Đã xóa bài viết.');
    }

    /**
     * POST /admin/posts/api/upload-image
     * Dùng cho CKEditor (simpleUpload, field "upload") và nút "Tải ảnh lên".
     * Trả về {"url": "..."} hoặc {"error": {"message": "..."}}.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'], // 5MB, không cho SVG
        ], [
            'upload.required' => 'Chưa chọn ảnh.',
            'upload.mimes' => 'Chỉ chấp nhận ảnh JPG, PNG, GIF hoặc WEBP.',
            'upload.max' => 'Ảnh tối đa 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => ['message' => $validator->errors()->first()]], 422);
        }

        $file = $request->file('upload');
        $name = Str::uuid().'.'.$file->guessExtension();

        // Lưu vào public/uploads, trả về URL dạng /uploads/...
        $file->move(public_path('uploads'), $name);

        return response()->json(['url' => '/uploads/'.$name]);
    }

    /** Thêm hậu tố -2, -3... nếu slug đã tồn tại */
    private function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $base = $base !== '' ? $base : 'bai-viet';
        $slug = $base;
        $i = 2;

        while (Post::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
