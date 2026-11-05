<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /** 5 bài mới nhất cho sidebar. */
    private function latestPosts()
    {
        return Post::published()->latest()->take(5)->get();
    }

    /**
     * Trang danh sách bài viết theo chuyên mục.
     * $slug và $view được truyền từ routes/web.php qua ->defaults().
     */
    public function category(string $slug, string $view)
    {
        $posts = Post::published()
            ->whereHas('category', fn ($q) => $q->where('slug', $slug))
            ->latest()
            ->paginate(6);

        return view($view, [
            'posts' => $posts,
            'latestPosts' => $this->latestPosts(),
        ]);
    }

    /**
     * Trang chuyên mục thêm từ admin (chưa có view riêng):
     * /giao-duc-tai-duc/{slug}, /tu-van/{slug}, /blog/{slug}. $group truyền qua ->defaults().
     */
    public function groupCategory(string $slug, string $group)
    {
        $category = Category::where('menu_group', $group)->where('slug', $slug)->firstOrFail();

        $posts = Post::published()
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(6);

        return view('category', [
            'category' => $category,
            'posts' => $posts,
            'latestPosts' => $this->latestPosts(),
        ]);
    }

    /** Chi tiết bài viết: /bai-viet/{slug} */
    public function show(string $slug)
    {
        $post = Post::published()->with('category')->where('slug', $slug)->first();

        if (! $post) {
            return redirect('/blog');
        }

        // Bài liên quan: cùng chuyên mục, không có thì lấy bài khác
        $relatedPosts = collect();
        if ($post->category_id) {
            $relatedPosts = Post::published()
                ->where('category_id', $post->category_id)
                ->whereKeyNot($post->id)
                ->latest()->limit(5)->get();
        }
        if ($relatedPosts->isEmpty()) {
            $relatedPosts = Post::published()
                ->whereKeyNot($post->id)
                ->latest()->limit(5)->get();
        }

        return view('post-detail', compact('post', 'relatedPosts'));
    }

    /** Trang /blog: bài nổi bật + lọc theo chuyên mục + tìm kiếm + phân trang. */
    public function index(Request $request)
    {
        $categoryId = $request->integer('category') ?: null;
        $keyword = trim((string) $request->query('q', ''));
        $filtering = $categoryId !== null || $keyword !== '';

        // Bài nổi bật: chỉ khi không lọc/tìm kiếm
        $featured = $filtering ? null : Post::published()->latest()->first();

        $posts = Post::published()
            ->with('category')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($keyword !== '', fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('excerpt', 'like', "%{$keyword}%");
            }))
            ->when($featured, fn ($q) => $q->whereKeyNot($featured->id))
            ->latest()
            ->paginate(9)
            ->withQueryString(); // giữ ?category= & ?q= khi chuyển trang

        return view('blog', [
            'featured' => $featured,
            'posts' => $posts,
            'categories' => Category::all(),
            'activeCategory' => $categoryId,
            'q' => $keyword,
        ]);
    }
}
