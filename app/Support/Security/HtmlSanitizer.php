<?php

declare(strict_types=1);

namespace App\Support\Security;

/**
 * Explicit allowlist HTML sanitizer for CMS content.
 * Never trusts raw editor HTML.
 */
final class HtmlSanitizer
{
    /** @var list<string> */
    private array $allowedTags = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'em', 'b', 'i', 'u', 's', 'code', 'pre', 'blockquote',
        'ul', 'ol', 'li', 'a', 'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'div', 'span', 'section', 'article',
        'video', 'audio', 'source', 'iframe',
    ];

    /** @var array<string, list<string>> */
    private array $allowedAttributes = [
        'a' => ['href', 'title', 'rel', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'iframe' => ['src', 'width', 'height', 'allow', 'allowfullscreen', 'title'],
        'video' => ['src', 'controls', 'width', 'height', 'poster'],
        'audio' => ['src', 'controls'],
        'source' => ['src', 'type'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        '*' => ['class', 'id'],
    ];

    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="cms-sanitize-root">'.$html.'</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('cms-sanitize-root');
        if (! $root) {
            return '';
        }

        $this->cleanNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private function cleanNode(\DOMNode $node): void
    {
        if (! $node->hasChildNodes()) {
            return;
        }

        /** @var list<\DOMNode> $children */
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (! in_array($tag, $this->allowedTags, true)) {
                    // Unwrap disallowed element: keep children.
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                $this->cleanAttributes($child, $tag);
                $this->cleanNode($child);
            } elseif ($child instanceof \DOMComment) {
                $node->removeChild($child);
            }
        }
    }

    private function cleanAttributes(\DOMElement $element, string $tag): void
    {
        $allowed = array_unique(array_merge(
            $this->allowedAttributes['*'] ?? [],
            $this->allowedAttributes[$tag] ?? [],
        ));

        /** @var list<string> $names */
        $names = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $names[] = $attribute->name;
        }

        foreach ($names as $name) {
            $lower = strtolower($name);
            if (! in_array($lower, $allowed, true) || str_starts_with($lower, 'on')) {
                $element->removeAttribute($name);
                continue;
            }

            $value = $element->getAttribute($name);
            if (in_array($lower, ['href', 'src'], true) && preg_match('/^\s*javascript:/i', $value)) {
                $element->removeAttribute($name);
            }
        }
    }
}
