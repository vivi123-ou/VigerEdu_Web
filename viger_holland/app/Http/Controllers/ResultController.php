<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Services\ResultPresenter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * Trang kết quả cá nhân, xem bằng mã riêng (không cần đăng nhập, không cho Google lập chỉ mục).
     */
    public function show(string $token, ResultPresenter $presenter): View
    {
        abort_unless(Str::isUuid($token), 404);

        $attempt = Attempt::where('result_token', $token)->firstOrFail();

        return view('public.result', [
            'attempt' => $attempt,
            'result' => $presenter->present($attempt),
        ]);
    }
}
