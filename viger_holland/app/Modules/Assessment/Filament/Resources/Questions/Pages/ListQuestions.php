<?php

namespace App\Modules\Assessment\Filament\Resources\Questions\Pages;

use App\Modules\Assessment\Filament\Resources\Questions\QuestionResource;
use App\Modules\Assessment\Models\Question;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Làm gì:   Trang danh sách câu hỏi, có thẻ lọc nhanh theo 6 nhóm và chữ "Trang này làm gì?".
 * Ai gọi:   QuestionResource (đường dẫn /admin/questions).
 * Dữ liệu:  bảng questions.
 */
class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected ?string $subheading = QuestionResource::HELP;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Thêm câu hỏi'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $counts = Question::query()->pluck('group')->countBy();

        $tabs = ['all' => Tab::make('Tất cả')->badge($counts->sum())];

        foreach (QuestionResource::groupOptions() as $code => $label) {
            $tabs[$code] = Tab::make($label)
                ->badge($counts[$code] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('group', $code));
        }

        return $tabs;
    }
}
