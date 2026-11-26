<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

/**
 * Làm gì:   Trang "Danh sách khách hàng" — danh bạ toàn bộ khách (mọi trạng thái), tập trung vào
 *           thông tin liên hệ và lần tương tác gần nhất. Khác trang Leads (quản lý quy trình tư vấn).
 * Ai gọi:   route crm.customers.index (menu Quản lý khách hàng → Danh sách khách hàng).
 * Dữ liệu:  đọc leads, lead_activities (đếm + thời điểm gần nhất), users (tư vấn viên).
 */
class CustomerController extends Controller
{
    /** Tab nhóm khách: key => [nhãn, các trạng thái] */
    public const TABS = [
        'all' => ['Tất cả', null],
        'open' => ['Đang chăm sóc', ['new', 'contacted', 'consulting', 'potential']],
        'converted' => ['Đã chuyển đổi', ['converted']],
        'lost' => ['Không tiềm năng', ['lost']],
    ];

    public const SORTS = [
        'recent' => 'Tương tác gần nhất',
        'newest' => 'Mới tiếp nhận',
        'name' => 'Tên A → Z',
        'idle' => 'Lâu chưa liên hệ',
    ];

    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'source', 'assigned_to']);
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'recent';

        $base = Lead::visibleTo($request->user())->filter($filters);

        // Số khách mỗi tab (theo bộ lọc tìm kiếm / nguồn / tư vấn viên đang áp dụng)
        $counts = collect(self::TABS)->map(fn ($t) => (clone $base)
            ->when($t[1], fn ($q, $statuses) => $q->whereIn('status', $statuses))
            ->count());

        $customers = (clone $base)
            ->with('assignee')
            ->withCount('activities')
            ->withMax('activities as last_activity_at', 'created_at')
            ->when(self::TABS[$tab][1], fn ($q, $statuses) => $q->whereIn('status', $statuses))
            ->tap(fn ($q) => match ($sort) {
                'newest' => $q->latest(),
                'name' => $q->orderBy('full_name'),
                'idle' => $q->orderByRaw('last_activity_at is null desc')->orderBy('last_activity_at'),
                default => $q->orderByDesc('last_activity_at'),
            })
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('crm::customers.index', [
            'customers' => $customers,
            'filters' => $filters,
            'tab' => $tab,
            'sort' => $sort,
            'counts' => $counts,
            'consultants' => $request->user()->assignableUsers(),
        ]);
    }
}
