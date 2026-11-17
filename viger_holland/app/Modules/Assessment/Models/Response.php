<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Đáp án một câu (1–5 sao) và thời gian trả lời.
 * Ai gọi:   Trang làm bài (H3) ghi; ScoringService (H2) đọc.
 * Dữ liệu:  bảng responses.
 */
#[Fillable(['test_session_id', 'question_id', 'value', 'ms'])]
class Response extends Model
{
    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }
}
