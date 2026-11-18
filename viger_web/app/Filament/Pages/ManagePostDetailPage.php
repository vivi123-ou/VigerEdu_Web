<?php

namespace App\Filament\Pages;

use App\Models\Post;
use App\Models\Setting;
use App\Support\PostDetailPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManagePostDetailPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Trang Chi tiết bài viết';

    protected static ?string $title = 'Quản trị giao diện trang Chi tiết bài viết';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung Website';

    protected static ?string $slug = 'trang-chi-tiet-bai-viet';

    protected static ?int $navigationSort = 40;

    /** Ảnh upload: điền giá trị thô (không điền ảnh mặc định vào FileUpload). */
    private const IMAGE_KEYS = ['post_detail_author_box_avatar', 'post_detail_related_placeholder'];

    private const SECTION_LABELS = [
        'post_detail_author_name' => 'Chi tiết bài viết › Tác giả',
        'post_detail_show_author_box' => 'Chi tiết bài viết › Hộp tác giả',
        'post_detail_author_box_' => 'Chi tiết bài viết › Hộp tác giả',
        'post_detail_show_related' => 'Chi tiết bài viết › Bài liên quan',
        'post_detail_related_' => 'Chi tiết bài viết › Bài liên quan',
        'post_detail_show_cta' => 'Chi tiết bài viết › CTA',
        'post_detail_cta_' => 'Chi tiết bài viết › CTA',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $data = PostDetailPage::all();

        foreach (self::IMAGE_KEYS as $key) {
            $data[$key] = Setting::get($key);
        }

        foreach (PostDetailPage::TOGGLE_KEYS as $key) {
            $data[$key] = (bool) (int) $data[$key];
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Thông tin dưới tiêu đề bài viết')
                    ->description('Nội dung, ảnh và chuyên mục của từng bài viết được quản trị ở mục Bài viết.')
                    ->schema([
                        TextInput::make('post_detail_author_name')
                            ->label('Tên tác giả hiển thị')
                            ->maxLength(100),
                    ]),

                Section::make('Hộp tác giả cuối bài')
                    ->schema([
                        Toggle::make('post_detail_show_author_box')->label('Hiển thị')->live()->columnSpanFull(),
                        TextInput::make('post_detail_author_box_name')
                            ->label('Tên')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('post_detail_show_author_box')),
                        FileUpload::make('post_detail_author_box_avatar')
                            ->label('Ảnh đại diện')
                            ->helperText('Bỏ trống để dùng logo mặc định.')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('post-detail')
                            ->visible(fn (Get $get) => $get('post_detail_show_author_box')),
                        Textarea::make('post_detail_author_box_bio')
                            ->label('Giới thiệu')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('post_detail_show_author_box')),
                    ])->columns(2),

                Section::make('Bài viết liên quan (sidebar)')
                    ->description('Lấy các bài cùng chuyên mục; nếu không có thì lấy các bài mới nhất.')
                    ->schema([
                        Toggle::make('post_detail_show_related')->label('Hiển thị')->live()->columnSpanFull(),
                        TextInput::make('post_detail_related_title')
                            ->label('Tiêu đề khối')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('post_detail_show_related')),
                        TextInput::make('post_detail_related_count')
                            ->label('Số bài hiển thị')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(10)
                            ->visible(fn (Get $get) => $get('post_detail_show_related')),
                        FileUpload::make('post_detail_related_placeholder')
                            ->label('Ảnh thay thế')
                            ->helperText('Dùng cho bài viết không có ảnh đại diện. Bỏ trống để dùng ảnh mặc định.')
                            ->image()
                            ->disk('public')
                            ->directory('post-detail')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('post_detail_show_related')),
                    ])->columns(2),

                Section::make('Hộp kêu gọi tư vấn (sidebar)')
                    ->schema([
                        Toggle::make('post_detail_show_cta')->label('Hiển thị')->live()->columnSpanFull(),
                        TextInput::make('post_detail_cta_title')
                            ->label('Tiêu đề')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('post_detail_show_cta')),
                        Textarea::make('post_detail_cta_description')
                            ->label('Mô tả')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('post_detail_show_cta')),
                        TextInput::make('post_detail_cta_button_text')
                            ->label('Nút: chữ')
                            ->maxLength(100)
                            ->visible(fn (Get $get) => $get('post_detail_show_cta')),
                        TextInput::make('post_detail_cta_button_url')
                            ->label('Nút: liên kết')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('post_detail_show_cta')),
                    ])->columns(2),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        $samplePost = Post::published()->latest()->first();

        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Lưu thay đổi')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('view')
                            ->label('Xem thử một bài viết')
                            ->color('gray')
                            ->url($samplePost ? url('/bai-viet/'.$samplePost->slug) : url('/blog'), shouldOpenInNewTab: true),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (PostDetailPage::TOGGLE_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = ! empty($data[$key]) ? '1' : '0';
            }
        }

        foreach (array_keys(PostDetailPage::DEFAULTS) as $key) {
            // Trường bị ẩn (khối đang tắt) không có trong state => giữ nguyên giá trị cũ
            if (! array_key_exists($key, $data)) {
                continue;
            }

            Setting::set(
                $key,
                $data[$key],
                'post_detail',
                in_array($key, self::IMAGE_KEYS, true) ? 'image' : 'text',
                $this->labelFor($key),
            );
        }

        Notification::make()->success()->title('Đã lưu giao diện trang Chi tiết bài viết.')->send();
    }

    private function labelFor(string $key): string
    {
        foreach (self::SECTION_LABELS as $prefix => $label) {
            if (str_starts_with($key, $prefix)) {
                return $label.' ('.$key.')';
            }
        }

        return $key;
    }
}
