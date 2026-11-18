{{-- Dùng inline style vì panel chưa có Filament theme riêng (class Tailwind tuỳ ý sẽ không được build) --}}
<x-filament-widgets::widget>
    <div style="position: relative; overflow: hidden; border-radius: 0.75rem; padding: 1.75rem 2rem; color: #fff; background: linear-gradient(120deg, #7a0b14 0%, #c1121f 55%, #e63946 100%); box-shadow: 0 10px 30px -12px rgba(193, 18, 31, 0.55);">
        {{-- Hoạ tiết trang trí --}}
        <div style="position: absolute; top: -60px; right: -40px; width: 220px; height: 220px; border-radius: 9999px; background: rgba(255,255,255,0.08);"></div>
        <div style="position: absolute; bottom: -80px; right: 140px; width: 180px; height: 180px; border-radius: 9999px; background: rgba(255,255,255,0.06);"></div>
        <div style="position: absolute; top: 24px; right: 32px; opacity: 0.18;">
            <x-filament::icon icon="heroicon-o-academic-cap" style="width: 96px; height: 96px; color: #fff;" />
        </div>

        <div style="position: relative; max-width: 42rem;">
            <p style="margin: 0; font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.85;">
                {{ $today }}
            </p>
            <h2 style="margin: 0.35rem 0 0; font-size: 1.6rem; font-weight: 700; line-height: 1.25;">
                {{ $greeting }}, {{ $userName }} 👋
            </h2>
            <p style="margin: 0.5rem 0 0; font-size: 0.95rem; opacity: 0.9;">
                Chào mừng trở lại trang quản trị VIGER EDU. Đây là tình hình website hôm nay.
            </p>

            <div style="display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 1.25rem;">
                <a href="{{ $createPostUrl }}" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: #fff; color: #c1121f; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                    <x-filament::icon icon="heroicon-m-pencil-square" style="width: 1.1rem; height: 1.1rem;" />
                    Viết bài mới
                </a>
                <a href="{{ $bannersUrl }}" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.35); color: #fff; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                    <x-filament::icon icon="heroicon-m-photo" style="width: 1.1rem; height: 1.1rem;" />
                    Quản lý banner
                </a>
                <a href="{{ $siteUrl }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.95rem; border-radius: 0.5rem; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.35); color: #fff; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" style="width: 1.1rem; height: 1.1rem;" />
                    Xem website
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
