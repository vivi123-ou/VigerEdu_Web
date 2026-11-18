<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * Làm gì:   Khung chung cho trang danh mục có các thẻ (ví dụ 6 nhóm / 15 cặp / chữ thứ ba): bấm thẻ thì bảng đổi theo,
 *           sửa trong hộp thoại, mỗi lần lưu ghi vào "Lịch sử thao tác". Lớp con khai báo thẻ, quyền và bảng của từng thẻ.
 * Ai gọi:   HollandCodes, MajorsCatalog (app/Modules/Assessment/Filament/Pages).
 * Dữ liệu:  tùy lớp con; admin_activities.
 */
abstract class TabbedTablePage extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected string $view = 'filament.pages.tabbed-table';

    #[Url(as: 'the')]
    public string $tab = '';

    /** Mã quyền cần có để vào trang. */
    abstract protected static function permission(): string;

    /**
     * Thẻ: mã => [tên, câu giải thích ngắn].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    abstract public function tabs(): array;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission(static::permission());
    }

    public function mount(): void
    {
        if (! array_key_exists($this->tab, $this->tabs())) {
            $this->tab = array_key_first($this->tabs());
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->tab = $tab;
            $this->resetTable();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return static::menuItem()['summary'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [static::helpAction()];
    }

    /**
     * Ghi lịch sử sau khi thêm / sửa một dòng danh mục (chỉ các cột đã đổi).
     */
    protected function logChange(Model $record, string $what): void
    {
        $changes = $record->getChanges();
        unset($changes['updated_at']);
        if ($record->wasRecentlyCreated) {
            AdminActivity::record(AdminActivity::ACTION_CREATE, 'catalog.'.$this->tab, $record->getKey(), 'Thêm '.$what);
        } elseif ($changes !== []) {
            AdminActivity::record(AdminActivity::ACTION_UPDATE, 'catalog.'.$this->tab, $record->getKey(), 'Sửa '.$what.' ('.implode(', ', array_keys($changes)).')');
        }
    }
}
