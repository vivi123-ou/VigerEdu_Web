<?php

namespace App\Modules\Assessment\Filament\Resources\Questions\Pages;

use App\Modules\Assessment\Filament\Resources\Questions\QuestionResource;
use App\Modules\Assessment\Models\Question;
use Filament\Resources\Pages\CreateRecord;

/**
 * Làm gì:   Thêm câu hỏi mới: tự đặt mã tiếp theo của nhóm (R051, R052…), phiên bản 1.0.
 * Ai gọi:   QuestionResource (nút "Thêm câu hỏi").
 * Dữ liệu:  bảng questions (đếm cả câu trong thùng rác để mã không trùng).
 */
class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [QuestionResource::helpAction()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $group = (string) $data['group'];
        $lastNumber = Question::withTrashed()->where('group', $group)->pluck('code')
            ->map(fn (string $code) => (int) substr($code, 1))
            ->max() ?? 0;

        return $data + [
            'code' => $group.str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT),
            'item_version' => '1.0',
            'language' => 'vi-VN',
            'last_reviewed_at' => now(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
