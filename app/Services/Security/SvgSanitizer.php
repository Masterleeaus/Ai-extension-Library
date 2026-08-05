<?php

declare(strict_types=1);

namespace App\Services\Security;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

final class SvgSanitizer
{
    public const MAX_BYTES = 8_388_608;

    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    /** @var array<string, true> */
    private const ALLOWED_ELEMENTS = [
        'svg' => true,
        'g' => true,
        'defs' => true,
        'title' => true,
        'desc' => true,
        'path' => true,
        'rect' => true,
        'circle' => true,
        'ellipse' => true,
        'line' => true,
        'polyline' => true,
        'polygon' => true,
        'text' => true,
        'tspan' => true,
        'textPath' => true,
        'clipPath' => true,
        'mask' => true,
        'linearGradient' => true,
        'radialGradient' => true,
        'stop' => true,
        'pattern' => true,
        'symbol' => true,
        'use' => true,
        'filter' => true,
        'feBlend' => true,
        'feColorMatrix' => true,
        'feComponentTransfer' => true,
        'feComposite' => true,
        'feConvolveMatrix' => true,
        'feDiffuseLighting' => true,
        'feDisplacementMap' => true,
        'feDistantLight' => true,
        'feDropShadow' => true,
        'feFlood' => true,
        'feFuncA' => true,
        'feFuncB' => true,
        'feFuncG' => true,
        'feFuncR' => true,
        'feGaussianBlur' => true,
        'feMerge' => true,
        'feMergeNode' => true,
        'feMorphology' => true,
        'feOffset' => true,
        'fePointLight' => true,
        'feSpecularLighting' => true,
        'feSpotLight' => true,
        'feTile' => true,
        'feTurbulence' => true,
    ];

    /** @var array<string, true> */
    private const ALLOWED_ATTRIBUTES = [
        'id' => true,
        'class' => true,
        'x' => true,
        'y' => true,
        'x1' => true,
        'y1' => true,
        'x2' => true,
        'y2' => true,
        'cx' => true,
        'cy' => true,
        'r' => true,
        'rx' => true,
        'ry' => true,
        'width' => true,
        'height' => true,
        'd' => true,
        'points' => true,
        'pathLength' => true,
        'viewBox' => true,
        'preserveAspectRatio' => true,
        'transform' => true,
        'opacity' => true,
        'display' => true,
        'visibility' => true,
        'fill' => true,
        'fill-opacity' => true,
        'fill-rule' => true,
        'stroke' => true,
        'stroke-width' => true,
        'stroke-opacity' => true,
        'stroke-linecap' => true,
        'stroke-linejoin' => true,
        'stroke-miterlimit' => true,
        'stroke-dasharray' => true,
        'stroke-dashoffset' => true,
        'vector-effect' => true,
        'paint-order' => true,
        'clip-path' => true,
        'clip-rule' => true,
        'mask' => true,
        'filter' => true,
        'marker-start' => true,
        'marker-mid' => true,
        'marker-end' => true,
        'color' => true,
        'color-interpolation' => true,
        'color-interpolation-filters' => true,
        'stop-color' => true,
        'stop-opacity' => true,
        'offset' => true,
        'gradientUnits' => true,
        'gradientTransform' => true,
        'spreadMethod' => true,
        'fx' => true,
        'fy' => true,
        'fr' => true,
        'patternUnits' => true,
        'patternContentUnits' => true,
        'patternTransform' => true,
        'font-family' => true,
        'font-size' => true,
        'font-weight' => true,
        'font-style' => true,
        'text-anchor' => true,
        'dominant-baseline' => true,
        'alignment-baseline' => true,
        'baseline-shift' => true,
        'letter-spacing' => true,
        'word-spacing' => true,
        'writing-mode' => true,
        'direction' => true,
        'unicode-bidi' => true,
        'textLength' => true,
        'lengthAdjust' => true,
        'startOffset' => true,
        'method' => true,
        'spacing' => true,
        'href' => true,
        'xlink:href' => true,
        'in' => true,
        'in2' => true,
        'result' => true,
        'mode' => true,
        'type' => true,
        'values' => true,
        'operator' => true,
        'k1' => true,
        'k2' => true,
        'k3' => true,
        'k4' => true,
        'stdDeviation' => true,
        'edgeMode' => true,
        'kernelMatrix' => true,
        'kernelUnitLength' => true,
        'order' => true,
        'divisor' => true,
        'bias' => true,
        'targetX' => true,
        'targetY' => true,
        'preserveAlpha' => true,
        'surfaceScale' => true,
        'diffuseConstant' => true,
        'specularConstant' => true,
        'specularExponent' => true,
        'limitingConeAngle' => true,
        'azimuth' => true,
        'elevation' => true,
        'scale' => true,
        'xChannelSelector' => true,
        'yChannelSelector' => true,
        'baseFrequency' => true,
        'numOctaves' => true,
        'seed' => true,
        'stitchTiles' => true,
        'flood-color' => true,
        'flood-opacity' => true,
        'lighting-color' => true,
        'radius' => true,
        'xml:space' => true,
    ];

