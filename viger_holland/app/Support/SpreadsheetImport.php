<?php

namespace App\Support;

use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Làm gì:   Khung chung "Nhập từ Excel" có bước ghép cột cho mọi danh mục (trường, trường – ngành, ngành…):
 *           headers() đọc dòng tiêu đề; guess() tự đoán cột theo tên (bỏ dấu, Việt / Anh / Đức);
 *           import() đọc từng dòng → importRow() của lớp con trả 'created' / 'updated' / 'skipped' (kèm lý do);
 *           template() tạo file mẫu (.xlsx) để team điền đúng cột. Thêm loại nhập mới = một lớp con nhỏ.
 * Ai gọi:   SchoolImport, ProgramImport, MajorImport; App\Filament\Support\ImportActions (nút trên trang admin).
 * Dữ liệu:  file người dùng tải lên (đọc bằng openspout), không giữ lại file.
 */
abstract class SpreadsheetImport
{
    /**
     * Ô nhập được: mã => tên hiện ở bước ghép cột.
     *
     * @return array<string, string>
     */
    abstract public function fields(): array;

    /**
     * Tên cột hay gặp (đã bỏ dấu, chữ thường, nối bằng "_") cho từng ô.
     *
     * @return array<string, list<string>>
     */
    abstract protected function aliases(): array;

    /**
     * Ghi một dòng. $values: ô => giá trị đã làm sạch (null nếu trống); $extra: cột không ghép.
     *
     * @param  array<string, string|null>  $values
     * @param  array<string, string>  $extra
     * @param  array<string, mixed>  $context
     * @return array{0: 'created'|'updated'|'skipped', 1?: string}
     */
    abstract protected function importRow(array $values, array $extra, array $context): array;

    /**
     * Ô bắt buộc phải ghép cột.
     *
     * @return list<string>
     */
    public function required(): array
    {
        return [];
    }

    /**
     * Một dòng ví dụ cho file mẫu (theo thứ tự fields()).
     *
     * @return list<string>
     */
    public function example(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function headers(string $path): array
    {
        foreach ($this->rows($path) as $row) {
            return array_values(array_map(fn ($v) => trim((string) $v), $row));
        }

        return [];
    }

    /**
     * Ô => tên cột trong file (đoán theo tên cột).
     *
     * @param  list<string>  $headers
     * @return array<string, string|null>
     */
    public function guess(array $headers): array
    {
        $normalized = collect($headers)->mapWithKeys(fn (string $h) => [$h => self::slug($h)]);
        $labels = collect($this->fields())->map(fn (string $label) => self::slug($label));

        return collect($this->fields())->map(function (string $label, string $field) use ($normalized, $labels) {
            $aliases = [...($this->aliases()[$field] ?? []), $field, $labels[$field]];

            return $normalized->search(fn (string $n) => in_array($n, $aliases, true)) ?: null;
        })->all();
    }

    /**
     * @param  array<string, string|null>  $mapping  ô => tên cột
     * @param  array<string, mixed>  $context
     * @return array{created: int, updated: int, skipped: int, problems: list<string>}
     */
    public function import(string $path, array $mapping, array $context = []): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'problems' => []];
        $headers = null;
        $line = 0;

        foreach ($this->rows($path) as $row) {
            $line++;
            if ($headers === null) {
                $headers = array_map(fn ($v) => trim((string) $v), $row);

                continue;
            }
            $record = array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null));
            if (collect($record)->every(fn ($v) => self::clean($v) === null)) {
                continue;
            }

            $values = [];
            foreach (array_keys($this->fields()) as $field) {
                $values[$field] = filled($mapping[$field] ?? null) ? self::clean($record[$mapping[$field]] ?? null) : null;
            }
            $used = array_filter($mapping);
            $extra = collect($record)->except($used)->reject(fn ($v, $k) => $k === '')
                ->map(fn ($v) => self::clean($v))->filter(fn ($v) => $v !== null)->all();

            [$result, $reason] = $this->importRow($values, $extra, $context) + [1 => null];
            $stats[$result]++;
            if ($result === 'skipped' && $reason !== null && count($stats['problems']) < 20) {
                $stats['problems'][] = "Dòng {$line}: {$reason}";
            }
        }

        return $stats;
    }

    /**
     * Tạo file mẫu .xlsx (dòng tiêu đề + một dòng ví dụ), trả đường dẫn file tạm.
     */
    public function template(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mau').'.xlsx';
        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_values($this->fields())));
        if ($this->example() !== []) {
            $writer->addRow(Row::fromValues($this->example()));
        }
        $writer->close();

        return $path;
    }

    public static function clean(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public static function truthy(?string $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['có', 'co', 'x', 'yes', 'ja', '1', 'true', 'rồi', 'roi', 'đã kiểm tra'], true);
    }

    public static function slug(string $text): string
    {
        return Str::of(Str::ascii($text))->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    /**
     * @return \Generator<int, list<mixed>>
     */
    private function rows(string $path): \Generator
    {
        $reader = str_ends_with(strtolower($path), '.csv') ? new CsvReader : new XlsxReader;
        $reader->open($path);
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    yield $row->toArray();
                }
                break;
            }
        } finally {
            $reader->close();
        }
    }
}
