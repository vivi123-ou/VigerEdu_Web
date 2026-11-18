<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Một dòng liên kết ngành – nghề (bảng major_occupation) để sửa trên admin: thêm nghề cho ngành, đổi loại liên kết.
 *           Dòng mới tự đặt mã LNK… kế tiếp (mã cũ giữ nguyên theo dữ liệu nạp).
 * Ai gọi:   trang "Ngành học & nghề gợi ý" (ô "Nghề liên quan" khi sửa ngành), test.
 * Dữ liệu:  bảng major_occupation.
 */
#[Fillable(['code', 'major_id', 'occupation_id', 'link_type', 'routes', 'source', 'validation_status'])]
class MajorOccupationLink extends Model
{
    protected $table = 'major_occupation';

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            if (blank($link->code)) {
                $max = static::query()->where('code', 'like', 'LNK%')->pluck('code')
                    ->map(fn (string $code) => (int) substr($code, 3))->max() ?? 0;
                $link->code = 'LNK'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
            }
            $link->source ??= 'Admin';
        });
    }

    /**
     * @return BelongsTo<Major, $this>
     */
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class)->withTrashed();
    }
}
