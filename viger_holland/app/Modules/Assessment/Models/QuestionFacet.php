<?php

namespace App\Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Chủ đề hoạt động trong một nhóm (10 chủ đề mỗi nhóm); rút đề yêu cầu đủ số chủ đề khác nhau.
 * Ai gọi:   QuestionPicker (H2); thư viện câu hỏi (H6); QuestionBankSeeder.
 * Dữ liệu:  bảng question_facets.
 */
#[Fillable(['code', 'group', 'name', 'sort_order'])]
class QuestionFacet extends Model
{
    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
