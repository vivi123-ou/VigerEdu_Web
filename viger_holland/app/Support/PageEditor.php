<?php

namespace App\Support;

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * "Sửa trực tiếp trên trang": trang khách được dựng ở chế độ sửa (chỉ trong admin), mỗi chữ / ảnh sửa được
 * có data-ed="khóa". Lưu: kiểm tra quyền + kiểu ô theo config/admin_content.php, ghi lịch sử như các trang khác.
 *
 * Khóa: tên cài đặt (intro_title…), group_name_R / group_desc_R, hoặc "block:{id}:{đường dẫn}" cho khối trang giới thiệu.
 */
final class PageEditor
{
    private static bool $active = false;

    /** Đường dẫn được sửa trong khối (items.N.* cho danh sách). */
    private const BLOCK_PATHS = '/^(title|intro|note|body|image|items\.\d+\.(title|text|question|answer|quote|name|role))$/';

    private const BLOCK_RICH = '/^(body|items\.\d+\.answer)$/';

    public static function start(): void
    {
        self::$active = true;
    }

    public static function stop(): void
    {
        self::$active = false;
    }

    public static function active(): bool
    {
        return self::$active;
    }

    /**
     * Chữ sửa được. Ngoài chế độ sửa: in chữ bình thường (đã thoát HTML / lọc an toàn).
     */
    public static function text(string $key, ?string $value, string $label = '', bool $rich = false): HtmlString
    {
        $value = (string) $value;
        $html = $rich ? HtmlSanitizer::clean($value) : e($value);

        if (! self::$active || ! self::canEdit($key)) {
            return new HtmlString($html);
        }

        $tag = $rich ? 'div' : 'span';
        $attributes = $rich ? 'contenteditable="true"' : 'contenteditable="plaintext-only"';

        return new HtmlString(sprintf('<%1$s class="ed%2$s" data-ed="%3$s" data-ed-type="%4$s" data-ed-label="%5$s" %6$s spellcheck="true">%7$s</%1$s>',
            $tag, $rich ? ' ed--rich' : '', e($key), $rich ? 'rich' : 'text', e($label ?: 'Bấm để nhập chữ'), $attributes, $html));
    }

    /**
     * Thuộc tính cho ảnh sửa được (bấm để chọn ảnh khác).
     */
    /**
     * Người đang đăng nhập có được sửa ô này không (theo vai trò).
     */
    public static function canEdit(string $key): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if (str_starts_with($key, 'block:')) {
            return $user->canAccessMenu('intro');
        }

        $field = self::field($key);

