<?php

namespace App\Support\VictoryGames;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class NativeAiuxHtmlSanitizer
{
    private const AGENT_NOISY_TAGS = [
        'script',
        'style',
        'noscript',
        'svg',
        'canvas',
        'img',
        'picture',
        'source',
        'video',
        'audio',
        'iframe',
        'object',
        'embed',
        'meta',
        'link',
    ];

    private const POSTMORTEM_NOISY_TAGS = [
        'script',
        'noscript',
        'canvas',
        'iframe',
        'object',
        'embed',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'id',
        'class',
        'name',
        'type',
        'value',
        'placeholder',
        'href',
        'role',
        'for',
        'title',
        'alt',
        'checked',
        'disabled',
        'required',
        'readonly',
        'multiple',
        'selected',
        'method',
        'action',
    ];

    private const POSTMORTEM_ALLOWED_ATTRIBUTES = [
        'src',
        'srcset',
        'sizes',
        'rel',
        'content',
        'charset',
        'http-equiv',
    ];

    private const ALLOWED_DATA_ATTRIBUTES = [
        'data-testid',
        'data-test',
        'data-qa',
        'data-cy',
    ];

    public function sanitize(string $html, string $mode = 'agent'): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);

        foreach ($this->noisyTagsFor($mode) as $tagName) {
            foreach (iterator_to_array($xpath->query('//'.$tagName) ?: []) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach (iterator_to_array($xpath->query('//comment()') ?: []) as $comment) {
            $comment->parentNode?->removeChild($comment);
        }

        foreach (iterator_to_array($xpath->query('//*') ?: []) as $element) {
            if ($element instanceof DOMElement) {
                $this->filterAttributes($element, $mode);
            }
        }

        $sanitized = $document->saveHTML() ?: '';

        return trim($sanitized);
    }

    public function truncate(string $html, int $limit): string
    {
        if (mb_strlen($html) <= $limit) {
            return $html;
        }

        return mb_substr($html, 0, $limit).'...';
    }

    private function filterAttributes(DOMElement $element, string $mode): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES;

        if ($mode === 'postmortem') {
            $allowed = [...$allowed, ...self::POSTMORTEM_ALLOWED_ATTRIBUTES];
        }

        $allowed = array_flip($allowed);
        $allowedDataAttributes = array_flip(self::ALLOWED_DATA_ATTRIBUTES);

        $attributeNames = [];

        foreach ($element->attributes ?? [] as $attribute) {
            $attributeNames[] = $attribute->nodeName;
        }

        foreach ($attributeNames as $attributeName) {
            $lowered = strtolower($attributeName);

            if (
                isset($allowed[$lowered])
                || str_starts_with($lowered, 'aria-')
                || isset($allowedDataAttributes[$lowered])
            ) {
                continue;
            }

            $element->removeAttribute($attributeName);
        }
    }

    private function noisyTagsFor(string $mode): array
    {
        return $mode === 'postmortem'
            ? self::POSTMORTEM_NOISY_TAGS
            : self::AGENT_NOISY_TAGS;
    }
}
