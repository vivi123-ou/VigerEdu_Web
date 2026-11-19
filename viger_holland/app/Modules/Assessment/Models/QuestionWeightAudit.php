<?php

namespace App\Modules\Assessment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Lịch sử đổi trọng số rút đề của một câu: giá trị cũ, mới, lý do, người sửa.
 * Ai gọi:   trang Sửa câu hỏi (Filament) khi đổi con số ưu tiên.
 * Dữ liệu:  bảng question_weight_audits.
 */
#[Fillable(['question_id', 'old_weight', 'new_weight', 'reason', 'user_id'])]
class QuestionWeightAudit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_weight' => 'float',
            'new_weight' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
