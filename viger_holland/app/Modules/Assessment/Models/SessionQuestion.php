<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Làm gì:   Một câu đã phát trong lượt làm bài, giữ nội dung và phiên bản lúc phát.
 * Ai gọi:   QuestionPicker (H2) ghi; báo cáo nội bộ đọc.
 * Dữ liệu:  bảng session_questions.
 */
#[Fillable(['test_session_id', 'question_id', 'group', 'item_version', 'text_snapshot', 'display_order'])]
class SessionQuestion extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }
}
