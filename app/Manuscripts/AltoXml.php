<?php

namespace App\Manuscripts;

class AltoXml
{
    public static function parse($xml)
    {
        $previousLibxmlState = libxml_use_internal_errors(true);
        $document = simplexml_load_string((string) $xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        if (!$document) {
            throw new \InvalidArgumentException('Invalid ALTO XML.');
        }

        $namespace = self::detectNamespace($document);
        $document->registerXPathNamespace('a', $namespace);
        $pages = $document->xpath('//a:Layout/a:Page');

        if ($pages === false || count($pages) === 0) {
            throw new \InvalidArgumentException('ALTO XML does not expose the expected page structure.');
        }

        if (count($pages) !== 1) {
            throw new \InvalidArgumentException('ALTO XML must contain exactly one page.');
        }

        $page = $pages[0];
        $page->registerXPathNamespace('a', $namespace);
        $lines = [];
        $fullText = [];

        foreach ($page->xpath('.//a:TextLine') ?: [] as $lineNode) {
            $lineNode->registerXPathNamespace('a', $namespace);
            $stringNodes = $lineNode->xpath('./a:String') ?: [];
            $lineText = trim(implode(' ', array_map(function ($stringNode) {
                return trim((string) $stringNode['CONTENT']);
            }, $stringNodes)));

            $wordNodes = [];
            foreach ($stringNodes as $stringNode) {
                $wordNodes[] = [
                    'text' => trim((string) $stringNode['CONTENT']),
                    'bbox' => [
                        'x' => (float) ($stringNode['HPOS'] ?: $lineNode['HPOS']),
                        'y' => (float) ($stringNode['VPOS'] ?: $lineNode['VPOS']),
                        'width' => (float) ($stringNode['WIDTH'] ?: 0),
                        'height' => (float) ($stringNode['HEIGHT'] ?: $lineNode['HEIGHT']),
                    ],
                ];
            }

            $lines[] = [
                'text' => $lineText,
                'bbox' => [
                    'x' => (float) $lineNode['HPOS'],
                    'y' => (float) $lineNode['VPOS'],
                    'width' => (float) $lineNode['WIDTH'],
                    'height' => (float) $lineNode['HEIGHT'],
                ],
                'words' => array_values(array_filter($wordNodes, function ($word) {
                    return $word['text'] !== '';
                })),
            ];
            $fullText[] = $lineText;
        }

        return [
            'page' => [
                'width' => $page ? (int) $page['WIDTH'] : 0,
                'height' => $page ? (int) $page['HEIGHT'] : 0,
            ],
            'text' => trim(implode(' ', array_filter($fullText))),
            'lines' => $lines,
        ];
    }

    protected static function detectNamespace(\SimpleXMLElement $document)
    {
        $namespaces = $document->getDocNamespaces();
        $namespace = isset($namespaces['']) ? (string) $namespaces[''] : '';

        if (preg_match('~^http://www\.loc\.gov/standards/alto/ns-v[0-9]+#$~', $namespace)) {
            return $namespace;
        }

        throw new \InvalidArgumentException('Unsupported ALTO XML namespace.');
    }
}
