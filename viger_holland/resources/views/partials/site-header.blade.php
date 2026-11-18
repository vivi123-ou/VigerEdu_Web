{{-- Đầu trang khách: chỉ logo (chốt 14/11), bấm vào về web chính. Chữ lấy từ Cài đặt, sửa được ở "Trang mở đầu". --}}
<header class="khdr">
    <div class="khdr__in">
        <a class="kbrand" href="{{ setting('main_site_url') ?: route('home') }}" title="{{ setting('main_site_link_text', 'Về trang chủ') }}">
            <img src="{{ media_url(setting('logo_path', 'img/logo.png')) }}" alt="{{ setting('site_name') }}" width="40" height="40">
            <span><b>{{ ed('brand_title', setting('site_name'), 'Tên trên logo') }}</b><small>{{ ed('site_tagline', null, 'Dòng chữ dưới tên') }}</small></span>
        </a>
    </div>
</header>
