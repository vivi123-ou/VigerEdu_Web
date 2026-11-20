<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
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

        $leads = Lead::with('assignee')
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
        $fields = collect(Lead::sections())->flatMap(fn ($s) => $s['fields'])->only(self::CREATE_FIELDS)->all();

        $lead = Lead::create($this->validateFields($request, $fields));

        return redirect()->route('crm.leads.show', $lead)
            ->with('success', "Đã thêm khách hàng {$lead->full_name}. Bạn có thể bổ sung thông tin học sinh và nhu cầu tư vấn.");
    }

    public function show(Lead $lead)
    {
        $lead->load(['assignee', 'activities.user']);

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
        $lead->fill($this->validateFields($request, $def['fields'], $lead));

        $changes = array_keys($lead->getDirty());
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
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xoá khách hàng.');

        $lead->delete();

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
        $filename = 'leads-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mã KH', 'Phụ huynh', 'SĐT', 'Email', 'Học sinh', 'Nguồn', 'Trạng thái', 'Phụ trách', 'Chương trình', 'Mã Holland', 'Ngày tạo', 'Ghi chú']);

            Lead::with('assignee')->filter($filters)->latest()->lazy()->each(function (Lead $lead) use ($out) {
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
                    ? Rule::exists('users', 'id')->where('active', true)
                    : Rule::in(array_keys($def['options']))],
                default => ['string', 'max:255'],
            }];
        }

        $attributes = collect($fields)->map(fn ($d) => mb_strtolower($d['label']))->all();

        return $request->validate($rules, self::MESSAGES, $attributes);
    }

    /** Danh sách tư vấn viên (nhân viên đang hoạt động) cho dropdown. */
    private function consultants(): Collection
    {
        return User::where('active', true)->orderBy('full_name')->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->displayName()]);
    }
}
