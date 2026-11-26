<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Làm gì:   Trang "Hồ sơ học viên" — danh sách theo HỌC SINH (khách đã có thông tin học sinh):
 *           học lực, ngoại ngữ, nguyện vọng, mã Holland, tình trạng hồ sơ du học.
 * Ai gọi:   route crm.students.index (menu Quản lý khách hàng → Hồ sơ học viên).
 * Dữ liệu:  đọc leads (phần II – III – IV của Lead::sections()). Lựa chọn lọc lấy từ cùng khai báo đó.
 */
class StudentController extends Controller
{
    private const PER_PAGE = 20;

    private const HOLLAND_COLUMNS = ['holland_r', 'holland_i', 'holland_a', 'holland_s', 'holland_e', 'holland_c'];

    public function index(Request $request)
    {
        $sections = Lead::sections();
        $profileStatuses = $sections['consult']['fields']['profile_status']['options'];

        $filters = $request->only(['q', 'program', 'german', 'education', 'holland']);
        $tab = array_key_exists($request->query('tab'), $profileStatuses) || $request->query('tab') === 'none'
            ? $request->query('tab') : 'all';

        $base = Lead::visibleTo($request->user())
            ->whereNotNull('student_name')->where('student_name', '!=', '')
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where(fn (Builder $w) => $w
                ->where('student_name', 'like', "%{$term}%")
                ->orWhere('full_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('student_school', 'like', "%{$term}%")
                ->orWhere('desired_major', 'like', "%{$term}%")))
            ->when($filters['program'] ?? null, fn (Builder $q, $v) => $q->where('program_interest', $v))
            ->when($filters['german'] ?? null, fn (Builder $q, $v) => $q->where('german_level', $v))
            ->when($filters['education'] ?? null, fn (Builder $q, $v) => $q->where('education_level', $v))
            // Có Holland = ít nhất 1 nhóm có điểm; chưa có = cả 6 nhóm trống
            ->when(($filters['holland'] ?? null) === 'yes', function (Builder $q) {
                $q->where(function (Builder $w) {
                    foreach (self::HOLLAND_COLUMNS as $c) {
                        $w->orWhereNotNull($c);
                    }
                });
            })
            ->when(($filters['holland'] ?? null) === 'no', function (Builder $q) {
                foreach (self::HOLLAND_COLUMNS as $c) {
                    $q->whereNull($c);
                }
            });

        // Đếm theo tình trạng hồ sơ (theo bộ lọc đang áp dụng)
        $byStatus = (clone $base)->selectRaw('profile_status, count(*) as total')->groupBy('profile_status')->pluck('total', 'profile_status');
        // profile_status null và '' đều thành khoá '' khi pluck
        $counts = ['all' => (int) $byStatus->sum(), 'none' => (int) $byStatus->get('', 0)];
        foreach (array_keys($profileStatuses) as $key) {
            $counts[$key] = (int) ($byStatus[$key] ?? 0);
        }

        $students = (clone $base)
            ->with('assignee')
            ->when($tab === 'none', fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('profile_status')->orWhere('profile_status', '')))
            ->when(! in_array($tab, ['all', 'none'], true), fn (Builder $q) => $q->where('profile_status', $tab))
            ->orderBy('student_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('crm::students.index', [
            'students' => $students,
            'filters' => $filters,
            'tab' => $tab,
            'counts' => $counts,
            'profileStatuses' => $profileStatuses,
            'options' => [
                'program' => $sections['consult']['fields']['program_interest']['options'],
                'german' => $sections['student']['fields']['german_level']['options'],
                'education' => $sections['student']['fields']['education_level']['options'],
            ],
        ]);
    }
}
