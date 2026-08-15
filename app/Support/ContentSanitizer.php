<?php

/**
 * @author OOI JIAN XIN
 *
 * Whitelist-based HTML sanitizer for rich lesson content.
 *
 * Lesson content may contain a small, safe subset of HTML (headings,
 * paragraphs, lists, links, basic inline formatting). Everything else -
 * scripts, styles, iframes, objects, embeds, SVG, images, event handlers -
 * is stripped. Rendering never trusts the raw input; content is always run
 * through sanitize() before being stored and is rendered with {!! !!} only
 * after sanitization.
 */

namespace App\Support;

class ContentSanitizer
{
    /**
     * Tags that are allowed to remain.
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'blockquote', 'code', 'pre', 'span',
    ];

    /**
     * Tags that are always removed completely (including their content)
     * because they can carry active or dangerous content.
     */
    private const DROP_WHOLE_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'link', 'meta', 'title', 'head', 'base', 'form', 'input', 'button',
        'textarea', 'select', 'option', 'template', 'noscript', 'frame', 'frameset',
    ];

    /**
     * Allowed attributes per tag. Any other attribute is removed.
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target'],
        'span' => [],
        'code' => [],
        'pre' => [],
        'blockquote' => [],
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        if (! class_exists(\DOMDocument::class)) {
            // No DOM extension: fall back to fully stripping tags.
            return static::stripAll($html);
        }

        $html = static::toUtf8($html);

        $document = new \DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);

        // Wrap in a body so DOMDocument can parse any fragment reliably.
        $document->loadHTML(
            '<html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_NONET | LIBXML_COMPACT
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if ($body) {
            static::cleanNode($document, $body);
        }

        $cleaned = '';
        if ($body) {
            foreach (iterator_to_array($body->childNodes) as $child) {
                $cleaned .= $document->saveHTML($child);
            }
        }

        return static::tidy($cleaned);
    }

    /**
     * Recursively walk a node, removing disallowed elements/attributes and
     * neutralising unsafe links.
     */
    private static function cleanNode(\DOMDocument $document, \DOMNode $node): void
    {
        // Iterate over a snapshot so removals are safe.
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->nodeName);

                if (in_array($tag, self::DROP_WHOLE_TAGS, true)) {
                    $node->removeChild($child);
                    continue;
                }

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap the element, keeping its (already-sanitised) text
                    // content but removing the container itself.
                    $wrapper = $node->ownerDocument->createDocumentFragment();
                    while ($child->firstChild) {
                        $wrapper->appendChild($child->firstChild);
                    }
                    $node->replaceChild($wrapper, $child);
                    continue;
                }

                // Whitelist attributes.
                $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
                $attributes = iterator_to_array($child->attributes);
                foreach ($attributes as $attribute) {
                    if (! in_array($attribute->nodeName, $allowed, true)) {
                        $child->removeAttribute($attribute->nodeName);
                    }
                }

                // Links must use an allowed scheme and force noopener.
                if ($tag === 'a') {
                    $href = trim((string) $child->getAttribute('href'));

                    if (! static::isSafeUrl($href)) {
                        $child->removeAttribute('href');
                        $child->removeAttribute('target');
                    } else {
                        $child->setAttribute('rel', 'noopener noreferrer');

                        $target = $child->getAttribute('target');
                        if (! in_array($target, ['_blank', '_self'], true)) {
                            $child->removeAttribute('target');
                        }
                    }
                }

                static::cleanNode($document, $child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            } elseif ($child->nodeType === XML_CDATA_SECTION_NODE) {
                $text = $document->createTextNode($child->nodeValue);
                $node->replaceChild($text, $child);
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }

    /**
     * Ensure the string is valid UTF-8 so DOMDocument serialisation is clean.
     */
    private static function toUtf8(string $value): string
    {
        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }

    /**
     * Remove all HTML tags (fallback when the DOM extension is unavailable).
     */
    private static function stripAll(string $html): string
    {
        return e(strip_tags($html));
    }

    /**
     * Collapse the extra whitespace DOMDocument introduces between blocks.
     */
    private static function tidy(string $html): string
    {
        return trim(preg_replace('/>\s+</', '><', $html) ?? $html);
    }
}
