@php
    $socials = array_filter([
        'Facebook' => setting('facebook_url'),
        'YouTube' => setting('youtube_url'),
        'TikTok' => setting('tiktok_url'),
    ]);
    $hotline = setting('hotline');
    $zalo = setting('zalo');
@endphp
<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__about">
            <p class="site-footer__name">{{ setting('company_name', setting('site_name')) }}</p>
            @if (filled(setting('footer_description')))
                <p class="site-footer__desc">{{ setting('footer_description') }}</p>
            @endif
        </div>

        <ul class="site-footer__contact">
            @if (filled($hotline))
                <li>Hotline: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}">{{ $hotline }}</a></li>
            @endif
            @if (filled($zalo))
                <li>Zalo: <a href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" rel="noopener">{{ $zalo }}</a></li>
            @endif
            @if (filled(setting('email')))
                <li>Email: <a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>
            @endif
            @if (filled(setting('address')))
                <li>Địa chỉ: {{ setting('address') }}</li>
            @endif
            @if (filled(setting('working_hours')))
                <li>Giờ làm việc: {{ setting('working_hours') }}</li>
            @endif
        </ul>

        @if ($socials)
            <ul class="site-footer__socials">
                @foreach ($socials as $name => $url)
                    <li><a href="{{ $url }}" rel="noopener" target="_blank">{{ $name }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>
    <p class="site-footer__copy">© {{ now()->year }} {{ setting('copyright', setting('site_name')) }}</p>
</footer>
