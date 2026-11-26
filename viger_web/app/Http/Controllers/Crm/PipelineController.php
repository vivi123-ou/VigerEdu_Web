<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmSetting;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Quy trình tư vấn" — bảng Kanban, mỗi cột một trạng thái lead; kéo thả để chuyển trạng thái.
 * Ai gọi:   route crm.pipeline.index (xem), crm.pipeline.move (JS kéo thả / ô chọn trên thẻ).
 * Dữ liệu:  đọc leads, lead_activities (lần tương tác gần nhất); ghi leads.status + lịch sử qua Lead::moveToStatus().
 */
class PipelineController extends Controller
{
    /** Số thẻ tối đa mỗi cột; cột nhiều hơn có link "Xem tất cả" sang trang Leads đã lọc */
    private const PER_COLUMN = 30;

    /** Cột đã đóng (chốt / loại) chỉ hiện khách cập nhật trong N ngày gần nhất */
    private const CLOSED = ['converted', 'lost'];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'source', 'assigned_to']);

        $user = $request->user();
        $columns = collect(Lead::STATUSES)->map(function ($meta, $status) use ($filters, $user) {
            $query = Lead::visibleTo($user)
                ->filter($filters)
                ->where('status', $status)
                ->when(in_array($status, self::CLOSED, true), fn ($q) => $q->where('updated_at', '>=', now()->subDays(CrmSetting::get('closed_days'))));

            return [
                'label' => $meta[0],
                'badge' => $meta[1],
                'total' => (clone $query)->count(),
                'leads' => $query->with('assignee')
                    ->withMax('activities as last_activity_at', 'created_at')
                    ->latest('updated_at')->latest('id')
                    ->limit(self::PER_COLUMN)
                    ->get(),
            ];
        });

        return view('crm::pipeline.index', [
            'columns' => $columns,
            'filters' => $filters,
            'closedDays' => CrmSetting::get('closed_days'),
            'closed' => self::CLOSED,
            'consultants' => $request->user()->assignableUsers(),
        ]);
    }

    /** Chuyển một lead sang trạng thái khác (kéo thả). Trả JSON cho JS; form thường thì quay lại trang. */
    public function move(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Lead::STATUSES))],
        ]);

        $changed = $lead->moveToStatus($data['status']);

        if ($request->expectsJson()) {
            return response()->json([
                'changed' => $changed,
                'status' => $lead->status,
                'label' => $lead->statusLabel(),
            ]);
        }

        return back()->with('success', $changed
            ? "Đã chuyển {$lead->full_name} sang “{$lead->statusLabel()}”."
            : 'Trạng thái không đổi.');
    }
}
