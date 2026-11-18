<?php

namespace App\Modules\Content\Filament\Pages;

use App\Filament\Pages\ContentSettingsPage;
use App\Filament\Support\ContentFields;
use App\Models\AdminActivity;
use App\Support\ContentPreview;
use App\Support\IntroBlocks;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * Làm gì:   Trang "Trang mở đầu": thẻ "Banner" (tiêu đề, giới thiệu, nút, ảnh, báo cáo mẫu) và thẻ "Các khối nội dung"
 *           (thêm, sửa, kéo thả thứ tự, bật / tắt, xóa các khối dưới banner). Cả hai có "Xem trước" và lịch sử.
 *           Nút "Sửa trực tiếp trên trang" mở trang khách ở chế độ bấm-vào-chữ-để-sửa (PageEditorController).
 * Ai gọi:   Filament (/admin/trang-mo-dau). Quyền: content.manage.
 * Dữ liệu:  settings (nhóm intro, intro_blocks JSON qua App\Support\IntroBlocks); lịch sử ở admin_activities.
 */
class IntroPage extends ContentSettingsPage
{
    protected static ?string $slug = 'trang-mo-dau';

    protected static string $menuKey = 'intro';

    protected static string $contentKey = 'intro';

    /** @var array<string, mixed>|null */
    public ?array $blocksData = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liveEdit')->label('Sửa trực tiếp trên trang')->icon(Heroicon::OutlinedCursorArrowRays)
                ->url(route('admin.page-editor.show', 'trang-gioi-thieu')),
            Action::make('viewPage')->label('Xem trang khách')->color('gray')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(route('home'), shouldOpenInNewTab: true),
            static::helpAction(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('intro')->persistTabInQueryString('the')->tabs([
                Tab::make('Banner')->key('banner')->icon(Heroicon::OutlinedPhoto)->components([$this->settingsForm()]),
                Tab::make('Các khối nội dung')->key('khoi')->icon(Heroicon::OutlinedSquares2x2)->components([
                    Form::make([EmbeddedSchema::make('blocksForm')])
                        ->id('blocksForm')
                        ->livewireSubmitHandler('saveBlocks')
                        ->footer([Actions::make([
                            Action::make('saveBlocks')->label('Lưu các khối')->submit('saveBlocks'),
                            Action::make('previewBlocks')->label('Xem trước')->color('gray')->icon(Heroicon::OutlinedEye)
                                ->action(fn () => $this->previewBlocks()),
                        ])->sticky()]),
                ]),
            ]),
            $this->historySection(),
        ]);
    }

    public function blocksForm(Schema $schema): Schema
    {
        return $schema->statePath('blocksData')->components([
            Builder::make('blocks')
                ->hiddenLabel()
                ->helperText(new HtmlString('Các khối hiện dưới banner theo đúng thứ tự này. Kéo biểu tượng ⋮⋮ để đổi thứ tự; khối tắt "Hiện khối này" thì khách không thấy. Sửa xong bấm <strong>"Lưu các khối"</strong>.'))
                ->addActionLabel('Thêm khối')
                ->blockPickerColumns(1)
                ->collapsible()
                ->collapsed()
                ->cloneable(false)
                ->blocks(collect(IntroBlocks::TYPES)->map(fn (array $type, string $key) => $this->block($key))->values()->all()),
        ]);
    }

    public function saveBlocks(): void
    {
        abort_unless(static::canAccess(), 404);

        $blocks = $this->blocksFromState();
        $before = IntroBlocks::all();
        IntroBlocks::save($blocks, 'Sửa các khối trang mở đầu ('.count($blocks).' khối)');

        $this->fillBlocks();
        $this->resetTable();
        Notification::make()->success()
            ->title($before === IntroBlocks::all() ? 'Không có thay đổi nào' : 'Đã lưu các khối')
            ->body($before === IntroBlocks::all() ? null : 'Trang mở đầu hiện ngay nội dung mới.')
            ->send();
    }

    public function previewBlocks(): void
    {
        abort_unless(static::canAccess(), 404);

        $blocks = (string) json_encode($this->blocksFromState(), JSON_UNESCAPED_UNICODE);
        $this->openInNewTab(ContentPreview::put('home', [IntroBlocks::SETTING_KEY => $blocks]));
    }

    #[Unexposed]
    protected function historySubjects(): array
    {
        return [AdminActivity::SUBJECT_SETTINGS.'.intro', IntroBlocks::SUBJECT, AdminActivity::SUBJECT_GROUPS];
    }

    #[Unexposed]
    protected function fillForm(): void
    {
        parent::fillForm();
        $this->fillBlocks();
    }

    #[Unexposed]
    protected function fillBlocks(): void
    {
        $this->getSchema('blocksForm')->fill(['blocks' => array_map(fn (array $block) => [
            'type' => $block['type'],
            'data' => [...$block['data'], 'block_id' => $block['id'], 'enabled' => (bool) $block['enabled']],
        ], IntroBlocks::all())]);
    }

    /**
     * Trạng thái form → danh sách khối đã kiểm tra, đúng định dạng IntroBlocks.
     *
     * @return list<array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    #[Unexposed]
    protected function blocksFromState(): array
    {
        $state = $this->getSchema('blocksForm')->getState();
        $blocks = [];

        try {
            foreach (array_values((array) ($state['blocks'] ?? [])) as $item) {
                $data = (array) ($item['data'] ?? []);
                $blocks[] = [
                    'id' => filled($data['block_id'] ?? null) ? (string) $data['block_id'] : IntroBlocks::newId(),
                    'type' => (string) $item['type'],
                    'enabled' => (bool) ($data['enabled'] ?? false),
                    'data' => IntroBlocks::normalize((string) $item['type'], $data),
                ];
            }
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['blocksData.blocks' => collect($exception->errors())->flatten()->first()]);
        }

        return $blocks;
    }

    #[Unexposed]
    protected function block(string $type): Block
    {
        $label = IntroBlocks::typeLabel($type);

        $fields = match ($type) {
            'steps' => [
                Repeater::make('items')->label('Các bước')->addActionLabel('Thêm bước')->maxItems(8)->columns(2)->components([
                    TextInput::make('title')->label('Tên bước'),
                    Textarea::make('text')->label('Giải thích')->rows(2),
                ]),
                TextInput::make('note')->label('Dòng ghi chú dưới các bước'),
                Toggle::make('in_hero')->label('Đặt khối này cạnh banner (khi banner không có ảnh)'),
            ],
            'groups' => [
                Textarea::make('intro')->label('Đoạn giới thiệu')->rows(4)
                    ->helperText('Tên và mô tả từng nhóm: bấm "Sửa trực tiếp trên trang" ở đầu trang này.'),
            ],
            'text_image' => [
                ContentFields::rich('body')->label('Nội dung'),
                ContentFields::image('image')->label('Ảnh')->helperText('Không bắt buộc. Nên rộng khoảng 1000px.'),
                Grid::make(2)->components([
                    Select::make('image_side')->label('Vị trí ảnh')->native(false)->default('right')->selectablePlaceholder(false)
                        ->options(['right' => 'Bên phải chữ', 'left' => 'Bên trái chữ']),
                    TextInput::make('image_alt')->label('Mô tả ảnh (cho người khiếm thị và Google)'),
                    TextInput::make('button_text')->label('Chữ trên nút (không bắt buộc)'),
                    TextInput::make('button_url')->label('Địa chỉ nút')->placeholder('https://'),
                ]),
            ],
            'faq' => [
                Repeater::make('items')->label('Các câu hỏi')->addActionLabel('Thêm câu hỏi')->maxItems(30)->components([
                    TextInput::make('question')->label('Câu hỏi'),
                    ContentFields::rich('answer')->label('Câu trả lời'),
                ]),
            ],
            'testimonials' => [
                Repeater::make('items')->label('Các lời cảm nhận')->addActionLabel('Thêm lời cảm nhận')->maxItems(20)->columns(2)
                    ->helperText('Chỉ đăng lời nhận xét thật, đã được người nói đồng ý. Khối không có lời cảm nhận nào sẽ tự ẩn.')
                    ->components([
                        Textarea::make('quote')->label('Lời cảm nhận')->rows(2)->columnSpanFull(),
                        TextInput::make('name')->label('Tên người nói'),
                        TextInput::make('role')->label('Vai trò (ví dụ: Phụ huynh, Đà Nẵng)'),
                    ]),
            ],
            default => [],
        };

        return Block::make($type)
            ->label(function (?array $state) use ($label): string {
                if ($state === null) {
                    return $label;
                }
                $title = trim((string) ($state['title'] ?? '')) ?: '(chưa có tiêu đề)';

                return $title.' · '.$label.(($state['enabled'] ?? false) ? '' : ' · đang ẩn');
            })
            ->components([
                Hidden::make('block_id'),
                Toggle::make('enabled')->label('Hiện khối này trên trang mở đầu')->default(false),
                TextInput::make('title')->label('Tiêu đề khối')->default($label),
                ...$fields,
            ]);
    }
}
