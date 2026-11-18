<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một dòng nhật ký thay đổi trên admin (ai sửa gì lúc nào).
 * `before` = giá trị trước khi sửa, dùng cho nút "Quay lại bản này".
 * subject_type: 'question', 'question_set' hoặc 'settings' (khi đó before/after là [khóa => giá trị]).
 */
#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'description', 'before', 'after'])]
class AdminActivity extends Model
{
    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const ACTION_RESTORE = 'restore';

    public const ACTION_IMPORT = 'import';

    public const ACTION_REVERT = 'revert';

    /** Tra cứu / xem dữ liệu khách (H4b): ghi lại để quản trị viên kiểm tra, không chứa SĐT, email đã gõ. */
    public const ACTION_SEARCH = 'search';

    public const ACTION_VIEW = 'view';

    public const SUBJECT_SETTINGS = 'settings';

    /** Nhật ký cài đặt của trang "Mô tả 6 nhóm". */
    public const SUBJECT_GROUPS = 'settings.groups';

    /**
     * Loại nội dung (subject_type) => model, để nút "Quay lại bản này" biết khôi phục vào đâu.
     * Mỗi giai đoạn thêm model của mình vào đây.
     *
     * @var array<string, class-string<Model>>
     */
    public const MODELS = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(string $action, ?string $subjectType, ?int $subjectId, string $description, ?array $before = null, ?array $after = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => mb_substr($description, 0, 255),
            'before' => $before,
            'after' => $after,
        ]);
    }

    /**
     * Ghi lại việc sửa một model vừa lưu: chỉ các cột đã đổi (giá trị thô trước và sau).
     */
    public static function recordModelChange(Model $model, string $subjectType, string $description, bool $mergeRecent = false): ?self
    {
        $changed = array_keys(collect($model->getChanges())->except(['updated_at', 'created_at'])->all());

        if ($changed === []) {
            return null;
        }

        // Sau save(), "original" đã là giá trị mới; giá trị cũ nằm trong getPrevious().
        $previous = $model->getPrevious();
        $before = collect($changed)->mapWithKeys(fn (string $key) => [$key => $previous[$key] ?? null])->all();
        $after = collect($changed)->mapWithKeys(fn (string $key) => [$key => $model->getAttributes()[$key] ?? null])->all();

        // Tự lưu khi đang gõ: gộp vào dòng lịch sử vừa ghi (cùng người, cùng đối tượng, trong 3 phút),
        // giữ giá trị "trước" của lần đầu để "Quay lại bản này" về đúng bản trước khi sửa.
        if ($mergeRecent) {
            $recent = static::query()
                ->where('user_id', auth()->id())->where('subject_type', $subjectType)->where('subject_id', $model->getKey())
                ->where('action', self::ACTION_UPDATE)->where('created_at', '>=', now()->subMinutes(3))
                ->latest('id')->first();

            if ($recent !== null && $recent->id === static::query()->where('subject_type', $subjectType)->where('subject_id', $model->getKey())->max('id')) {
                $recent->update([
                    'description' => mb_substr($description, 0, 255),
                    'before' => array_replace($before, (array) $recent->before),
                    'after' => array_replace((array) $recent->after, $after),
                ]);

                return $recent;
            }
        }

        return static::record(self::ACTION_UPDATE, $subjectType, (int) $model->getKey(), $description, $before, $after);
    }

    /**
     * Nhật ký gần đây của một đối tượng.
     *
     * @return Collection<int, self>
     */
    public static function forSubject(string $subjectType, ?int $subjectId, int $limit = 10): Collection
    {
        return static::query()
            ->with('user')
            ->where('subject_type', $subjectType)
            ->when($subjectId !== null, fn ($query) => $query->where('subject_id', $subjectId), fn ($query) => $query->whereNull('subject_id'))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function canRevert(): bool
    {
        return $this->action === self::ACTION_UPDATE && ! empty($this->before);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'Thêm',
            self::ACTION_UPDATE => 'Sửa',
            self::ACTION_DELETE => 'Vào thùng rác',
            self::ACTION_RESTORE => 'Khôi phục',
            self::ACTION_IMPORT => 'Nhập Excel',
            self::ACTION_REVERT => 'Quay lại bản cũ',
            self::ACTION_SEARCH => 'Tra cứu',
            self::ACTION_VIEW => 'Xem',
            default => $this->action,
        };
    }
}
