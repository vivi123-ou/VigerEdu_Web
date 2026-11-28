<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Models\Category;
use App\Models\Page;
use App\Support\LocalizedUrl;
use Illuminate\Support\Facades\Route;

// CRM (TẠM THỜI chạy trong viger_web cho đến khi viger_crm thành app riêng) — phải nạp TRƯỚC route web chính
require __DIR__.'/crm.php';

/*
|--------------------------------------------------------------------------
| Trang công khai: khai báo cho từng ngôn ngữ
| - Tiếng Việt: /ve-chung-toi, tên route giữ nguyên (category.tong-quan, page.show...)
| - Tiếng Anh:  /en/about-us,  tên route thêm tiền tố "en." (en.category.tong-quan...)
| Slug tiếng Anh lấy từ App\Support\LocalizedUrl::SEGMENTS. URL quyết định ngôn ngữ (middleware locale:xx).
|--------------------------------------------------------------------------
*/
foreach (config('app.supported_locales') as $locale) {
    $isPrimary = $locale === LocalizedUrl::primaryLocale();
    $path = fn (string $vietnamesePath) => LocalizedUrl::path($vietnamesePath, $locale);

    Route::middleware("locale:{$locale}")
        ->prefix(LocalizedUrl::prefix($locale))
        ->name($isPrimary ? '' : "{$locale}.")
        ->group(function () use ($path) {
            /*
            | Trang tĩnh
            */
            Route::view('/', 'welcome')->name('home');
            Route::view($path('/ve-chung-toi'), 'about');
            Route::view($path('/giao-duc-tai-duc'), 'education');
            Route::view($path('/tu-van'), 'consult');
            Route::view($path('/tu-van/quy-trinh-tu-van'), 'consult.consult-process');

            /*
            | Liên hệ
            */
            Route::get($path('/lien-he'), [ContactController::class, 'show']);
            Route::post($path('/lien-he'), [ContactController::class, 'store'])->middleware('throttle:5,1');

            /*
            | Các trang danh sách bài viết theo chuyên mục
            | URL tiếng Việt => [slug chuyên mục, tên view]
            | Mỗi route được đặt tên category.{slug} (dùng cho breadcrumb ở post-detail)
            */
            $categoryPages = [
                '/giao-duc-tai-duc/tong-quan' => ['tong-quan', 'education.overview'],
                '/giao-duc-tai-duc/he-thong-giao-duc' => ['he-thong-giao-duc', 'education.education-system'],
                '/giao-duc-tai-duc/nghien-cuu-va-ung-dung' => ['nghien-cuu-va-ung-dung', 'education.research-applied'],      // CHƯA có view
                '/giao-duc-tai-duc/chi-phi-va-co-hoi' => ['chi-phi-va-co-hoi', 'education.costs-opportunities'],
                '/giao-duc-tai-duc/co-hoi-nghe-nghiep' => ['co-hoi-nghe-nghiep', 'education.career-opportunities'],
                '/tu-van/du-hoc-dai-hoc' => ['du-hoc-dai-hoc', 'consult.consult-bachelor'],        // CHƯA có view
                '/tu-van/du-hoc-cao-hoc' => ['du-hoc-cao-hoc', 'consult.consult-master'],          // CHƯA có view
                '/chuong-trinh-hoc' => ['chuong-trinh-hoc', 'programs'],
                '/blog/hieu-ve-duc' => ['hieu-ve-duc', 'blog.understand-germany'],         // CHƯA có view
                '/blog/cuoc-song-o-duc' => ['cuoc-song-o-duc', 'blog.life-in-germany'],            // CHƯA có view
            ];

            foreach ($categoryPages as $uri => [$slug, $view]) {
                Route::get($path($uri), [BlogController::class, 'category'])
                    ->defaults('slug', $slug)
                    ->defaults('view', $view)
                    ->name("category.$slug");
            }

            /*
            | Chuyên mục thêm mới từ admin (không có view riêng) => view chung "category"
            | Phải đặt SAU các route cố định ở trên để không đè lên chúng.
            */
            foreach (Category::GROUP_PREFIXES as $group => $prefix) {
                Route::get($path($prefix).'/{slug}', [BlogController::class, 'groupCategory'])
                    ->defaults('group', $group)
                    ->where('slug', '[a-z0-9-]+');
            }

            /*
            | Blog
            */
            Route::get($path('/blog'), [BlogController::class, 'index']);
            Route::get($path('/bai-viet').'/{slug}', [BlogController::class, 'show']);

            /*
            | Trang tĩnh động (quản trị từ Filament)
            */
            Route::get($path('/trang').'/{slug}', function (string $slug) {
                $page = Page::published()->where('slug', $slug)->firstOrFail();

                return view('page', compact('page'));
            })->name('page.show');
        });
}

/*
|--------------------------------------------------------------------------
| Đăng nhập / đăng xuất
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| Khu vực quản trị (yêu cầu đăng nhập + role ADMIN)
| Chỉ MỘT nhóm duy nhất, prefix đầy đủ là admin/posts => /admin/posts/...
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin/posts')
    ->group(function () {
        Route::get('/', [AdminPostController::class, 'index']);
        Route::get('/new', [AdminPostController::class, 'create']);
        Route::get('/edit/{post}', [AdminPostController::class, 'edit']);
        Route::get('/view/{post}', [AdminPostController::class, 'show']);
        Route::post('/save', [AdminPostController::class, 'save']);
        Route::post('/delete/{post}', [AdminPostController::class, 'destroy']);
        Route::post('/api/upload-image', [AdminPostController::class, 'uploadImage']);
    });

/*
|--------------------------------------------------------------------------
| Chuyển ngôn ngữ
|--------------------------------------------------------------------------
*/
// Link cũ / trang không có tiền tố ngôn ngữ: ghi nhớ ngôn ngữ và chuyển sang trang tương ứng
Route::get('/lang/{locale}', function (string $locale) {
    if (! in_array($locale, config('app.supported_locales'), true)) {
        return redirect()->back(fallback: '/');
    }

    session()->put('locale', $locale);

    return redirect(LocalizedUrl::switchTo($locale, url()->previous('/')));
})->name('switch.language');
