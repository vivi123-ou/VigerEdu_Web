<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Làm gì:   Trang "Lịch hẹn": danh sách theo ngày (Sắp tới / Hôm nay / Quá hạn / Đã xong / Đã huỷ),
 *           đặt lịch, đổi lịch, cập nhật kết quả (hoàn thành, khách vắng mặt, huỷ).
 * Ai gọi:   route crm.appointments.*; nút "Đặt lịch hẹn" trên hồ sơ khách (?lead=ID).
 * Dữ liệu:  đọc/ghi appointments; mỗi thao tác ghi một dòng lịch sử tư vấn (lead_activities, loại "appointment").
 */
class AppointmentController extends Controller
{
    public const TABS = [
        'upcoming' => 'Sắp tới',
        'today' => 'Hôm nay',
        'overdue' => 'Quá hạn chưa cập nhật',
        'done' => 'Đã xong',
        'cancelled' => 'Đã huỷ / vắng',
    ];

    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'upcoming';
        $owner = $request->query('user');

        // Chỉ lịch hẹn của khách trong phạm vi
        $base = Appointment::query()
            ->whereHas('lead', fn (Builder $l) => $l->visibleTo($request->user()))
            ->when($owner === 'none', fn (Builder $q) => $q->whereNull('user_id'))
            ->when($owner && $owner !== 'none', fn (Builder $q) => $q->where('user_id', $owner));

        $counts = collect(self::TABS)->map(fn ($label, $key) => $this->applyTab(clone $base, $key)->count());

        $appointments = $this->applyTab(clone $base, $tab)
            ->with(['lead', 'consultant'])
            ->tap(fn (Builder $q) => in_array($tab, ['done', 'cancelled', 'overdue'], true)
                ? $q->orderByDesc('scheduled_at')
                : $q->orderBy('scheduled_at'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('crm::appointments.index', [
            'appointments' => $appointments,
            'groups' => $appointments->getCollection()->groupBy(fn (Appointment $a) => $a->scheduled_at->toDateString()),
            'tab' => $tab,
            'owner' => $owner,
            'counts' => $counts,
            'consultants' => $this->consultants(),
        ]);
    }

    public function create(Request $request)
    {
        $lead = $request->integer('lead') ? Lead::find($request->integer('lead')) : null;
        $lead = $lead && $request->user()->canSeeLead($lead) ? $lead : null;

        $appointment = new Appointment([
            'lead_id' => $lead?->id,
            'user_id' => $lead?->assigned_to ?? $request->user()->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'duration_minutes' => 30,
            'type' => 'call',
        ]);

        return view('crm::appointments.form', $this->formData($appointment));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $appointment = Appointment::create($data + ['created_by' => $request->user()->id, 'status' => 'scheduled']);
        $appointment->load('lead', 'consultant');

        $appointment->lead->log(
            'Đặt lịch hẹn: '.$appointment->summary()
            .($appointment->consultant ? ' (phụ trách: '.$appointment->consultant->displayName().')' : '')
            .($appointment->note ? ". Nội dung: {$appointment->note}" : '.'),
            'appointment'
        );

        return $this->backTo($request, $appointment, 'Đã đặt lịch hẹn '.$appointment->summary().'.');
    }

    public function edit(Appointment $appointment)
    {
        abort_unless($appointment->status === 'scheduled', 403, 'Chỉ đổi được lịch hẹn chưa diễn ra.');

        return view('crm::appointments.form', $this->formData($appointment));
    }

    public function update(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->status === 'scheduled', 403, 'Chỉ đổi được lịch hẹn chưa diễn ra.');

        $old = $appointment->summary();
        $appointment->update($this->validated($request, $appointment));

        if ($appointment->wasChanged(['scheduled_at', 'type'])) {
            $appointment->lead->log("Đổi lịch hẹn: {$old} → {$appointment->summary()}.", 'appointment');
        }

        return $this->backTo($request, $appointment, 'Đã cập nhật lịch hẹn.');
    }

