<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Luồng làm bài của khách. GĐ3 làm lại theo Career Map: Thông tin → Làm bài (36 câu rút từ thư viện) → Báo cáo.
 */
class TestController extends Controller
{
    public function show(): View
    {
        return view('public.updating');
    }
}
