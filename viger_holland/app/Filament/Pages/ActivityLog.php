<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\UsesAdminMenu;
use App\Models\AdminActivity;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Làm gì:   Trang "Lịch sử thao tác" (chỉ đọc): ai sửa gì, ai tra cứu / mở báo cáo của khách nào, ai tải dữ liệu, lúc nào.
 *           Lọc theo người, loại việc, khoảng ngày. Không sửa / xóa được.
 * Ai gọi:   Filament (/admin/lich-su-thao-tac). Quyền: logs.view (mặc định chỉ quản trị viên).
 * Dữ liệu:  admin_activities (không chứa SĐT, email khách đã gõ).
 */
class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;
    use UsesAdminMenu;

    protected static ?string $slug = 'lich-su-thao-tac';

    protected static string $menuKey = 'logs';

    protected string $view = 'filament.pages.simple-table';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessAdmin() && $user->hasPermission('logs.view');
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

    public function table(Table $table): Table
    {
        return $table
            ->query(AdminActivity::query()->with('user'))
            ->defaultSort('id', 'desc')
            ->searchable()
            ->searchPlaceholder('Tìm trong nội dung')
            ->searchUsing(fn (Builder $query, string $search) => $query->where('description', 'like', '%'.$search.'%'))
            ->columns([
                TextColumn::make('created_at')->label('Lúc')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('user.name')->label('Ai')->default('Hệ thống'),
                TextColumn::make('action')->label('Việc')->badge()->formatStateUsing(fn (AdminActivity $record) => $record->actionLabel())
                    ->color(fn (string $state) => match ($state) {
                        AdminActivity::ACTION_VIEW, AdminActivity::ACTION_SEARCH => 'info',
                        'export' => 'warning', AdminActivity::ACTION_DELETE => 'danger', default => 'gray',
                    }),
                TextColumn::make('description')->label('Nội dung')->wrap(),
            ])
            ->filters([
                SelectFilter::make('user_id')->label('Người làm')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('action')->label('Loại việc')->options([
                    AdminActivity::ACTION_UPDATE => 'Sửa', AdminActivity::ACTION_CREATE => 'Thêm', AdminActivity::ACTION_DELETE => 'Vào thùng rác',
                    AdminActivity::ACTION_REVERT => 'Quay lại bản cũ', AdminActivity::ACTION_SEARCH => 'Tra cứu', AdminActivity::ACTION_VIEW => 'Xem',
                    'export' => 'Tải dữ liệu',
                ]),
                Filter::make('ngay')->label('Khoảng ngày')
                    ->schema([DatePicker::make('tu')->label('Từ ngày'), DatePicker::make('den')->label('Đến ngày')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['tu'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['den'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ]);
    }
}