    /** Cập nhật kết quả: hoàn thành (kèm kết quả) / khách vắng mặt / huỷ. */
    public function status(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['done', 'no_show', 'cancelled'])],
            'result' => ['nullable', 'string', 'max:2000'],
        ], [], ['result' => 'kết quả']);

        abort_unless($appointment->status === 'scheduled', 422, 'Lịch hẹn này đã được cập nhật trước đó.');

        $appointment->update($data);

        $verb = ['done' => 'Hoàn thành', 'no_show' => 'Khách vắng mặt', 'cancelled' => 'Huỷ'][$data['status']];
        $appointment->lead->log(
            "{$verb} lịch hẹn: {$appointment->summary()}".(! empty($data['result']) ? ". Kết quả: {$data['result']}" : '.'),
            'appointment'
        );

        return back()->with('success', "Đã cập nhật: {$verb} lịch hẹn với {$appointment->lead->full_name}.");
    }

    private function applyTab(Builder $q, string $tab): Builder
    {
        return match ($tab) {
            'today' => $q->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()]),
            'overdue' => $q->overdue(),
            'done' => $q->where('status', 'done'),
            'cancelled' => $q->whereIn('status', ['cancelled', 'no_show']),
            default => $q->upcoming(),
        };
    }

    private function validated(Request $request, ?Appointment $appointment = null): array
    {
        $data = $request->validate([
            'lead_id' => ['required', Rule::in(Lead::visibleTo($request->user())->pluck('id')->all())],
            'user_id' => ['nullable', Rule::in($this->consultants()->keys()->all())],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', Rule::in(array_keys(Appointment::DURATIONS))],
            'type' => ['required', Rule::in(array_keys(Appointment::TYPES))],
            'location' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'scheduled_at.after' => 'Thời gian hẹn phải sau thời điểm hiện tại.',
        ], [
            'lead_id' => 'khách hàng', 'user_id' => 'tư vấn viên', 'scheduled_at' => 'thời gian',
            'duration_minutes' => 'thời lượng', 'type' => 'hình thức', 'location' => 'địa điểm', 'note' => 'nội dung',
        ]);

        // Không cho một tư vấn viên có hai lịch chồng giờ
        $conflict = Appointment::conflictFor(
            isset($data['user_id']) ? (int) $data['user_id'] : null,
            Carbon::parse($data['scheduled_at']),
            (int) $data['duration_minutes'],
            $appointment?->id,
        );

        if ($conflict) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Tư vấn viên đã có lịch với '.$conflict->lead->full_name
                    .' từ '.$conflict->scheduled_at->format('H:i').' đến '.$conflict->endsAt()->format('H:i d/m/Y').'.',
            ]);
        }

        return $data;
    }

    private function formData(Appointment $appointment): array
    {
        return [
            'appointment' => $appointment,
            'leads' => Lead::visibleTo(auth()->user())->orderBy('full_name')->get(['id', 'full_name', 'phone', 'student_name'])
                ->mapWithKeys(fn (Lead $l) => [$l->id => $l->full_name.' · '.$l->phone.($l->student_name ? ' (HS: '.$l->student_name.')' : '')]),
            'consultants' => $this->consultants(),
            'returnTo' => request('return') === 'lead' ? 'lead' : null,
        ];
    }

    /** Sau khi lưu: quay về hồ sơ khách nếu đặt từ đó, ngược lại về trang Lịch hẹn. */
    private function backTo(Request $request, Appointment $appointment, string $message)
    {
        $url = $request->input('return') === 'lead'
            ? route('crm.leads.show', $appointment->lead_id).'#appointments'
            : route('crm.appointments.index');

        return redirect($url)->with('success', $message);
    }

    /** Tư vấn viên có thể nhận lịch hẹn — theo phạm vi của người đang thao tác. */
    private function consultants(): Collection
    {
        return auth()->user()->assignableUsers();
    }
}
