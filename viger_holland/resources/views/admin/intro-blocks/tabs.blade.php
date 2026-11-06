{{-- Hai tab của trang giới thiệu. Biến: $active (banner | blocks) --}}
<nav class="tabs" aria-label="Trang giới thiệu">
    <a @class(['tabs__item', 'is-active' => $active === 'banner']) href="{{ route('admin.content.edit', 'intro') }}" @if ($active === 'banner') aria-current="page" @endif>Banner</a>
    <a @class(['tabs__item', 'is-active' => $active === 'blocks']) href="{{ route('admin.intro-blocks.index') }}" @if ($active === 'blocks') aria-current="page" @endif>Các khối nội dung</a>
    <a class="tabs__item tabs__item--link" href="{{ route('home') }}" target="_blank" rel="noopener">Xem trang khách ↗</a>
</nav>
