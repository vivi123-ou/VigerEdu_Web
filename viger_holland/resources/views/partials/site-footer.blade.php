@php
    $socials = \App\Support\ContentPages::links(setting('footer_links'));
    $hotline = setting('hotline');
    $zalo = setting('zalo');
@endphp
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__inner">
            <div>
                <p class="site-footer__name">{{ ed('company_name', setting('site_name'), 'Tên công ty') }}</p>
                @if (filled(setting('footer_description')))
                    <p class="site-footer__desc">{{ ed('footer_description', null, 'Giới thiệu ngắn') }}</p>
                @endif
            </div>

            <div>
                <p class="site-footer__heading">{{ setting('footer_contact_heading', 'Liên hệ') }}</p>
                @if (\App\Support\PageEditor::active() && \App\Support\PageEditor::canEdit('hotline'))
                    {{-- Chế độ sửa: hiện mọi dòng, kể cả dòng trống (để trống thì khách không thấy) --}}
                    <ul>
                        <li>Hotline: {{ ed('hotline', null, 'Số hotline') }}</li>
                        <li>Zalo: {{ ed('zalo', null, 'Số Zalo') }}</li>
                        <li>Email: {{ ed('email', null, 'Email liên hệ') }}</li>
                        <li class="site-footer__address">{{ ed('address', null, 'Địa chỉ') }}</li>
                        <li>{{ ed('working_hours', null, 'Giờ làm việc') }}</li>
                    </ul>
                @else
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
                            <li class="site-footer__address">{{ setting('address') }}</li>
                        @endif
                        @if (filled(setting('working_hours')))
                            <li>{{ setting('working_hours') }}</li>
                        @endif
                    </ul>
                @endif
            </div>

            @if ($socials)
                <div>
                    <p class="site-footer__heading">{{ setting('footer_social_heading', 'Theo dõi') }}</p>
                    <ul>
                        @foreach ($socials as $link)
                            <li><a href="{{ $link['url'] }}" @if ($link['new_tab']) target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <p class="site-footer__copy">© {{ now()->year }} {{ ed('copyright', setting('site_name'), 'Dòng bản quyền') }}</p>
    </div>
</footer>
