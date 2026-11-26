<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Làm gì:   Cấu hình CRM dạng key => value (trang "Cấu hình CRM"). Chưa lưu thì dùng giá trị mặc định trong DEFAULTS.
 * Ai gọi:   CrmSetting::get('idle_days'); Dashboard, Báo cáo, Kanban, API nhận khách.
 * Dữ liệu:  bảng crm_settings, cache 'crm_settings' (xoá khi lưu).
 */
class CrmSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'idle_days' => 7,            // khách "cần chăm sóc lại" sau N ngày không có tương tác
        'closed_days' => 30,         // Kanban: cột đã chốt chỉ hiện khách cập nhật trong N ngày
        'auto_assign' => 'off',      // khách mới từ API: off | round_robin
        'auto_assign_role' => 'consultant',
        'auto_assign_last' => null,  // id người được chia gần nhất (nội bộ)
    ];

    public static function get(string $key): mixed
    {
        $all = Cache::rememberForever('crm_settings', fn () => static::pluck('value', 'key')->all());
        $value = $all[$key] ?? self::DEFAULTS[$key] ?? null;

        return is_int(self::DEFAULTS[$key] ?? null) ? (int) $value : $value;
    }

    /** @param array<string, mixed> $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        Cache::forget('crm_settings');
    }

    /**
     * Chia đều khách mới từ API (vòng tròn) cho nhân viên đang hoạt động có vai trò đã chọn.
     * Tắt hoặc không có ai phù hợp => null (khách vào hàng "chưa phân công").
     */
    public static function nextAssignee(): ?User
    {
        if (static::get('auto_assign') !== 'round_robin') {
            return null;
        }

        $users = User::where('active', true)->where('crm_role', static::get('auto_assign_role'))->orderBy('id')->get();
        if ($users->isEmpty()) {
            return null;
        }

        $last = (int) static::get('auto_assign_last');
        $next = $users->first(fn (User $u) => $u->id > $last) ?? $users->first();
        static::put(['auto_assign_last' => $next->id]);

        return $next;
    }
}
