<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    private const FILTER_KEYS = ['q', 'status', 'source', 'assigned_to', 'date'];

    /** Các trường có trong form "Thêm khách hàng" */
    private const CREATE_FIELDS = ['full_name', 'phone', 'email', 'source', 'status', 'assigned_to', 'note'];

    private const MESSAGES = [
        'phone.regex' => 'Số điện thoại không hợp lệ (VD: 0901234567).',
        'phone.unique' => 'Số điện thoại này đã có trong danh sách leads.',
        'student_dob.before' => 'Ngày sinh phải trước ngày hôm nay.',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(self::FILTER_KEYS);

        $leads = Lead::visibleTo($request->user())->with('assignee')
            ->filter($filters)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('crm::leads.index', [
            'leads' => $leads,
            'filters' => $filters,
            'consultants' => $this->consultants(),
        ]);
    }

    public function create()
    {
        return view('crm::leads.create', ['consultants' => $this->consultants()]);
    }

    public function store(Request $request)
    {
        $canAssign = $request->user()->crmCan('leads.assign');
        $fields = collect(Lead::sections())->flatMap(fn ($s) => $s['fields'])->only(self::CREATE_FIELDS)
            ->when(! $canAssign, fn ($c) => $c->except('assigned_to'))   // không có quyền => bỏ qua trường gửi lên
            ->all();

        $data = $this->validateFields($request, $fields);

        // Không có quyền phân công => khách tự giao cho người tạo (để không "mất" khách khỏi phạm vi của mình)
        if (! $canAssign) {
            $data['assigned_to'] = $request->user()->id;
        }

        $lead = Lead::create($data);

        return redirect()->route('crm.leads.show', $lead)
            ->with('success', "Đã thêm khách hàng {$lead->full_name}. Bạn có thể bổ sung thông tin học sinh và nhu cầu tư vấn.");
    }

    public function show(Lead $lead)
    {
        $lead->load(['assignee', 'activities.user', 'appointments.consultant']);

        return view('crm::leads.show', [
            'lead' => $lead,
            'sections' => Lead::sections(),
        ]);
    }

    /** Form sửa một phần thông tin: general | parent | student | consult | holland */
    public function edit(Lead $lead, string $section)
    {
        return view('crm::leads.edit', [
            'lead' => $lead,
            'key' => $section,
            'section' => $this->section($section),
            'consultants' => $this->consultants(),
        ]);
    }

    public function update(Request $request, Lead $lead, string $section)
    {
        $def = $this->section($section);

        // Không có quyền phân công => bỏ qua trường người phụ trách (không đổi, không báo lỗi)
        $fields = $request->user()->crmCan('leads.assign') ? $def['fields'] : array_diff_key($def['fields'], ['assigned_to' => true]);
        $data = $this->validateFields($request, $fields, $lead);

        $lead->fill($data);

        $changes = array_keys($lead->getDirty());

        // Tư vấn viên tự sửa điểm Holland => bỏ mã / gói kết quả cũ từ hệ thống Holland, mã tính lại từ điểm mới
        if ($section === 'holland' && $changes) {
            $lead->holland_code = null;
            $lead->holland_result = null;
        }
        $oldStatus = $lead->getOriginal('status');
        $lead->save();
        $lead->load('assignee');

        // Ghi lịch sử tư vấn
        if (in_array('status', $changes, true)) {
            $lead->log('Đổi trạng thái: '.(Lead::STATUSES[$oldStatus][0] ?? $oldStatus).' → '.$lead->statusLabel().'.');
        }
        if (in_array('assigned_to', $changes, true)) {
            $lead->log($lead->assignee
                ? 'Phân công tư vấn viên: '.$lead->assignee->displayName().'.'
                : 'Bỏ phân công tư vấn viên.');
        }
        $others = array_diff($changes, ['status', 'assigned_to']);
        if ($others) {
            $labels = collect($others)->map(fn ($f) => $def['fields'][$f]['label'])->implode(', ');
            $lead->log("Cập nhật {$def['title']}: {$labels}.");
        }

        return redirect()->route('crm.leads.show', $lead)->withFragment($section)
            ->with('success', $changes ? 'Đã lưu thay đổi.' : 'Không có thay đổi nào.');
    }

    public function destroy(Request $request, Lead $lead)
    {
        $lead->delete();
        CrmAuditLog::record('leads.delete', "Xoá khách hàng {$lead->code()}.");

        return redirect()->route('crm.leads.index')
            ->with('success', "Đã xoá khách hàng {$lead->full_name} ({$lead->code()}).");
    }

    /** Mục VI — thêm ghi chú / hoạt động trao đổi với phụ huynh */
    public function storeNote(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(LeadActivity::MANUAL_TYPES)],
            'content' => ['required', 'string', 'max:2000'],
        ], [], ['content' => 'nội dung', 'type' => 'loại hoạt động']);

        $lead->log($data['content'], $data['type']);

        return redirect()->route('crm.leads.show', $lead)->withFragment('history')
            ->with('success', 'Đã thêm ghi chú.');
    }

    /**
     * Xuất CSV (UTF-8 BOM để Excel hiển thị đúng tiếng Việt), theo đúng bộ lọc đang áp dụng.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        // Bộ lọc ghi bằng chữ dễ đọc (VD "trạng thái: Mới"), không ghi tham số URL
        $applied = collect($filters)->filter(fn ($v) => filled($v))->map(fn ($v, $k) => match ($k) {
            'q' => "tìm “{$v}”",
            'status' => 'trạng thái: '.(Lead::STATUSES[$v][0] ?? $v),
            'source' => 'nguồn: '.(Lead::SOURCES[$v] ?? $v),
            'assigned_to' => 'phụ trách: '.($v === 'none' ? 'Chưa phân công' : (User::find($v)?->displayName() ?? $v)),
            'date' => 'thời gian: '.(['today' => 'Hôm nay', '7d' => '7 ngày qua', '30d' => '30 ngày qua'][$v] ?? $v),
            default => "{$k}: {$v}",
        });
        CrmAuditLog::record('leads.export', 'Xuất CSV danh sách khách'.($applied->isNotEmpty() ? ' (lọc: '.$applied->implode(', ').')' : '').'.');
        $filename = 'leads-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mã KH', 'Phụ huynh', 'SĐT', 'Email', 'Học sinh', 'Nguồn', 'Trạng thái', 'Phụ trách', 'Chương trình', 'Mã Holland', 'Ngày tạo', 'Ghi chú']);

            Lead::visibleTo(auth()->user())->with('assignee')->filter($filters)->latest()->lazy()->each(function (Lead $lead) use ($out) {
                fputcsv($out, [
                    $lead->code(),
                    $lead->full_name,
                    $lead->phone,
                    $lead->email,
                    $lead->student_name,
                    $lead->sourceLabel(),
                    $lead->statusLabel(),
                    $lead->assignee?->displayName() ?? 'Chưa gán',
                    $lead->program_interest,
                    $lead->hollandCode(),
                    $lead->created_at->format('d/m/Y H:i'),
                    $lead->note,
                ]);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function section(string $key): array
    {
        return Lead::sections()[$key] ?? abort(404);
    }

    /** Sinh luật validate từ khai báo trường trong Lead::sections(). */
    private function validateFields(Request $request, array $fields, ?Lead $lead = null): array
    {
        $rules = [];
        foreach ($fields as $name => $def) {
            $base = ! empty($def['required']) ? ['required'] : ['nullable'];

            $rules[$name] = [...$base, ...match ($def['type']) {
                'textarea' => ['string', 'max:2000'],
                'email' => ['email', 'max:255'],
                'tel' => ['regex:/^(0|\+84)\d{9,10}$/', Rule::unique('leads', 'phone')->ignore($lead?->id)],
                'date' => ['date', 'before:today'],
                'score' => ['integer', 'between:0,100'],
                'gpa' => ['numeric', 'between:0,10'],
                'select' => [$def['options'] === 'consultants'
                    ? Rule::in($this->consultants()->keys()->all())
                    : Rule::in(array_keys($def['options']))],
                default => ['string', 'max:255'],
            }];
        }

        $attributes = collect($fields)->map(fn ($d) => mb_strtolower($d['label']))->all();

        return $request->validate($rules, self::MESSAGES, $attributes);
    }

    /** Danh sách tư vấn viên (nhân viên đang hoạt động) cho dropdown. */
    /** Danh sách tư vấn viên có thể giao khách — theo phạm vi của người đang thao tác. */
    private function consultants(): Collection
    {
        return auth()->user()->assignableUsers();
    }
}
