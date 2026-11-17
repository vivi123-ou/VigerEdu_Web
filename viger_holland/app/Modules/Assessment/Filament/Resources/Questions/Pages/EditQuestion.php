<?php

namespace App\Modules\Assessment\Filament\Resources\Questions\Pages;

use App\Modules\Assessment\Filament\Resources\Questions\QuestionResource;
use App\Modules\Assessment\Models\Question;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Làm gì:   Sửa một câu hỏi. Đổi chữ thì tăng phiên bản câu (1.0 → 1.1); đổi trọng số thì ghi lại lý do và người đổi.
 * Ai gọi:   QuestionResource (nút "Sửa" trên bảng).
 * Dữ liệu:  bảng questions, question_weight_audits.
 */
class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Sửa câu '.$this->getRecord()->code;
    }

    protected function getHeaderActions(): array
    {
        return [
            QuestionResource::helpAction(),
            DeleteAction::make()->label('Bỏ vào thùng rác'),
            RestoreAction::make()->label('Khôi phục'),
        ];
    }

    /**
     * @param  Question  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $reason = trim((string) ($this->data['weight_change_reason'] ?? ''));

        return DB::transaction(function () use ($record, $data, $reason): Question {
            $oldWeight = (float) $record->selection_weight;
            $record->fill($data);

            if ($record->isDirty('text')) {
                $record->item_version = self::nextVersion((string) $record->getOriginal('item_version'));
            }

            if ($record->isDirty('review_status')) {
                $record->last_reviewed_at = now();
            }

            $record->save();

            if ((float) $record->selection_weight !== $oldWeight) {
                $record->weightAudits()->create([
                    'old_weight' => $oldWeight,
                    'new_weight' => $record->selection_weight,
                    'reason' => $reason,
                    'user_id' => auth()->id(),
                ]);
            }

            return $record;
        });
    }

    /**
     * "1.0" → "1.1", "1.9" → "1.10"; giá trị lạ thì về "1.1".
     */
    public static function nextVersion(string $version): string
    {
        return preg_match('/^(\d+)\.(\d+)$/', $version, $match) === 1
            ? $match[1].'.'.((int) $match[2] + 1)
            : '1.1';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
