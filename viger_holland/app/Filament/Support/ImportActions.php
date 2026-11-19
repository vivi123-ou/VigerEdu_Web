<?php

namespace App\Filament\Support;

use App\Models\AdminActivity;
use App\Support\SpreadsheetImport;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Làm gì:   Hai nút dùng chung cho mọi danh mục nhập được từ Excel:
 *           "Nhập từ Excel" (2 bước: tải file → ghép cột, máy đoán sẵn; xong báo thêm / cập nhật / bỏ qua và dòng nào lỗi, ghi lịch sử)
 *           và "Tải file mẫu" (.xlsx đúng cột, có một dòng ví dụ). Thêm loại nhập mới chỉ cần một lớp SpreadsheetImport.
 * Ai gọi:   SchoolsCatalog (trường, trường – ngành), MajorsCatalog (ngành).
 * Dữ liệu:  file tải lên (đĩa local, xóa ngay sau khi nhập); admin_activities.
 */
final class ImportActions
{
    /**
     * @param  class-string<SpreadsheetImport>  $importer
     * @param  list<Component>  $contextFields  ô thêm ở bước 1 (ví dụ chọn quốc gia)
     * @param  (Closure(array<string, mixed>): array<string, mixed>)|null  $context  dữ liệu bước 1 → ngữ cảnh cho importer
     */
    public static function import(string $name, string $what, string $importer, string $subject, array $contextFields = [], ?Closure $context = null): Action
    {
        return Action::make($name)->label('Nhập từ Excel')->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->modalHeading('Nhập '.$what.' từ Excel')
            ->steps([
                Step::make('Tải file')->description('File Excel (.xlsx) hoặc .csv, dòng đầu là tên cột. Chưa có file: bấm "Tải file mẫu" để lấy file đúng cột.')->schema([
                    ...$contextFields,
                    FileUpload::make('file')->label('File')->disk('local')->directory('imports')->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'text/plain'])
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) use ($importer): void {
                            $file = is_array($state) ? reset($state) : $state;
                            if (! $file instanceof TemporaryUploadedFile) {
                                return;
                            }
                            $import = app($importer);
                            $headers = $import->headers($file->getRealPath());
                            $set('headers', $headers);
                            foreach ($import->guess($headers) as $field => $column) {
                                $set('map.'.$field, $column);
                            }
                        }),
                    Hidden::make('headers'),
                ]),
                Step::make('Ghép cột')->description('Chọn cột trong file ứng với từng ô. Máy đã đoán sẵn, bạn kiểm tra lại. Cột không chọn được lưu ở phần thêm.')
                    ->schema(function (Get $get) use ($importer): array {
                        $import = app($importer);

                        return collect($import->fields())->map(fn (string $label, string $field) => Select::make('map.'.$field)
                            ->label($label)->required(in_array($field, $import->required(), true))
                            ->options(collect($get('headers') ?? [])->filter()->mapWithKeys(fn ($h) => [$h => $h])->all()))
                            ->values()->all();
                    })->columns(2),
            ])
            ->action(function (array $data) use ($importer, $context, $what, $subject): void {
                $path = Storage::disk('local')->path((string) $data['file']);
                try {
                    $stats = app($importer)->import($path, $data['map'] ?? [], $context ? $context($data) : []);
                } finally {
                    Storage::disk('local')->delete((string) $data['file']);
                }

                AdminActivity::record(AdminActivity::ACTION_IMPORT, $subject, null,
                    "Nhập {$what} từ Excel: thêm {$stats['created']}, cập nhật {$stats['updated']}, bỏ qua {$stats['skipped']}");

                $body = "Thêm {$stats['created']}, cập nhật {$stats['updated']}, bỏ qua {$stats['skipped']} dòng.";
                if ($stats['problems'] !== []) {
                    $body .= "\n".implode("\n", array_slice($stats['problems'], 0, 8)).(count($stats['problems']) > 8 ? "\n…" : '');
                }
                Notification::make()->title('Đã nhập xong')->body($body)
                    ->{$stats['skipped'] > 0 ? 'warning' : 'success'}()
                    ->persistent($stats['skipped'] > 0)
                    ->send();
            });
    }

    /**
     * @param  class-string<SpreadsheetImport>  $importer
     */
    public static function template(string $name, string $fileName, string $importer): Action
    {
        return Action::make($name)->label('Tải file mẫu')->icon(Heroicon::OutlinedDocumentArrowDown)->color('gray')
            ->action(fn (): BinaryFileResponse => response()->download(app($importer)->template(), $fileName)->deleteFileAfterSend());
    }
}