    /** @var array<string, true> */
    private const LOCAL_REFERENCE_ATTRIBUTES = [
        'clip-path' => true,
        'filter' => true,
        'fill' => true,
        'marker-end' => true,
        'marker-mid' => true,
        'marker-start' => true,
        'mask' => true,
        'stroke' => true,
    ];

    public function sanitize(string $svg): string
    {
        if ($svg === '' || strlen($svg) > self::MAX_BYTES) {
            throw new RuntimeException('SVG size is invalid.');
        }

        if (str_contains($svg, "\0") || preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $svg) === 1) {
            throw new RuntimeException('DTD and entity declarations are not allowed in SVG.');
        }

        if (preg_match('//u', $svg) !== 1) {
            throw new RuntimeException('SVG must contain valid UTF-8 XML.');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document = new DOMDocument('1.0', 'UTF-8');
            $document->preserveWhiteSpace = false;
            $document->formatOutput = false;

            $loaded = $document->loadXML(
                $svg,
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT
            );

            if (! $loaded || libxml_get_errors() !== []) {
                throw new RuntimeException('SVG XML is malformed.');
            }

            $root = $document->documentElement;
            if (! $root instanceof DOMElement
                || $root->localName !== 'svg'
                || $root->namespaceURI !== self::SVG_NAMESPACE) {
                throw new RuntimeException('Document root must be an SVG element.');
            }

            $this->removeCommentsAndProcessingInstructions($document);
            $this->sanitizeElements($document, $root);

            $output = $document->saveXML($root);
            if (! is_string($output) || $output === '') {
                throw new RuntimeException('Sanitized SVG could not be serialized.');
            }

            if (preg_match('/<\s*(?:script|style|foreignObject|iframe|object|embed|image|audio|video)\b/i', $output) === 1
                || preg_match('/\son[a-z0-9_-]+\s*=/i', $output) === 1
                || preg_match('/(?:javascript|vbscript|data|file)\s*:/i', $output) === 1) {
                throw new RuntimeException('Sanitized SVG retained unsafe content.');
            }

            return $output;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function removeCommentsAndProcessingInstructions(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//comment() | //processing-instruction()');

        if ($nodes === false) {
            return;
        }

        $remove = [];
        foreach ($nodes as $node) {
            $remove[] = $node;
        }

        foreach ($remove as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    private function sanitizeElements(DOMDocument $document, DOMElement $root): void
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*');

        if ($nodes === false) {
            throw new RuntimeException('SVG elements could not be inspected.');
        }

        $elements = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        foreach ($elements as $element) {
            if (! $element->isSameNode($root)
                && ($element->namespaceURI !== self::SVG_NAMESPACE
                    || ! isset(self::ALLOWED_ELEMENTS[$element->localName]))) {
                $element->parentNode?->removeChild($element);

                continue;
            }

            $this->sanitizeAttributes($element, $root);
        }
    }

    private function sanitizeAttributes(DOMElement $element, DOMElement $root): void
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            if ($attribute instanceof DOMAttr) {
                $attributes[] = $attribute;
            }
        }

        foreach ($attributes as $attribute) {
            $name = $attribute->nodeName;
            $localName = $attribute->localName;
            $value = trim($attribute->value);

            if ($this->isAllowedNamespaceDeclaration($element, $root, $attribute)) {
                continue;
            }

            if (str_starts_with(strtolower($name), 'on')
                || strtolower($localName) === 'style'
                || ! isset(self::ALLOWED_ATTRIBUTES[$name])) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($name === 'href' || $name === 'xlink:href') {
                if (! in_array($element->localName, ['use', 'textPath'], true)
                    || preg_match('/^#[A-Za-z_][A-Za-z0-9_.:-]*$/', $value) !== 1) {
                    $element->removeAttributeNode($attribute);
                }

                continue;
            }

            if ($this->containsUnsafeValue($value)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if (isset(self::LOCAL_REFERENCE_ATTRIBUTES[$name])
                && stripos($value, 'url(') !== false
                && preg_match('/^url\(\s*["\']?#[A-Za-z_][A-Za-z0-9_.:-]*["\']?\s*\)$/', $value) !== 1) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function isAllowedNamespaceDeclaration(
        DOMElement $element,
        DOMElement $root,
        DOMAttr $attribute,
    ): bool {
        if (! $element->isSameNode($root)) {
            return false;
        }

        return ($attribute->nodeName === 'xmlns' && $attribute->value === self::SVG_NAMESPACE)
            || ($attribute->nodeName === 'xmlns:xlink' && $attribute->value === self::XLINK_NAMESPACE);
    }

    private function containsUnsafeValue(string $value): bool
    {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
            return true;
        }

        return preg_match(
            '/(?:javascript|vbscript|data|file|https?|ftp)\s*:|^\s*\/\/|@import|expression\s*\(/i',
            $value,
        ) === 1;
    }
}
