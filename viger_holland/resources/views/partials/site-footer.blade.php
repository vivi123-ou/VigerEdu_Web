{{-- Chân trang khách gọn (chốt 14/11): logo, hotline, email, địa chỉ, bản quyền, link về web chính. Chữ sửa được ở "Trang mở đầu". --}}
@php
    $editing = \App\Support\PageEditor::active() && \App\Support\PageEditor::canEdit('hotline');
    $hotline = setting('hotline');
@endphp
<footer class="kfoot">
    <div class="wrap">
        <div class="kfoot__row">
            <a class="kbrand" href="{{ setting('main_site_url') ?: route('home') }}">
                <img src="{{ media_url(setting('logo_path', 'img/logo.png')) }}" alt="{{ setting('site_name') }}" width="40" height="40">
                <span><b>{{ setting('brand_title', setting('site_name')) }}</b><small>{{ setting('site_tagline') }}</small></span>
            </a>
            <ul class="kfoot__ct">
                @if ($editing || filled($hotline))
                    <li><svg class="ic"><use href="#i-phone"/></svg>
                        @if ($editing){{ ed('hotline', null, 'Số hotline') }}@else<a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}">{{ $hotline }}</a>@endif
                    </li>
                @endif
                @if ($editing || filled(setting('email')))
                    <li><svg class="ic"><use href="#i-mail"/></svg>
                        @if ($editing){{ ed('email', null, 'Email liên hệ') }}@else<a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a>@endif
                    </li>
                @endif
                @if ($editing || filled(setting('address')))
                    <li><svg class="ic"><use href="#i-pin"/></svg>{{ ed('address', null, 'Địa chỉ') }}</li>
                @endif
            </ul>
        </div>
        <div class="kfoot__copy">
            <span>Copyright © {{ now()->year }} {{ ed('copyright', null, 'Tên ghi bản quyền') }}</span>
            @if (filled(setting('main_site_url')))
                <a href="{{ setting('main_site_url') }}">{{ setting('main_site_link_text', 'Về trang chủ') }} »</a>
            @endif
        </div>
    </div>
</footer>
