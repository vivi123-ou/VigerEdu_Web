<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Support\PostDetailPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /** Bài đã xuất bản, viết bằng ngôn ngữ đang chọn trên website. */
    private function visiblePosts(): Builder
    {
        return Post::published()->writtenIn(app()->getLocale());
    }

    /** 5 bài mới nhất cho sidebar. */
    private function latestPosts()
    {
        return $this->visiblePosts()->latest()->take(5)->get();
    }

    /**
     * Trang danh sách bài viết theo chuyên mục.
     * $slug và $view được truyền từ routes/web.php qua ->defaults().
     */
    public function category(string $slug, string $view)
    {
        $category = Category::with('translationOf.englishVersion', 'englishVersion')->where('slug', $slug)->first();

        // Chế độ tiếng Anh: lấy cả bài của chuyên mục tiếng Anh riêng (vd. Tổng quan => German education overview)
        $posts = $this->visiblePosts()
            ->whereIn('category_id', $category?->languageFamilyIds() ?? [])
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
        $category = Category::with('englishVersion')->where('menu_group', $group)->where('slug', $slug)->firstOrFail();

        $posts = $this->visiblePosts()
            ->whereIn('category_id', $category->languageFamilyIds())
            ->latest()
            ->paginate(6);

        return view('category', [
            'category' => $category->localized(),
            'posts' => $posts,
            'latestPosts' => $this->latestPosts(),
        ]);
    }

    /** Chi tiết bài viết: /bai-viet/{slug} */
    public function show(string $slug)
    {
        $post = $this->visiblePosts()->with('category')->where('slug', $slug)->first();

        if (! $post) {
            return redirect('/blog');
        }

        // Bài liên quan: cùng chuyên mục, không có thì lấy bài khác (số lượng cấu hình trong admin)
        $relatedCount = PostDetailPage::relatedCount();
        $relatedPosts = collect();
        if ($post->category_id) {
            $relatedPosts = $this->visiblePosts()
                ->where('category_id', $post->category_id)
                ->whereKeyNot($post->id)
                ->latest()->limit($relatedCount)->get();
        }
        if ($relatedPosts->isEmpty()) {
            $relatedPosts = $this->visiblePosts()
                ->whereKeyNot($post->id)
                ->latest()->limit($relatedCount)->get();
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
        $featured = $filtering ? null : $this->visiblePosts()->latest()->first();

        $posts = $this->visiblePosts()
            ->with('category')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            // Tìm trong tiêu đề/tóm tắt của ngôn ngữ đang chọn (cột JSON đa ngôn ngữ)
            ->when($keyword !== '', fn ($q) => $q->whereTranslationLike(['title', 'excerpt'], $keyword, [app()->getLocale()]))
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
