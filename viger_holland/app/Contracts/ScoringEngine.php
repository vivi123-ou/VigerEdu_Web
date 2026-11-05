<?php

namespace App\Contracts;

use App\Data\HollandResult;
use App\Data\Major;
use Illuminate\Support\Collection;

/**
 * Thuật toán chấm điểm Holland. Mọi engine phải trả kết quả đúng cấu trúc `holland-result/1`
 * (App\Data\HollandResult) để CRM và chatbot dùng được mà không phải sửa.
 *
 * Cách viết engine mới: xem docs/THAY_THUAT_TOAN.md.
 */
interface ScoringEngine
{
    /**
     * Mã engine, ghi vào kết quả (ví dụ "riasec_fit_v2").
     */
    public function name(): string;

    public function version(): string;

    /**
     * Chấm từ bài làm từng câu.
     *
     * @param  list<array{group: string, value: int|float, max: int|float}>  $answers
     * @param  Collection<int, Major>  $majors
     */
    public function score(array $answers, Collection $majors): HollandResult;

    /**
     * Chấm từ điểm % 6 nhóm có sẵn (0–1), ví dụ khi chatbot đã tự tính.
     *
     * @param  array<string, int|float>  $percent  ['R' => 0.33, 'I' => 0.67, ...]
     * @param  Collection<int, Major>  $majors
     */
    public function scorePercent(array $percent, Collection $majors): HollandResult;
}
