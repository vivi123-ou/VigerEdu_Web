<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_simple_formatting(): void
    {
        $html = '<p>Chào <strong>bạn</strong>, <em>xem</em> <a href="https://viger.edu.vn">web</a></p><ul><li>Một</li></ul>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_removes_scripts_events_and_javascript_links(): void
    {
        $clean = HtmlSanitizer::clean('<p onclick="alert(1)">Chữ<script>alert(1)</script></p><a href="javascript:alert(1)">x</a><iframe src="https://x"></iframe><img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringContainsString('<p>Chữ</p>', $clean);
    }

    public function test_unknown_tags_are_unwrapped_and_new_tab_links_get_noopener(): void
    {
        $clean = HtmlSanitizer::clean('<div><span style="color:red">Giữ chữ</span></div><a href="https://a.vn" target="_blank">a</a>');

        $this->assertSame('Giữ chữ<a href="https://a.vn" target="_blank" rel="noopener">a</a>', $clean);
    }

    public function test_plain_text_becomes_paragraphs_and_is_escaped(): void
    {
        $this->assertSame('<p>Dòng 1<br>Dòng 2</p><p>Đoạn 2 &amp; tiếp</p>', HtmlSanitizer::clean("Dòng 1\nDòng 2\n\nĐoạn 2 & tiếp"));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }
}
