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
 * Làm gì:   Form thêm / sửa một câu hỏi, có chú thích cạnh từng ô.
 * Ai gọi:   QuestionResource::form() (trang Thêm câu, Sửa câu).
 * Dữ liệu:  bảng questions, question_facets; trọng số mặc định theo loại đọc từ Cài đặt bài test.
 */
class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        $isEditing = fn (string $operation): bool => $operation === 'edit';

        return $schema->columns(1)->components([
            Section::make('Nội dung câu')
                ->columns(3)
                ->components([
                    TextInput::make('code')->label('Mã câu')
                        ->disabled()->dehydrated(false)
                        ->placeholder('Tự đặt khi lưu')
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Phần mềm tự đặt theo nhóm (ví dụ R051). Không đổi được để lượt làm bài cũ vẫn khớp.'),
                    Select::make('group')->label('Nhóm')
                        ->options(fn () => QuestionResource::groupOptions())
                        ->required()->live()
                        ->disabled($isEditing)
                        ->afterStateUpdated(fn (Set $set) => $set('question_facet_id', null))
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Câu này đo sở thích của nhóm nào. Chỉ chọn khi thêm câu mới; câu đã có không đổi nhóm.'),
                    Select::make('question_facet_id')->label('Chủ đề')
                        ->options(fn (Get $get) => QuestionFacet::query()->where('group', $get('group'))->orderBy('sort_order')->pluck('name', 'id'))
                        ->required()->searchable()
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Mỗi nhóm có 10 chủ đề. Đề rút ra phải có ít nhất 4 chủ đề khác nhau mỗi nhóm.'),
                    Textarea::make('text')->label('Câu hỏi khách đọc')
                        ->required()->maxLength(300)->rows(2)->columnSpanFull()
                        ->helperText('Viết ở ngôi "Tôi…", một ý, dễ hiểu với học sinh. Sửa chữ thì phiên bản câu tự tăng; lượt làm bài cũ vẫn giữ câu cũ.'),
                ]),

            Section::make('Rút đề')
                ->description('Trọng số chỉ quyết định câu hay được rút vào đề đến mức nào, không dùng để tính điểm.')
                ->columns(3)
                ->components([
                    Select::make('priority_tier')->label('Loại câu')
                        ->options(fn () => QuestionResource::tierOptions())
                        ->required()->default('B')->live()
                        ->afterStateUpdated(function (Set $set, Get $get, string $operation, ?string $state): void {
                            if ($operation === 'create' && $state !== null) {
                                $set('selection_weight', TestConfig::get('tier_weight_'.strtolower($state)));
                            }
                        })
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'A – Lõi: câu đại diện rõ nhất cho nhóm. B – Chuẩn. C – Mở rộng: câu bổ sung, ít được rút hơn.'),
                    TextInput::make('selection_weight')->label('Trọng số')
                        ->numeric()->minValue(0)->maxValue(100)->step(0.5)->required()
                        ->default(fn () => TestConfig::get('tier_weight_b'))
                        ->live(onBlur: true)
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Mặc định A = 5, B = 3, C = 1 (đổi ở Cài đặt bài test). Đặt 0 thì câu gần như không được rút.'),
                    Toggle::make('is_anchor')->label('Câu neo')
                        ->inline(false)
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Câu neo luôn có trong mọi đề khi bật "Dùng câu neo" ở Cài đặt bài test. Chỉ dùng khi Thầy cần so sánh các đề.'),
                    TextInput::make('weight_change_reason')->label('Lý do đổi trọng số')
                        ->dehydrated(false)->maxLength(500)->columnSpanFull()
                        ->visible(fn (Get $get, ?Question $record) => $record !== null && (float) $get('selection_weight') !== (float) $record->selection_weight)
                        ->required(fn (Get $get, ?Question $record) => $record !== null && (float) $get('selection_weight') !== (float) $record->selection_weight)
                        ->helperText('Bắt buộc khi đổi trọng số, để sau này biết vì sao đề thay đổi.'),
                ]),

            Section::make('Duyệt')
                ->columns(2)
                ->components([
                    Select::make('review_status')->label('Trạng thái')
                        ->options(Question::STATUS_LABELS)->required()
                        ->default(Question::STATUS_DRAFT)
                        ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, 'Chỉ câu "Đã duyệt" được rút vào đề. Câu mới thêm để "Nháp" cho tới khi Thầy duyệt.'),
                    TextInput::make('item_version')->label('Phiên bản câu')
                        ->disabled()->dehydrated(false)->placeholder('1.0'),
                    Textarea::make('review_note')->label('Ghi chú duyệt')->rows(2)->maxLength(1000)->columnSpanFull(),
                    TextInput::make('source_reference')->label('Nguồn tham khảo')->maxLength(255)->columnSpanFull(),
                ]),
        ]);
    }
}
