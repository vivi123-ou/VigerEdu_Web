<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Ghi chú tư vấn" — chỉ nội dung nhân viên nhập (ghi chú, cuộc gọi, gặp mặt, tin nhắn, email):
 *           tìm trong nội dung, thêm nhanh cho bất kỳ khách nào, sửa / xoá ghi chú của mình.
 * Ai gọi:   route crm.notes.* (menu Quản lý tư vấn → Ghi chú tư vấn).
 * Dữ liệu:  đọc/ghi lead_activities có type thuộc LeadActivity::MANUAL_TYPES. Dòng hệ thống không sửa được.
 *           Quyền sửa / xoá: người viết hoặc quản trị viên.
 */
class NoteController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $type = in_array($request->query('type'), LeadActivity::MANUAL_TYPES, true) ? $request->query('type') : null;
        $author = $request->query('author');
        $q = trim((string) $request->query('q'));

        $base = LeadActivity::query()
            ->whereHas('lead', fn (Builder $l) => $l->visibleTo($request->user()))
            ->whereIn('type', LeadActivity::MANUAL_TYPES)
            ->when($author, fn (Builder $b) => $b->where('user_id', $author))
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('content', 'like', "%{$q}%")
                ->orWhereHas('lead', fn (Builder $l) => $l
                    ->where('full_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('student_name', 'like', "%{$q}%"))));

        $byType = (clone $base)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        $notes = (clone $base)
            ->when($type, fn (Builder $b) => $b->where('type', $type))
            ->with(['lead:id,full_name,phone,student_name,status', 'user'])
            ->latest()->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('crm::notes.index', [
            'notes' => $notes,
            'type' => $type,
            'author' => $author,
            'q' => $q,
            'counts' => ['all' => (int) $byType->sum()] + collect(LeadActivity::MANUAL_TYPES)->mapWithKeys(fn ($t) => [$t => (int) ($byType[$t] ?? 0)])->all(),
            'leads' => Lead::visibleTo($request->user())->orderBy('full_name')->get(['id', 'full_name', 'phone', 'student_name'])
                ->mapWithKeys(fn (Lead $l) => [$l->id => $l->full_name.' · '.$l->phone.($l->student_name ? ' (HS: '.$l->student_name.')' : '')]),
            'authors' => $request->user()->assignableUsers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['required', Rule::exists('leads', 'id')],
            'type' => ['required', Rule::in(LeadActivity::MANUAL_TYPES)],
            'content' => ['required', 'string', 'max:2000'],
        ], [], ['lead_id' => 'khách hàng', 'type' => 'loại', 'content' => 'nội dung']);

        $lead = Lead::findOrFail($data['lead_id']);
        abort_unless($request->user()->canSeeLead($lead), 403, 'Khách hàng này không thuộc phạm vi của bạn.');
        $lead->log($data['content'], $data['type']);

        return redirect()->route('crm.notes.index')->with('success', "Đã thêm ghi chú cho {$lead->full_name}.");
    }

    public function update(Request $request, LeadActivity $note)
    {
        $this->authorizeNote($request, $note);

        $data = $request->validate([
            'type' => ['required', Rule::in(LeadActivity::MANUAL_TYPES)],
            'content' => ['required', 'string', 'max:2000'],
        ], [], ['type' => 'loại', 'content' => 'nội dung']);

        $note->update($data);
        CrmAuditLog::record('notes.change', "Sửa ghi chú #{$note->id} của khách {$note->lead->code()}.");

        return back()->with('success', 'Đã cập nhật ghi chú.');
    }

    public function destroy(Request $request, LeadActivity $note)
    {
        $this->authorizeNote($request, $note);

        $note->delete();
        CrmAuditLog::record('notes.change', "Xoá ghi chú #{$note->id} của khách {$note->lead->code()}.");

        return back()->with('success', 'Đã xoá ghi chú.');
    }

    /** Ai được sửa / xoá một ghi chú: người viết hoặc quản trị viên; dòng hệ thống thì không ai. */
    public static function canManage(?User $user, LeadActivity $note): bool
    {
        return $user
            && in_array($note->type, LeadActivity::MANUAL_TYPES, true)
            && ($user->crmCan('notes.manage_all') || $note->user_id === $user->id);
    }

    private function authorizeNote(Request $request, LeadActivity $note): void
    {
        abort_unless(self::canManage($request->user(), $note), 403, 'Bạn chỉ được sửa / xoá ghi chú do mình viết.');
    }
}
