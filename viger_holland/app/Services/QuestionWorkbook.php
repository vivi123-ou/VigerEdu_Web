<?php

namespace App\Services;

use App\Enums\AnswerType;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Support\ChoiceList;
use App\Support\LeadFields;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Options as ReaderOptions;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * File Excel bộ câu hỏi, đúng cột tab CAU_HOI của docs/3_Mau_GoogleSheet_Viger_Holland_v2.xlsx.
 *
 * Xuất: tab CAU_HOI (Phần A + Phần B của một bộ) + tab HUONG_DAN.
 * Nhập: đọc → xem trước (lỗi theo dòng, câu thêm / sửa / tắt) → áp dụng. Khớp theo Mã câu;
 * câu không có trong file chỉ bị tắt, không bị xóa. Dòng nào lỗi thì không ghi gì cả.
 */
final class QuestionWorkbook
{
    public const SHEET = 'CAU_HOI';

    /** Đúng thứ tự cột tab CAU_HOI. */
    public const COLUMNS = [
        'Mã câu', 'Phần', 'Loại', 'Nội dung câu hỏi', 'Kiểu trả lời', 'Lựa chọn / Điểm', 'Ghi vào cột',
        'Dùng chấm xếp loại?', 'Nhóm RIASEC / Đối tượng', 'Thứ tự hiển thị', 'is_active', 'AI cần thu',
        'Bắt buộc trước khi chuyển tư vấn',
    ];

    private const SEPARATOR = ' | ';

    private const RIASEC = ['R', 'I', 'A', 'S', 'E', 'C'];

    /** Cách gõ khác của kiểu trả lời (theo file mẫu) => kiểu. */
    private const TYPE_ALIASES = [
        'sđt' => AnswerType::Phone,
        'ôtick' => AnswerType::Consent,
        'số0-10' => AnswerType::Number,
        'chọn' => AnswerType::Single,
        'chọn/chữ' => AnswerType::Single,
        'có/không' => AnswerType::Boolean,
        'đúng/sai' => AnswerType::YesNo,
    ];

    // ------------------------------------------------------------------ Xuất

