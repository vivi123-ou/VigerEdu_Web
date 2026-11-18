<?php

namespace App\Modules\Assessment\Filament\Resources\Questions;

use App\Filament\Concerns\UsesAdminMenu;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\EditQuestion;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\ListQuestions;
use App\Modules\Assessment\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Modules\Assessment\Filament\Resources\Questions\Tables\QuestionsTable;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\RiasecType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Làm gì:   Trang "Câu hỏi trắc nghiệm": xem, lọc, sửa, dùng / tạm ngưng, thêm câu mới, thùng rác.
 * Ai gọi:   Filament (khu quản trị /admin/questions). Quyền: questions.manage (QuestionPolicy).
 * Dữ liệu:  bảng questions, question_facets, question_weight_audits, riasec_types (tên nhóm).
 */
class QuestionResource extends Resource
{
    use UsesAdminMenu;

    protected static ?string $model = Question::class;

    protected static ?string $slug = 'questions';

    protected static string $menuKey = 'questions';

    protected static ?string $modelLabel = 'câu hỏi';

    protected static ?string $pluralModelLabel = 'câu hỏi';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Question>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Tên 6 nhóm lấy từ danh mục (admin sửa được), ví dụ "R – Thực hành".
     *
     * @return array<string, string>
     */
    public static function groupOptions(): array
    {
        return once(fn () => RiasecType::query()->orderBy('hex_index')->get()
            ->mapWithKeys(fn (RiasecType $type) => [$type->code => $type->code.' – '.$type->name])
            ->all());
    }

    /**
     * @return array<string, string>
     */
    public static function tierOptions(): array
    {
        return collect(Question::TIER_LABELS)->mapWithKeys(fn (string $label, string $tier) => [$tier => $label.' ('.$tier.')'])->all();
    }
}
