<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmRole;
use App\Models\CrmSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Trang "Cấu hình CRM": ngưỡng chăm sóc lại, số ngày hiện khách đã chốt trên Kanban, tự chia khách mới
 *           từ API; xem tình trạng các kênh kết nối (bật / có token / lần nhận gần nhất — chỉ đọc, cấu hình ở .env).
 * Ai gọi:   route crm.settings.* (quyền settings.manage).
 * Dữ liệu:  ghi crm_settings; đọc config('crm.api_channels'), crm_intake_events.
 */
class SettingController extends Controller
{
    private const CHANNEL_LABELS = ['website' => 'Website VIGER', 'chatbot' => 'AI Chatbot', 'holland' => 'Holland'];

    public function index()
    {
        $last = DB::table('crm_intake_events')->selectRaw('channel, max(created_at) as last_at, count(*) as total')
            ->groupBy('channel')->get()->keyBy('channel');

        $channels = collect(config('crm.api_channels'))->map(fn ($c) => [
            'label' => self::CHANNEL_LABELS[$c['code']] ?? $c['code'],
            'code' => $c['code'],
            'enabled' => $c['enabled'],
            'has_token' => ! empty($c['token_hash']),
            'last_at' => isset($last[$c['code']]) ? Carbon::parse($last[$c['code']]->last_at) : null,
            'total' => (int) ($last[$c['code']]->total ?? 0),
        ]);

        $roles = CrmRole::where('is_system', false)->orderBy('id')->pluck('name', 'key');
        $roleCounts = User::where('active', true)->selectRaw('crm_role, count(*) as total')->groupBy('crm_role')->pluck('total', 'crm_role');

        return view('crm::settings.index', [
            'settings' => collect(array_keys(CrmSetting::DEFAULTS))->mapWithKeys(fn ($k) => [$k => CrmSetting::get($k)]),
            'channels' => $channels,
            'roles' => $roles,
            'roleCounts' => $roleCounts,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'idle_days' => ['required', 'integer', 'between:1,90'],
            'closed_days' => ['required', 'integer', 'between:1,365'],
            'auto_assign' => ['required', Rule::in(['off', 'round_robin'])],
            'auto_assign_role' => ['required', Rule::exists('crm_roles', 'key')->where('is_system', false)],
        ], [], [
            'idle_days' => 'số ngày chăm sóc lại',
            'closed_days' => 'số ngày hiện khách đã chốt',
            'auto_assign' => 'tự chia khách',
            'auto_assign_role' => 'vai trò nhận khách',
        ]);

        // Ghi nhật ký bằng tên dễ hiểu, không dùng mã cấu hình
        $names = [
            'idle_days' => 'Nhắc chăm sóc lại sau (ngày)',
            'closed_days' => 'Kanban: hiện khách đã chốt trong (ngày)',
            'auto_assign' => 'Phân công tự động',
            'auto_assign_role' => 'Chia cho vai trò',
        ];
        $show = fn (string $k, $v) => match ($k) {
            'auto_assign' => $v === 'round_robin' ? 'Chia đều lần lượt' : 'Tắt',
            'auto_assign_role' => CrmRole::where('key', $v)->value('name') ?? $v,
            default => (string) $v,
        };
        $changes = collect($data)->filter(fn ($v, $k) => (string) CrmSetting::get($k) !== (string) $v)
            ->map(fn ($v, $k) => $names[$k].': '.$show($k, CrmSetting::get($k)).' → '.$show($k, $v));
        CrmSetting::put($data);
        if ($changes->isNotEmpty()) {
            CrmAuditLog::record('settings.update', 'Đổi cấu hình CRM — '.$changes->implode(', ').'.');
        }

        return redirect()->route('crm.settings.index')->with('success', 'Đã lưu cấu hình CRM.');
    }
}