    /**
     * Ghi file .xlsx của một bộ câu, trả về đường dẫn file tạm.
     */
    public function export(QuestionSet $set): string
    {
        $path = storage_path('app/private/exports/'.Str::uuid().'.xlsx');
        @mkdir(dirname($path), 0775, true);

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName(self::SHEET);

        $header = (new Style)->withFontBold(true);
        $writer->addRow(Row::fromValuesWithStyle(self::COLUMNS, $header));

        $info = Question::part(Question::PART_INFO)->ordered()->get();
        $holland = $set->questions()->orderBy('code')->get();

        foreach ($info as $question) {
            $writer->addRow(Row::fromValues($this->infoRow($question)));
        }

        foreach ($holland as $question) {
            $writer->addRow(Row::fromValues($this->hollandRow($question, $set)));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('HUONG_DAN');
        foreach ($this->guideLines($set) as $line) {
            $writer->addRow(Row::fromValues([$line]));
        }

        $writer->close();

        $this->addDropdowns($path, $info->count() + $holland->count() + 200);

        return $path;
    }

    /**
     * @return list<string|int>
     */
    private function infoRow(Question $question): array
    {
        return [
            $question->code, 'A', 'Thu thập', $question->text, $question->answer_type->label(),
            ChoiceList::format($question->options, self::SEPARATOR), (string) $question->crmKey(), 'Không', 'ALL',
            $question->sort_order, $question->is_active ? 1 : 0, '', $question->is_required ? 'Có' : 'Không',
        ];
    }

    /**
     * @return list<string|int>
     */
    private function hollandRow(Question $question, QuestionSet $set): array
    {
        return [
            $question->code, 'B', 'Holland', $question->text, ($question->answer_type ?? $set->answer_type)->label(),
            ChoiceList::format($question->scale($set), self::SEPARATOR), '', 'Không', (string) $question->riasec,
            $question->sort_order, $question->is_active ? 1 : 0, '', '',
        ];
    }

    /**
     * @return list<string>
     */
    private function guideLines(QuestionSet $set): array
    {
        return [
            'HƯỚNG DẪN SỬA FILE CÂU HỎI – bộ "'.$set->name.'" (mã '.$set->code.')',
            '',
            'Sửa ở tab CAU_HOI rồi vào trang quản trị → "Xuất / nhập Excel" → "Nhập bộ câu hỏi". Trước khi lưu luôn có bước xem trước.',
            'Mã câu: giữ nguyên để sửa câu cũ. Thêm câu mới: để trống ô Mã câu (hệ thống tự đặt mã).',
            'Phần: A = form thông tin khách; B = câu Holland. Câu B mới được thêm vào bộ "'.$set->name.'".',
            'Kiểu trả lời: chọn trong danh sách sổ xuống.',
            'Lựa chọn / Điểm: các lựa chọn ngăn cách bằng " | ". Dạng "Nhãn = giá trị", ví dụ: Đúng = 1 | Không = 0. Câu B để trống = theo thang của bộ.',
            'Ghi vào cột (chỉ câu A): tên trường CRM (ví dụ phone, email) hoặc extra_fields.ma_moi cho trường tùy biến. Để trống = không gửi CRM.',
            'Nhóm RIASEC / Đối tượng: câu B ghi R, I, A, S, E hoặc C; câu A ghi ALL.',
            'is_active: 1 = hiện trên web, 0 = ẩn. Xóa dòng khỏi file thì câu đó chỉ bị TẮT, không bị xóa.',
            'Bắt buộc trước khi chuyển tư vấn (chỉ câu A): Có = khách bắt buộc trả lời.',
            'Các cột "Loại", "Dùng chấm xếp loại?", "AI cần thu" giữ cho giống Google Sheet mẫu, hệ thống không dùng.',
            '',
            'Trường CRM chuẩn: '.implode(', ', LeadFields::selectable()),
        ];
    }

    /**
     * openspout chưa hỗ trợ ô chọn sổ xuống: chèn <dataValidations> vào XML của tab CAU_HOI.
     */
    private function addDropdowns(string $path, int $lastRow): void
    {
        $lists = [
            'B' => ['A', 'B'],
            'E' => array_values(array_unique(array_map(fn (AnswerType $type) => $type->label(), AnswerType::cases()))),
            'I' => [...self::RIASEC, 'ALL'],
            'K' => ['1', '0'],
            'M' => ['Có', 'Không'],
        ];

        $xml = '<dataValidations count="'.count($lists).'">';
        foreach ($lists as $column => $values) {
            $formula = htmlspecialchars('"'.implode(',', $values).'"', ENT_XML1);
            $xml .= '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Giá trị không hợp lệ" error="Vui lòng chọn trong danh sách." sqref="'.$column.'2:'.$column.$lastRow.'"><formula1>'.$formula.'</formula1></dataValidation>';
        }
        $xml .= '</dataValidations>';

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return;
        }

        $entry = 'xl/worksheets/sheet1.xml';
        $sheet = $zip->getFromName($entry);

        if (is_string($sheet) && ! str_contains($sheet, '<dataValidations')) {
            // Theo thứ tự của chuẩn OOXML: dataValidations đứng trước hyperlinks / printOptions / pageMargins…
            $anchor = null;
            foreach (['<hyperlinks', '<printOptions', '<pageMargins', '<pageSetup', '<headerFooter', '<drawing', '<legacyDrawing', '<tableParts', '</worksheet>'] as $tag) {
                $position = strpos($sheet, $tag);
                if ($position !== false && ($anchor === null || $position < $anchor)) {
                    $anchor = $position;
                }
            }

            if ($anchor !== null) {
                $zip->addFromString($entry, substr_replace($sheet, $xml, $anchor, 0));
            }
        }

        $zip->close();
    }

