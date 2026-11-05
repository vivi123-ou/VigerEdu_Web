<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Dán nhiều câu Holland cùng lúc: mỗi dòng một câu.
 * Dòng có thể bắt đầu bằng chữ nhóm, ví dụ "R | Tôi thích sửa xe đạp"; không có thì dùng nhóm chọn chung.
 */
class BulkQuestionsRequest extends FormRequest
{
    private const GROUP_PATTERN = '/^\s*([RIASEC])\s*[|:]\s*(.+)$/u';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lines' => ['required', 'string', 'max:50000'],
            'riasec' => ['nullable', Rule::in(['R', 'I', 'A', 'S', 'E', 'C'])],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $errors = [];

            foreach ($this->parsedRows() as $number => $row) {
                if ($row['riasec'] === null) {
                    $errors[] = "Dòng {$number}: chưa có nhóm. Chọn \"Nhóm cho các câu\" hoặc ghi đầu dòng, ví dụ: R | {$row['text']}";
                } elseif (mb_strlen($row['text']) > 500) {
                    $errors[] = "Dòng {$number}: câu quá dài (tối đa 500 ký tự).";
                }
            }

            foreach (array_slice($errors, 0, 10) as $error) {
                $validator->errors()->add('lines', $error);
            }
        }];
    }

    /**
     * Các câu đã tách: [số dòng => {text, riasec}].
     *
     * @return array<int, array{text: string, riasec: string|null}>
     */
    public function parsedRows(): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->input('lines')) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match(self::GROUP_PATTERN, $line, $match)) {
                $rows[$index + 1] = ['text' => trim($match[2]), 'riasec' => $match[1]];
            } else {
                $rows[$index + 1] = ['text' => $line, 'riasec' => $this->input('riasec') ?: null];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Vui lòng dán ít nhất một câu hỏi (mỗi dòng một câu).',
        ];
    }
}
