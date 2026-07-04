<?php

namespace App\Manuscripts;

class AltoXml
{
    const ALTO_NAMESPACE = 'http://www.loc.gov/standards/alto/ns-v4#';

    public static function parse($xml)
    {
        $document = simplexml_load_string((string) $xml);

        if (!$document) {
            throw new \InvalidArgumentException('Invalid ALTO XML.');
        }

        $document->registerXPathNamespace('a', self::ALTO_NAMESPACE);
        $pages = $document->xpath('//a:Page');
        $page = !empty($pages[0]) ? $pages[0] : null;
        $lines = [];
        $fullText = [];

        foreach ($document->xpath('//a:TextLine') as $lineNode) {
            $lineNode->registerXPathNamespace('a', self::ALTO_NAMESPACE);
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
}