    // ------------------------------------------------------------------ Nhập

    /**
     * Đọc file và dựng bản xem trước. Không ghi gì vào database.
     *
     * @return array{file_errors: list<string>, rows: list<array<string, mixed>>, deactivate: list<array{code: string, text: string}>, counts: array{create: int, update: int, unchanged: int, deactivate: int, errors: int}}
     */
    public function preview(string $path, QuestionSet $set): array
    {
        $result = ['file_errors' => [], 'rows' => [], 'deactivate' => [], 'counts' => ['create' => 0, 'update' => 0, 'unchanged' => 0, 'deactivate' => 0, 'errors' => 0]];

        try {
            $sheetRows = $this->readSheet($path);
        } catch (Throwable $exception) {
            $result['file_errors'][] = $exception instanceof RuntimeException ? $exception->getMessage() : 'Không đọc được file. Vui lòng lưu lại dưới dạng Excel (.xlsx) rồi thử lại.';

            return $result;
        }

        $existing = Question::withTrashed()->get()->keyBy('code');
        $seenCodes = [];
        $newCodeOffset = ['A' => 0, 'B' => 0];

        foreach ($sheetRows as $line => $cells) {
            $row = $this->parseRow($line, $cells, $set, $existing, $seenCodes);

            if ($row['errors'] === [] && $row['action'] === 'create') {
                $row['code'] = $row['code'] ?: Question::nextCode($row['part'], $newCodeOffset[$row['part']]++);
            }

            if ($row['code']) {
                $seenCodes[$row['code']] = $line;
            }

            $result['rows'][] = $row;
            $result['counts'][$row['errors'] === [] ? $row['action'] : 'errors']++;
        }

        if ($result['rows'] === []) {
            $result['file_errors'][] = 'Tab CAU_HOI không có dòng câu hỏi nào.';
        }

        // Câu đang bật nhưng không có trong file → sẽ bị tắt (không xóa). Ô đồng ý không bao giờ bị tắt.
        $missing = Question::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('part', Question::PART_INFO)->orWhere('question_set_id', $set->id))
            ->whereNotIn('code', array_keys($seenCodes))
            ->where(fn ($query) => $query->whereNull('lead_field')->orWhere('lead_field', '!=', 'consent'))
            ->orderBy('part')->orderBy('sort_order')
            ->get();

        $result['deactivate'] = $missing->map(fn (Question $question) => ['code' => $question->code, 'text' => $question->text])->all();
        $result['counts']['deactivate'] = $missing->count();

