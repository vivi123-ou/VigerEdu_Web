<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Đọc config/admin_content.php: các trang "Nội dung trang" và các ô của từng trang.
 * Kiểm tra + chuẩn hóa giá trị trước khi lưu hoặc xem trước.
 */
final class ContentPages
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $page): ?array
    {
        return config("admin_content.{$page}");
    }

    /**
     * Mọi ô của trang (bỏ phân nhóm).
     *
     * @return list<array<string, mixed>>
     */
    public static function fields(array $page): array
    {
        return array_merge(...array_values($page['sections']));
    }

    /**
     * Kiểm tra dữ liệu gửi lên và trả về [khóa => giá trị đã chuẩn hóa để lưu].
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    public static function validate(array $page, array $input): array
    {
        $rules = [];
        $messages = [];
        $attributes = [];

        foreach (self::fields($page) as $field) {
            $key = $field['key'];
            $presence = ($field['required'] ?? false) ? 'required' : 'nullable';
            $attributes[$key] = '"'.$field['label'].'"';

            $rules[$key] = match ($field['type']) {
                'color' => [$presence, 'regex:/^#[0-9a-fA-F]{6}$/'],
                'number' => [$presence, 'integer', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 100000)],
                'font' => [$presence, Rule::in(array_keys(config('holland.fonts')))],
                'url' => [$presence, 'url:http,https', 'max:255'],
                'email' => [$presence, 'email', 'max:255'],
                'image' => [$presence, 'string', 'max:255', 'regex:#^((img|storage)/[A-Za-z0-9_\-./]+|https://\S+)$#'],
                'links' => ['nullable', 'array', 'max:20'],
                'rich', 'domains' => [$presence, 'string', 'max:20000'],
                'textarea' => [$presence, 'string', 'max:2000'],
                default => [$presence, 'string', 'max:255'],
            };

            if ($field['type'] === 'number') {
                $messages["{$key}.max"] = 'Ô :attribute lớn nhất là :max.';
            }

            if ($field['type'] === 'links') {
                $rules["{$key}.*.label"] = ['nullable', 'string', 'max:60'];
                $rules["{$key}.*.new_tab"] = ['nullable', 'boolean'];
                $rules["{$key}.*.url"] = ['nullable', 'required_with:'.$key.'.*.label', 'url:http,https', 'max:255'];
                $messages["{$key}.*.url.url"] = 'Địa chỉ link chưa đúng. Ví dụ: viger.edu.vn hoặc viger.edu.vn/du-hoc-duc';
                $messages["{$key}.*.url.required_with"] = 'Vui lòng nhập địa chỉ cho link này.';
            }
        }

        $messages += [
            'required' => 'Vui lòng điền ô :attribute.',
            'regex' => 'Ô :attribute chưa đúng dạng.',
            'url' => 'Ô :attribute chưa phải địa chỉ web. Ví dụ: viger.edu.vn',
            'email' => 'Email chưa đúng (ví dụ: ten@viger.edu.vn).',
            'in' => 'Lựa chọn ở ô :attribute không hợp lệ.',
            'integer' => 'Ô :attribute phải là số nguyên.',
            'min' => 'Ô :attribute nhỏ nhất là :min.',
            'max' => 'Ô :attribute quá dài.',
        ];

        // Link gõ thiếu https:// thì tự thêm (ví dụ "viger.edu.vn").
        foreach (self::fields($page) as $field) {
            $key = $field['key'];
            if ($field['type'] === 'url' && array_key_exists($key, $input)) {
                $input[$key] = LinkNormalizer::normalize($input[$key]);
            }
            if ($field['type'] === 'links' && is_array($input[$key] ?? null)) {
                $input[$key] = array_map(fn ($row) => is_array($row) ? [...$row, 'url' => LinkNormalizer::normalize($row['url'] ?? '')] : $row, $input[$key]);
            }
        }

        $validated = Validator::make($input, $rules, $messages, $attributes)->validate();

        $values = [];
        foreach (self::fields($page) as $field) {
            $values[$field['key']] = self::normalize($field, $validated[$field['key']] ?? null);
        }

        return $values;
    }

    /**
     * Giá trị hiện tại của các ô (chuỗi lưu trong settings).
     *
     * @return array<string, string|null>
     */
    public static function currentValues(array $page): array
    {
        return collect(self::fields($page))
            ->mapWithKeys(fn (array $field) => [$field['key'] => Setting::query()->where('key', $field['key'])->value('value')])
            ->all();
    }

    /**
     * Danh sách link đã lưu: [{label, url, new_tab}].
     *
     * @return list<array{label: string, url: string, new_tab: bool}>
     */
    public static function links(?string $json): array
    {
        $links = json_decode((string) $json, true);

        return is_array($links) ? array_values(array_filter($links, fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))) : [];
    }

    /**
     * Tên miền được phép (mỗi dòng một địa chỉ) → danh sách origin "https://ten-mien".
     *
     * @return list<string>
     */
    public static function domains(?string $text): array
    {
        return collect(preg_split('/[\s,]+/', (string) $text) ?: [])
            ->map(function (string $line): ?string {
                $line = trim($line);
                if ($line === '') {
                    return null;
                }
                $url = preg_match('#^https?://#i', $line) ? $line : 'https://'.$line;
                $parts = parse_url($url);

                return isset($parts['host']) && preg_match('/^[a-z0-9.\-]+$/i', $parts['host'])
                    ? strtolower(($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''))
                    : null;
            })
            ->filter()->unique()->values()->all();
    }

    private static function normalize(array $field, mixed $value): string
    {
        return match ($field['type']) {
            'rich' => HtmlSanitizer::clean((string) $value),
            'links' => json_encode(collect((array) $value)
                ->map(fn ($row) => ['label' => trim((string) ($row['label'] ?? '')), 'url' => trim((string) ($row['url'] ?? '')), 'new_tab' => (bool) ($row['new_tab'] ?? false)])
                ->filter(fn (array $row) => $row['label'] !== '' && $row['url'] !== '')
                ->values()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'domains' => implode("\n", self::domains((string) $value)),
            'color' => strtolower((string) $value),
            default => trim((string) $value),
        };
    }
}
