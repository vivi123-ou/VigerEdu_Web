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
    <div class="container">
        <div class="site-footer__inner">
            <div>
                <p class="site-footer__name">{{ setting('company_name', setting('site_name')) }}</p>
                @if (filled(setting('footer_description')))
                    <p class="site-footer__desc">{{ setting('footer_description') }}</p>
                @endif
            </div>

            <div>
                <p class="site-footer__heading">{{ setting('footer_contact_heading', 'Liên hệ') }}</p>
                <ul>
                    @if (filled($hotline))
                        <li>Hotline: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}">{{ $hotline }}</a></li>
                    @endif
                    @if (filled($zalo))
                        <li>Zalo: <a href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" rel="noopener">{{ $zalo }}</a></li>
                    @endif
                    @if (filled(setting('email')))
                        <li><a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>
                    @endif
                    @if (filled(setting('address')))
                        <li>{{ setting('address') }}</li>
                    @endif
                    @if (filled(setting('working_hours')))
                        <li>{{ setting('working_hours') }}</li>
                    @endif
                </ul>
            </div>

            @if ($socials)
                <div>
                    <p class="site-footer__heading">{{ setting('footer_social_heading', 'Theo dõi') }}</p>
                    <ul>
                        @foreach ($socials as $name => $url)
                            <li><a href="{{ $url }}" rel="noopener" target="_blank">{{ $name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <p class="site-footer__copy">© {{ now()->year }} {{ setting('copyright', setting('site_name')) }}</p>
    </div>
</footer>
