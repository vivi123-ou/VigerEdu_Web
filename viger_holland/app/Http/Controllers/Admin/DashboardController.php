<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Bảng điều khiển: lối tắt tới việc hay làm. Số liệu thư viện câu hỏi và lượt làm bài thêm ở các giai đoạn sau.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard');
    }
}
