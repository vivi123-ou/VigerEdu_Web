<?php

namespace App\Modules\Assessment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Một phiên bản cài đặt bài test; dòng mới nhất là bản đang dùng.
 * Ai gọi:   TestConfig, trang "Cách bốc đề & chấm điểm"; lượt làm bài ghi lại phiên bản đã dùng.
 * Dữ liệu:  bảng test_config_versions.
 */
#[Fillable(['settings', 'note', 'user_id'])]
class TestConfigVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
