<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminMenu;
use Illuminate\View\View;

/**
 * Trang tạm cho các mục admin chưa làm: báo rõ mục này sẽ có ở giai đoạn nào.
 */
class SectionController extends Controller
{
    public function __invoke(string $section): View
    {
        $item = AdminMenu::find($section);

        abort_if($item === null, 404);

        return view('admin.coming-soon', ['item' => $item]);
    }
}
