<?php

namespace App\Modules\Assessment\Filament\Resources\Questions;

use App\Modules\Assessment\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\EditQuestion;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\ListQuestions;
use App\Modules\Assessment\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Modules\Assessment\Filament\Resources\Questions\Tables\QuestionsTable;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\RiasecType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Làm gì:   Trang "Thư viện câu hỏi": xem, lọc, sửa, duyệt / tạm ngưng, thêm câu mới, thùng rác.
 * Ai gọi:   Filament (khu quản trị /admin/questions). Quyền: questions.manage (QuestionPolicy).
 * Dữ liệu:  bảng questions, question_facets, question_weight_audits, riasec_types (tên nhóm).
 */
class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $slug = 'questions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Bài trắc nghiệm';

    protected static ?string $navigationLabel = 'Thư viện câu hỏi';

    protected static ?string $modelLabel = 'câu hỏi';

    protected static ?string $pluralModelLabel = 'câu hỏi';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    /** Chữ của nút "Trang này làm gì?" ở đầu trang danh sách. */
    public const HELP = 'Thư viện 300 câu: 6 nhóm × 50 câu, mỗi nhóm 10 chủ đề. Mỗi lượt làm bài, phần mềm rút ngẫu nhiên 6 câu mỗi nhóm trong số câu "Đã duyệt"; câu loại A (trọng số cao) hay được rút hơn câu loại C. Trọng số chỉ để rút đề, không ảnh hưởng điểm. Câu "Tạm ngưng" hoặc trong thùng rác không được rút; lượt làm bài cũ vẫn giữ nguyên.';

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
        return collect(Question::TIER_LABELS)->mapWithKeys(fn (string $label, string $tier) => [$tier => $tier.' – '.$label])->all();
    }
}
