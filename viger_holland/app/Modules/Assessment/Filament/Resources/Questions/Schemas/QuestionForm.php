<?php

namespace App\Modules\Assessment\Filament\Resources\Questions\Schemas;

use App\Modules\Assessment\Filament\Resources\Questions\QuestionResource;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\QuestionFacet;
use App\Modules\Assessment\Support\TestConfig;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Làm gì:   Form thêm / sửa một câu hỏi, chữ viết cho người không làm IT, có chú thích cạnh từng ô.
 * Ai gọi:   QuestionResource::form() (trang Thêm câu hỏi, Sửa câu).
 * Dữ liệu:  bảng questions, question_facets; con số ưu tiên mặc định theo mức đọc từ "Cách bốc đề & chấm điểm".
 */
class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        $isEditing = fn (string $operation): bool => $operation === 'edit';
        $weightChanged = fn (Get $get, ?Question $record): bool => $record !== null && (float) $get('selection_weight') !== (float) $record->selection_weight;

        return $schema->columns(1)->components([
            Section::make('Câu hỏi')
                ->columns(3)
                ->components([
                    Select::make('group')->label('Thuộc nhóm')
                        ->options(fn () => QuestionResource::groupOptions())
                        ->required()->live()
                        ->disabled($isEditing)
                        ->afterStateUpdated(fn (Set $set) => $set('question_facet_id', null))
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Câu này đo sở thích của nhóm nào. Chỉ chọn khi thêm câu mới; câu đã có không đổi nhóm.'),
                    Select::make('question_facet_id')->label('Chủ đề')
                        ->options(fn (Get $get) => QuestionFacet::query()->where('group', $get('group'))->orderBy('sort_order')->pluck('name', 'id'))
                        ->required()->searchable()
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Mỗi nhóm có 10 chủ đề. Đề bốc ra phải có ít nhất 4 chủ đề khác nhau mỗi nhóm.'),
                    Select::make('review_status')->label('Tình trạng')
                        ->options(Question::STATUS_LABELS)->required()
                        ->default(Question::STATUS_DRAFT)
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Chỉ câu "Đang dùng" được bốc vào đề. Câu mới để "Nháp" cho tới khi Thầy duyệt.'),
                    Textarea::make('text')->label('Câu khách đọc')
                        ->required()->maxLength(300)->rows(2)->columnSpanFull()
                        ->helperText('Viết ở ngôi "Tôi…", một ý, dễ hiểu với học sinh lớp 10. Người đã làm bài trước đó vẫn giữ câu cũ.'),
                ]),

            Section::make('Mức ưu tiên khi bốc đề')
                ->description('Câu ưu tiên cao được bốc vào đề thường xuyên hơn. Không ảnh hưởng điểm của học sinh.')
                ->columns(3)
                ->components([
                    Select::make('priority_tier')->label('Mức ưu tiên')
                        ->options(fn () => QuestionResource::tierOptions())
                        ->required()->default('B')->live()
                        ->afterStateUpdated(function (Set $set, string $operation, ?string $state): void {
                            if ($operation === 'create' && $state !== null) {
                                $set('selection_weight', TestConfig::get('tier_weight_'.strtolower($state)));
                            }
                        })
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Cao: câu đại diện rõ nhất cho nhóm. Vừa: câu thường. Thấp: câu bổ sung, ít được bốc hơn.'),
                    TextInput::make('selection_weight')->label('Con số ưu tiên')
                        ->numeric()->minValue(0)->maxValue(100)->step(0.5)->required()
                        ->default(fn () => TestConfig::get('tier_weight_b'))
                        ->live(onBlur: true)
                        ->helperText('Mặc định Cao = 5, Vừa = 3, Thấp = 1.')
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Số càng lớn càng hay được bốc. Đặt 0 thì câu gần như không được bốc.'),
                    Toggle::make('is_anchor')->label('Luôn có trong mọi đề')
                        ->inline(false)
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Chỉ có tác dụng khi bật "Dùng câu luôn có trong mọi đề" ở trang "Cách bốc đề & chấm điểm". Dùng khi Thầy cần so sánh các đề.'),
                    TextInput::make('weight_change_reason')->label('Vì sao đổi con số ưu tiên?')
                        ->dehydrated(false)->maxLength(500)->columnSpanFull()
                        ->visible($weightChanged)
                        ->required($weightChanged)
                        ->validationMessages(['required' => 'Vui lòng ghi lý do để sau này biết vì sao đề thay đổi.']),
                ]),

            Section::make('Ghi chú')
                ->columns(2)
                ->collapsible()
                ->collapsed($isEditing)
                ->components([
                    Textarea::make('review_note')->label('Ghi chú khi duyệt')->rows(2)->maxLength(1000)->columnSpanFull(),
                    TextInput::make('source_reference')->label('Nguồn tham khảo')->maxLength(255),
                    TextInput::make('item_version')->label('Phiên bản câu')
                        ->disabled()->dehydrated(false)->placeholder('1.0')
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Tự tăng mỗi khi sửa chữ của câu (1.0 → 1.1).'),
                ]),
        ]);
    }
}