        return $result;
    }

    /**
     * Áp dụng bản xem trước (chỉ khi không có lỗi). Trả về số câu thêm / sửa / tắt.
     *
     * @param  array<string, mixed>  $preview
     * @return array{create: int, update: int, deactivate: int}
     */
    public function apply(array $preview, QuestionSet $set, string $fileName): array
    {
        if ($preview['file_errors'] !== [] || $preview['counts']['errors'] > 0) {
            throw new RuntimeException('File còn lỗi, chưa thể nhập.');
        }

        return DB::transaction(function () use ($preview, $set, $fileName): array {
            $summary = ['create' => 0, 'update' => 0, 'deactivate' => 0];

            foreach ($preview['rows'] as $row) {
                if ($row['action'] === 'create') {
                    Question::create([...$row['attributes'], 'code' => $row['code'], 'part' => $row['part'],
                        'question_set_id' => $row['part'] === Question::PART_HOLLAND ? $set->id : null]);
                    $summary['create']++;
                } elseif ($row['action'] === 'update') {
                    $question = Question::withTrashed()->where('code', $row['code'])->firstOrFail();
                    $question->fill($row['attributes']);
                    $question->deleted_at = null;
                    $question->save();
                    AdminActivity::recordModelChange($question, AdminActivity::SUBJECT_QUESTION, 'Nhập Excel: sửa câu '.$question->shortLabel());
                    $summary['update']++;
                }
            }

            $codes = array_column($preview['deactivate'], 'code');
            $summary['deactivate'] = $codes === [] ? 0 : Question::whereIn('code', $codes)->update(['is_active' => false]);

            AdminActivity::record(AdminActivity::ACTION_IMPORT, AdminActivity::SUBJECT_SET, $set->id,
                'Nhập Excel "'.mb_strimwidth($fileName, 0, 80, '…').'" vào bộ "'.$set->name.'": thêm '.$summary['create'].', sửa '.$summary['update'].', tắt '.$summary['deactivate'],
                null, ['file' => $fileName, ...$summary]);

            return $summary;
        });
    }

    /**
     * @return array<int, array<string, mixed>> [số dòng trong Excel => [tên cột => giá trị]]
     */
    private function readSheet(string $path): array
    {
        // Giữ dòng trống để số dòng báo lỗi khớp với số dòng trong Excel.
        $reader = new Reader(new ReaderOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if (mb_strtoupper(trim($sheet->getName())) !== self::SHEET) {
                    continue;
                }

                $headers = null;
                $rows = [];

                foreach ($sheet->getRowIterator() as $index => $row) {
                    $values = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row->toArray());

                    if ($headers === null) {
                        $headers = array_map(fn ($value) => trim((string) $value), $values);
                        $missing = array_diff(['Mã câu', 'Phần', 'Nội dung câu hỏi', 'Kiểu trả lời'], $headers);

                        if ($missing !== []) {
                            throw new RuntimeException('Tab CAU_HOI thiếu cột: '.implode(', ', $missing).'. Hãy tải file mẫu mới rồi chép câu hỏi vào.');
                        }

                        continue;
                    }

                    if (collect($values)->every(fn ($value) => $value === null || $value === '')) {
                        continue;
                    }

                    $rows[$index] = collect($headers)->mapWithKeys(fn (string $header, int $column) => [$header => $values[$column] ?? null])->all();
                }

                return $rows;
            }
        } finally {
            $reader->close();
        }

        throw new RuntimeException('File không có tab CAU_HOI. Hãy dùng nút "Tải bộ câu hỏi" để lấy file đúng mẫu.');
    }

    /**
     * @param  array<string, mixed>  $cells
     * @param  Collection<string, Question>  $existing
     * @param  array<string, int>  $seenCodes
     * @return array<string, mixed>
     */
    private function parseRow(int $line, array $cells, QuestionSet $set, Collection $existing, array $seenCodes): array
    {
        $errors = [];
        $text = fn (string $column) => trim((string) ($cells[$column] ?? ''));

        $code = mb_strtoupper($text('Mã câu'));
        $part = mb_strtoupper($text('Phần'));
        $content = $text('Nội dung câu hỏi');

        if (! in_array($part, [Question::PART_INFO, Question::PART_HOLLAND], true)) {
            $errors[] = 'Cột "Phần" phải là A hoặc B.';
        }

        if ($content === '') {
            $errors[] = 'Thiếu nội dung câu hỏi.';
        } elseif (mb_strlen($content) > 500) {
            $errors[] = 'Câu hỏi quá dài (tối đa 500 ký tự).';
        }

        if ($code !== '' && ! preg_match('/^[AB]\d{1,4}$/', $code)) {
            $errors[] = 'Mã câu "'.$code.'" không đúng dạng (ví dụ A05, B12). Câu mới thì để trống.';
        } elseif ($code !== '' && isset($seenCodes[$code])) {
            $errors[] = 'Mã câu '.$code.' bị trùng với dòng '.$seenCodes[$code].'.';
        } elseif ($code !== '' && $part !== '' && ! str_starts_with($code, $part)) {
            $errors[] = 'Mã câu '.$code.' không khớp với Phần '.$part.'.';
        }

        $question = $code !== '' ? $existing->get($code) : null;

        if ($question?->isHolland() && $question->question_set_id !== $set->id) {
            $errors[] = 'Câu '.$code.' thuộc bộ câu khác. Hãy nhập vào đúng bộ, hoặc xóa mã để thêm thành câu mới.';
        }

        $attributes = [];

        if ($errors === []) {
            $attributes = $part === Question::PART_HOLLAND
                ? $this->hollandAttributes($cells, $set, $question, $errors)
                : $this->infoAttributes($cells, $question, $existing, $errors);

            $sort = $cells['Thứ tự hiển thị'] ?? null;
            if ($sort !== null && $sort !== '') {
                if (! is_numeric($sort) || (int) $sort < 0) {
                    $errors[] = 'Thứ tự hiển thị phải là số không âm.';
                } else {
                    $attributes['sort_order'] = (int) $sort;
                }
            } elseif ($question === null) {
                $attributes['sort_order'] = 1000 + $line;
            }

            $active = mb_strtolower($text('is_active'));
            $attributes['is_active'] = ! in_array($active, ['0', 'không', 'khong', 'no', 'false'], true);
        }

        $action = $question === null ? 'create' : 'update';
        $changes = $question !== null && $errors === [] ? $this->changedFields($question, $attributes) : [];

        if ($question !== null && $changes === [] && ! $question->trashed()) {
            $action = 'unchanged';
        }

        return [
            'line' => $line,
            'code' => $code,
            'part' => $part,
            'text' => $content,
            'action' => $action,
            'changes' => $changes,
            'errors' => $errors,
            'attributes' => $attributes,
        ];
    }

    /**
     * @param  array<string, mixed>  $cells
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function hollandAttributes(array $cells, QuestionSet $set, ?Question $question, array &$errors): array
    {
        $group = mb_strtoupper(trim((string) ($cells['Nhóm RIASEC / Đối tượng'] ?? '')));

        if (! in_array($group, self::RIASEC, true)) {
            $errors[] = 'Thiếu nhóm RIASEC (ghi R, I, A, S, E hoặc C).';
        }

        $type = $this->answerType((string) ($cells['Kiểu trả lời'] ?? ''));
        if ($type !== null && ! $type->isScored()) {
            $errors[] = 'Câu Holland chỉ dùng kiểu Đúng / Không, Thang 3 mức hoặc Thang 5 mức.';
        }

        $optionsText = trim((string) ($cells['Lựa chọn / Điểm'] ?? ''));
        $scale = $optionsText === '' ? null : ChoiceList::parseScale($optionsText, '|');

        if ($optionsText !== '' && empty($scale)) {
            $errors[] = 'Cột "Lựa chọn / Điểm" cần dạng "Nhãn = điểm | Nhãn = điểm", ví dụ: Đúng = 1 | Không = 0.';
        }

        if ($scale !== null && ChoiceList::same($scale, $set->scaleOptions())) {
            $scale = null;
        }

        $attributes = [
            'text' => trim((string) $cells['Nội dung câu hỏi']),
            'riasec' => $group,
            'answer_type' => $scale === null ? $set->answer_type : ($type ?? $set->answer_type),
            'options' => $scale ?: null,
            'is_required' => true,
        ];

        // Đổi thang riêng thì điểm tối đa đi theo thang mới.
        if ($question === null || ! ChoiceList::same($question->options, $attributes['options'])) {
            $attributes['max_score'] = null;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $cells
     * @param  Collection<string, Question>  $existing
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function infoAttributes(array $cells, ?Question $question, Collection $existing, array &$errors): array
    {
        $type = $this->answerType((string) ($cells['Kiểu trả lời'] ?? ''));

        if ($type === null) {
            $errors[] = 'Kiểu trả lời "'.($cells['Kiểu trả lời'] ?? '').'" không có trong danh sách.';

            return [];
        }

        if ($type->isScored()) {
            $errors[] = 'Câu Phần A không dùng kiểu '.$type->label().'.';
        }

        $options = ChoiceList::parse((string) ($cells['Lựa chọn / Điểm'] ?? ''), '|');
        if (in_array($type, [AnswerType::Single, AnswerType::Multi], true) && $options === []) {
            $errors[] = 'Câu chọn cần có lựa chọn, ví dụ: Học sinh = HS | Phụ huynh = PH.';
        }

        $leadField = null;
        $extraKey = null;
        $target = trim((string) ($cells['Ghi vào cột'] ?? ''));

        if ($type === AnswerType::Consent) {
            $leadField = 'consent';
        } elseif (str_starts_with($target, 'extra_fields.')) {
            $extraKey = substr($target, strlen('extra_fields.'));
            if (! LeadFields::isValidExtraKey($extraKey)) {
                $errors[] = 'Mã trường tùy biến "'.$extraKey.'" chỉ gồm chữ thường không dấu, số và dấu gạch dưới.';
            }
        } elseif ($target !== '') {
            if (! in_array($target, LeadFields::selectable(), true)) {
                $errors[] = 'Cột "Ghi vào cột": "'.$target.'" không phải trường CRM. Dùng tên trường chuẩn (ví dụ phone) hoặc extra_fields.ma_moi.';
            } else {
                $leadField = $target;
            }
        }

        $owner = $existing->first(fn (Question $other) => $other->part === Question::PART_INFO && ! $other->trashed() && $other->code !== $question?->code
            && (($leadField !== null && $other->lead_field === $leadField) || ($extraKey !== null && $other->extra_field_key === $extraKey)));

        if ($owner !== null) {
            $errors[] = 'Trường CRM "'.($leadField ?? 'extra_fields.'.$extraKey).'" đang được câu '.$owner->code.' dùng.';
        }

        $required = mb_strtolower(trim((string) ($cells['Bắt buộc trước khi chuyển tư vấn'] ?? '')));

        return [
            'text' => trim((string) $cells['Nội dung câu hỏi']),
            'answer_type' => $type,
            'options' => in_array($type, [AnswerType::Single, AnswerType::Multi], true) ? $options : ($question?->options),
            'lead_field' => $leadField,
            'extra_field_key' => $extraKey,
            'is_required' => $type === AnswerType::Consent || str_starts_with($required, 'có'),
        ];
    }

    private function answerType(string $label): ?AnswerType
    {
        $normalized = mb_strtolower(preg_replace('/\s+/u', '', $label) ?? '');

        foreach (AnswerType::cases() as $type) {
            if (mb_strtolower(preg_replace('/\s+/u', '', $type->label()) ?? '') === $normalized || $type->value === $normalized) {
                return $type;
            }
        }

        return self::TYPE_ALIASES[$normalized] ?? null;
    }

    /**
     * Tên các mục thay đổi (để hiện ở bước xem trước).
     *
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function changedFields(Question $question, array $attributes): array
    {
        $labels = [
            'text' => 'nội dung', 'answer_type' => 'kiểu trả lời', 'options' => 'lựa chọn / điểm', 'lead_field' => 'trường CRM',
            'extra_field_key' => 'trường CRM', 'riasec' => 'nhóm', 'sort_order' => 'thứ tự', 'is_active' => 'bật / tắt', 'is_required' => 'bắt buộc',
        ];

        $changed = [];

        foreach ($attributes as $key => $value) {
            if (! isset($labels[$key])) {
                continue;
            }

            $current = $question->{$key};
            $same = match ($key) {
                'options' => ChoiceList::same($current, $value),
                'answer_type' => $current === $value,
                default => (string) ($current instanceof \BackedEnum ? $current->value : (is_bool($current) ? (int) $current : $current))
                    === (string) (is_bool($value) ? (int) $value : $value),
            };

            if (! $same) {
                $changed[] = $labels[$key];
            }
        }

        return array_values(array_unique($changed));
    }
}
