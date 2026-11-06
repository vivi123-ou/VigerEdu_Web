<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Lọc HTML từ trình soạn thảo: chỉ giữ thẻ định dạng đơn giản (đoạn, đậm, nghiêng, link, danh sách, tiêu đề nhỏ).
 * Bỏ script, style, thuộc tính sự kiện (onclick…), link javascript:. Dùng khi lưu và khi hiển thị.
 */
final class HtmlSanitizer
{
    /** Thẻ được giữ => thuộc tính được giữ. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h3' => [], 'h4' => [], 'blockquote' => [],
        'a' => ['href', 'target', 'rel'],
    ];

    /** Thẻ bị bỏ cả nội dung bên trong. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        // Chữ thường không có thẻ: đổi xuống dòng thành đoạn văn.
        if (strip_tags($html) === $html) {
            return collect(preg_split('/\R{2,}/', $html) ?: [])
                ->map(fn (string $paragraph) => '<p>'.preg_replace('/\R/', '<br>', e(trim($paragraph))).'</p>')
                ->implode('');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('root');

        if ($root === null) {
            return e(strip_tags($html));
        }

        self::cleanChildren($root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $node->removeChild($child);

                    continue;
                }

                self::cleanChildren($child);

                if (! array_key_exists($tag, self::ALLOWED)) {
                    // Thẻ lạ (div, span, font…): bỏ thẻ, giữ chữ bên trong.
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }

                self::cleanAttributes($child, self::ALLOWED[$tag]);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function cleanAttributes(DOMElement $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($element->tagName !== 'a') {
            return;
        }

        $href = trim($element->getAttribute('href'));
        if (! preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $href)) {
            $element->removeAttribute('href');
        }

        if ($element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener');
        } else {
            $element->removeAttribute('target');
        }
    }
}