        return $field !== null && $user->canAccessMenu($field['menu']);
    }

    public static function imageAttributes(string $key, ?string $path): HtmlString
    {
        return new HtmlString(self::$active && self::canEdit($key) ? sprintf('data-ed-image="%s" data-ed-path="%s" tabindex="0" role="button" title="Bấm để đổi ảnh"', e($key), e((string) $path)) : '');
    }

    /**
     * Lưu các thay đổi gửi từ trang đang sửa: [khóa => giá trị]. Trả về số ô đã đổi.
     *
     * @param  array<string, mixed>  $changes
     *
     * @throws ValidationException
     */
    public static function save(User $user, array $changes): int
    {
        $settings = [];
        $blocks = [];

        foreach ($changes as $key => $value) {
            $key = (string) $key;
            $value = is_string($value) ? $value : '';

            if (str_starts_with($key, 'block:')) {
                [, $id, $path] = array_pad(explode(':', $key, 3), 3, '');
                self::guard($user->canAccessMenu('intro') && preg_match(self::BLOCK_PATHS, $path), $key);
                if ($path === 'image' && $value !== '' && ! preg_match('#^((img|storage)/[A-Za-z0-9_\-./]+|https://\S+)$#', $value)) {
                    throw ValidationException::withMessages([$key => 'Ảnh chưa đúng, vui lòng chọn lại.']);
                }
                $blocks[$id][$path] = preg_match(self::BLOCK_RICH, $path) ? HtmlSanitizer::clean($value) : self::cleanText($value, $path === 'intro' ? 1500 : 800);

                continue;
            }

            $field = self::field($key);
            self::guard($field !== null && $user->canAccessMenu($field['menu']), $key);
            $settings[$field['subject']][$key] = [self::normalize($field, $value, $key), $field['group'], $field['title']];
        }

        $count = 0;

        foreach ($settings as $subject => $values) {
            $before = [];
            $after = [];
            foreach ($values as $key => [$value, $group]) {
                $current = Setting::query()->where('key', $key)->value('value');
                if ((string) $current !== $value) {
                    $before[$key] = $current;
                    $after[$key] = $value;
                    Setting::put($key, $value, $group);
                }
            }
            if ($after !== []) {
                $count += count($after);
                AdminActivity::record(AdminActivity::ACTION_UPDATE, $subject, null, 'Sửa trực tiếp trên trang: "'.reset($values)[2].'" ('.count($after).' ô)', $before, $after);
            }
        }

        if ($blocks !== []) {
            $all = IntroBlocks::all();
            foreach ($all as &$block) {
                foreach ($blocks[$block['id']] ?? [] as $path => $value) {
                    if (Arr::get($block['data'], $path) !== $value) {
                        Arr::set($block['data'], $path, $value);
                        $count++;
                    }
                }
            }
            unset($block);
            IntroBlocks::save($all, 'Sửa trực tiếp các khối trang giới thiệu');
        }

        return $count;
    }

    /**
     * Thông tin ô theo khóa cài đặt: kiểu, mục menu (quyền), nhóm cài đặt, loại nhật ký.
     *
     * @return array{type: string, menu: string, group: string, subject: string, title: string}|null
     */
    private static function field(string $key): ?array
    {
        if (preg_match('/^group_(name|desc)_[RIASEC]$/', $key)) {
            return ['type' => str_contains($key, 'desc') ? 'textarea' : 'text', 'menu' => 'groups', 'group' => 'intro',
                'subject' => AdminActivity::SUBJECT_GROUPS, 'title' => 'Mô tả 6 nhóm Holland'];
        }

        foreach (config('admin_content') as $pageKey => $page) {
            foreach (ContentPages::fields($page) as $field) {
                if ($field['key'] === $key && in_array($field['type'], ['text', 'textarea', 'rich', 'image', 'email'], true)) {
                    return ['type' => $field['type'], 'menu' => $page['menu'], 'group' => $page['group'],
                        'subject' => AdminActivity::SUBJECT_SETTINGS.'.'.$pageKey, 'title' => $page['title'], 'required' => $field['required'] ?? false];
                }
            }
        }

        return null;
    }

    private static function normalize(array $field, string $value, string $key): string
    {
        $value = match ($field['type']) {
            'rich' => HtmlSanitizer::clean($value),
            'textarea' => self::cleanText($value, 2000, multiline: true),
            'image' => trim($value),
            default => self::cleanText($value, 255),
        };

        if ($field['type'] === 'image' && $value !== '' && ! preg_match('#^((img|storage)/[A-Za-z0-9_\-./]+|https://\S+)$#', $value)) {
            throw ValidationException::withMessages([$key => 'Ảnh chưa đúng, vui lòng chọn lại.']);
        }

        if (($field['required'] ?? false) && $value === '') {
            throw ValidationException::withMessages([$key => 'Ô này không được để trống.']);
        }

        if ($field['type'] === 'email' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([$key => 'Email chưa đúng (ví dụ: ten@viger.edu.vn).']);
        }

        return $value;
    }

    private static function cleanText(string $value, int $max, bool $multiline = false): string
    {
        $value = str_replace("\u{00A0}", ' ', strip_tags($value));
        $value = $multiline ? preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', $value)) : preg_replace('/\s+/u', ' ', $value);

        return mb_substr(trim((string) $value), 0, $max);
    }

    private static function guard(bool $allowed, string $key): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages([$key => 'Bạn không có quyền sửa nội dung này.']);
        }
    }
}
