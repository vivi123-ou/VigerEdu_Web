<?php

namespace App\Modules\Assessment\Models;

use App\Modules\Respondent\Models\FieldValue;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Làm gì:   Một lượt làm bài: đề đã phát, đáp án, kết quả. Không chứa họ tên, SĐT, email.
 * Ai gọi:   Luồng làm bài (H3), ScoringService (H2), báo cáo, CrmSync (H5).
 * Dữ liệu:  bảng test_sessions, session_questions, responses, field_values.
 */
#[Fillable(['uuid', 'result_token', 'test_config_version_id', 'status', 'device_hash', 'generation_seed', 'generation_warnings', 'source', 'utm', 'scores', 'result', 'code2', 'code3', 'profile_case', 'needs_counseling', 'reliability', 'crm_status', 'started_at', 'answered_at', 'completed_at'])]
class TestSession extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation_warnings' => 'array',
            'utm' => 'array',
            'scores' => 'array',
            'result' => 'array',
            'needs_counseling' => 'boolean',
            'started_at' => 'datetime',
            'answered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TestConfigVersion, $this>
     */
    public function configVersion(): BelongsTo
    {
        return $this->belongsTo(TestConfigVersion::class, 'test_config_version_id');
    }

    /**
     * @return HasMany<SessionQuestion, $this>
     */
    public function sessionQuestions(): HasMany
    {
        return $this->hasMany(SessionQuestion::class)->orderBy('display_order');
    }

    /**
     * @return HasMany<Response, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * @return HasMany<FieldValue, $this>
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }
}
