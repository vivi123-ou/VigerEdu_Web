<?php

namespace App\Filament\Support;

use App\Models\Media;
use App\Services\ImageUploader;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

/**
 * Làm gì:   Dựng ô Filament cho từng ô khai báo trong config/admin_content.php (chữ, đoạn văn, ảnh, màu, font, link…)
 *           và ô "Chọn ảnh" dùng chung: chọn ảnh trong Kho ảnh (có hình nhỏ) hoặc tải ảnh mới ngay tại chỗ.
 *           Kiểm tra dữ liệu thật nằm ở App\Support\ContentPages::validate (báo lỗi tiếng Việt), ô ở đây chỉ để nhập.
 * Ai gọi:   ContentSettingsPage, IntroPage (khối Chữ và ảnh).
 * Dữ liệu:  bảng media (danh sách ảnh để chọn).
 */
final class ContentFields
{
    /**
     * @param  array{key: string, label: string, type: string, hint?: string, required?: bool}  $field
     */
    public static function make(array $field): Component
    {
        $key = $field['key'];

        $component = match ($field['type']) {
            'textarea' => Textarea::make($key)->rows(3)->autosize(),
            'domains' => Textarea::make($key)->rows(4)->placeholder('https://viger.edu.vn'),
            'rich' => self::rich($key),
            'color' => ColorPicker::make($key),
            'font' => Select::make($key)->native(false)
                ->options(collect(config('holland.fonts'))->mapWithKeys(fn ($value, string $font) => [$font => $font])->all()),
            'image' => self::image($key),
            'url' => TextInput::make($key)->placeholder('https://')->prefixIcon(Heroicon::OutlinedLink),
            'email' => TextInput::make($key)->prefixIcon(Heroicon::OutlinedEnvelope),
            'links' => Repeater::make($key)->columns(3)->addActionLabel('Thêm link')->defaultItems(0)->components([
                TextInput::make('label')->label('Chữ hiển thị'),
                TextInput::make('url')->label('Địa chỉ')->placeholder('https://'),
                Toggle::make('new_tab')->label('Mở ở thẻ mới')->inline(false),
            ]),
            default => TextInput::make($key),
        };

        return $component->label($field['label'])
            ->helperText($field['hint'] ?? null)
            ->markAsRequired($field['required'] ?? false)
            ->columnSpanFull();
    }

    public static function rich(string $key): RichEditor
    {
        return RichEditor::make($key)->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']]);
    }

    /**
     * Ô "Chọn ảnh": danh sách ảnh trong Kho ảnh kèm hình nhỏ; nút bên cạnh để tải ảnh mới (tự thu nhỏ, nén) và chọn luôn.
     */
    public static function image(string $key): Select
    {
        return Select::make($key)
            ->native(false)
            ->searchable()
            ->allowHtml()
            ->placeholder('Chưa chọn ảnh')
            ->options(fn (?string $state) => self::imageOptions($state))
            ->suffixAction(
                Action::make('upload')
                    ->label('Tải ảnh mới')
                    ->tooltip('Tải ảnh mới từ máy')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->modalHeading('Tải ảnh mới')
                    ->modalSubmitActionLabel('Tải lên và chọn')
                    ->modalWidth('md')
                    ->schema([
                        FileUpload::make('file')->label('Ảnh')->image()->required()->storeFiles(false)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])->maxSize(8192)
                            ->helperText('JPG, PNG, WebP hoặc GIF, tối đa 8 MB. Ảnh rộng hơn 1920px được tự thu nhỏ.'),
                    ])
                    ->action(function (array $data, Select $component): void {
                        $component->state(self::upload($data['file'])->path);
                    }),
            );
    }

    /**
     * @throws RuntimeException khi ảnh hỏng / không đọc được
     */
    public static function upload(TemporaryUploadedFile $file): Media
    {
        return app(ImageUploader::class)->store($file);
    }

    /**
     * @return array<string, string>
     */
    public static function imageOptions(?string $current = null): array
    {
        $options = Media::query()->latest('id')->limit(200)->get()
            ->mapWithKeys(fn (Media $media) => [$media->path => self::thumb($media->url(), $media->original_name)])
            ->all();

        if (filled($current) && ! array_key_exists($current, $options)) {
            $options = [$current => self::thumb(media_url($current), basename($current))] + $options;
        }

        return $options;
    }

    private static function thumb(string $url, string $name): string
    {
        return '<span style="display:inline-flex;align-items:center;gap:.6rem">'
            .'<img src="'.e($url).'" alt="" style="width:56px;height:36px;object-fit:cover;border-radius:4px;background:#f3f4f6">'
            .'<span>'.e($name).'</span></span>';
    }
}
