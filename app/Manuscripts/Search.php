<?php

namespace App\Manuscripts;

class Search
{
    public static function mergeJobWithWorkerResponse(array $job, array $response = [])
    {
        $jobPages = [];

        foreach ((array) ($job['pages'] ?? []) as $page) {
            $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;

            if ($pageNumber > 0) {
                $jobPages[$pageNumber] = $page;
            }
        }

        foreach ((array) ($response['pages'] ?? []) as $page) {
            $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;

            if ($pageNumber <= 0) {
                continue;
            }

            $current = isset($jobPages[$pageNumber]) && is_array($jobPages[$pageNumber]) ? $jobPages[$pageNumber] : [];
            $jobPages[$pageNumber] = static::mergePagePayload($current, $page);
        }

        ksort($jobPages);
        $job['pages'] = array_values($jobPages);

        return $job;
    }

    public static function buildViewModel(array $job, array $reviewData = [], $query = '')
    {
        $normalizedQuery = static::normalizeQuery($query);
        $pages = static::buildPages($job, $reviewData, $normalizedQuery);
        $matches = static::buildMatches($pages, $normalizedQuery);
        $viewerPages = array_values(array_filter($pages, function ($page) {
            return $page['image_url'] !== '';
        }));

        return [
            'query' => $normalizedQuery,
            'has_query' => $normalizedQuery !== '',
            'viewer_enabled' => !empty($viewerPages),
            'initial_page_number' => static::resolveInitialPageNumber($pages, $matches),
            'page_count' => count($pages),
            'pages_with_text_count' => count(array_filter($pages, function ($page) {
                return $page['effective_text'] !== '';
            })),
            'match_count' => count($matches),
            'matches' => $matches,
            'pages' => $pages,
        ];
    }

    public static function buildPages(array $job, array $reviewData = [], $query = '')
    {
        $pages = [];

        foreach ((array) ($job['pages'] ?? []) as $page) {
            $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;

            if ($pageNumber <= 0) {
                continue;
            }

            $review = isset($reviewData[$pageNumber]) && is_array($reviewData[$pageNumber]) ? $reviewData[$pageNumber] : [];
            $reviewedText = isset($review['transcription']) ? trim((string) $review['transcription']) : '';
            $rawText = static::extractRawText($page);
            $layoutData = static::extractLayoutData($page);
            $effectiveText = $reviewedText !== '' ? $reviewedText : $rawText;
            $effectiveTextSource = $reviewedText !== '' ? 'reviewed' : ($rawText !== '' ? 'raw' : 'none');
            $highlightBoxes = static::buildHighlightBoxes(
                $layoutData,
                $query,
                $effectiveText,
                $effectiveTextSource
            );

            $pages[] = [
                'page_number' => $pageNumber,
                'anchor_id' => 'manuscript-page-' . $pageNumber,
                'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
                'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
                'raw_text' => $rawText,
                'layout_data' => $layoutData,
                'layout_available' => !empty($layoutData['lines']),
                'effective_text_source' => $effectiveTextSource,
                'editorial_status' => !empty($review['status']) ? (string) $review['status'] : 'pendente',
                'effective_text' => $effectiveText,
                'effective_text_html' => static::buildHighlightedHtml($effectiveText, $query),
                'highlight_boxes' => $highlightBoxes,
                'highlight_box_count' => count($highlightBoxes),
                'review_notes' => isset($review['notes']) ? trim((string) $review['notes']) : '',
            ];
        }

        usort($pages, function ($left, $right) {
            return $left['page_number'] <=> $right['page_number'];
        });

        return $pages;
    }

    public static function buildHighlightedHtml($text, $query = '')
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $pattern = static::buildExactPattern($query);

        if (!$pattern) {
            return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }

        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (!is_array($parts)) {
            return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }

        $html = '';

        foreach ($parts as $index => $part) {
            $escapedPart = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
            $html .= $index % 2 === 1 ? '<mark>' . $escapedPart . '</mark>' : $escapedPart;
        }

        return nl2br($html);
    }

    protected static function buildMatches(array $pages, $query = '')
    {
        $pattern = static::buildExactPattern($query);

        if (!$pattern) {
            return [];
        }

        $matches = [];

        foreach ($pages as $page) {
            if ($page['effective_text'] === '') {
                continue;
            }

            $occurrences = preg_match_all($pattern, $page['effective_text'], $found);

            if (!$occurrences) {
                continue;
            }

            $matches[] = [
                'page_number' => $page['page_number'],
                'anchor_id' => $page['anchor_id'],
                'filename' => $page['filename'],
                'editorial_status' => $page['editorial_status'],
                'occurrences' => (int) $occurrences,
                'highlight_box_count' => isset($page['highlight_box_count']) ? (int) $page['highlight_box_count'] : 0,
                'excerpt_html' => static::buildExcerptHtml($page['effective_text'], $query),
            ];
        }

        return $matches;
    }

    protected static function extractRawText(array $page)
    {
        $candidates = [
            isset($page['raw_text']) ? $page['raw_text'] : '',
            isset($page['text']) ? $page['text'] : '',
        ];

        $layoutData = static::extractLayoutData($page);

        if (!empty($layoutData['text'])) {
            $candidates[] = $layoutData['text'];
        }

        if (!empty($layoutData['lines']) && is_array($layoutData['lines'])) {
            $lineTexts = array_values(array_filter(array_map(function ($line) {
                return isset($line['text']) ? trim((string) $line['text']) : '';
            }, $layoutData['lines'])));

            if (!empty($lineTexts)) {
                $candidates[] = implode(' ', $lineTexts);
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    protected static function extractLayoutData(array $page)
    {
        $layoutData = [];

        if (isset($page['layout_data']) && is_array($page['layout_data'])) {
            $layoutData = $page['layout_data'];
        } elseif (isset($page['htr']['layout_data']) && is_array($page['htr']['layout_data'])) {
            $layoutData = $page['htr']['layout_data'];
        }

        $layoutData['text'] = isset($layoutData['text']) ? trim((string) $layoutData['text']) : '';
        $layoutData['lines'] = isset($layoutData['lines']) && is_array($layoutData['lines']) ? $layoutData['lines'] : [];

        return $layoutData;
    }

    protected static function buildHighlightBoxes(array $layoutData, $query = '', $effectiveText = '', $effectiveTextSource = 'none')
    {
        $exactBoxes = static::buildExactHighlightBoxes($layoutData, $query);

        if (!empty($exactBoxes)) {
            return $exactBoxes;
        }

        if ($effectiveTextSource !== 'reviewed') {
            return [];
        }

        return static::buildApproximateLineHighlightBoxes($layoutData, $effectiveText, $query);
    }

    protected static function buildExactHighlightBoxes(array $layoutData, $query = '')
    {
        $pattern = static::buildExactPattern($query);

        if (!$pattern || empty($layoutData['lines']) || !is_array($layoutData['lines'])) {
            return [];
        }

        $boxes = [];

        foreach ($layoutData['lines'] as $line) {
            if (empty($line['words']) || !is_array($line['words'])) {
                continue;
            }

            foreach ($line['words'] as $word) {
                $wordText = isset($word['text']) ? trim((string) $word['text']) : '';
                $bbox = isset($word['bbox']) && is_array($word['bbox']) ? $word['bbox'] : [];

                if ($wordText === '' || !preg_match($pattern, $wordText) || empty($bbox)) {
                    continue;
                }

                $boxes[] = [
                    'text' => $wordText,
                    'x' => isset($bbox['x']) ? (float) $bbox['x'] : 0.0,
                    'y' => isset($bbox['y']) ? (float) $bbox['y'] : 0.0,
                    'width' => isset($bbox['width']) ? (float) $bbox['width'] : 0.0,
                    'height' => isset($bbox['height']) ? (float) $bbox['height'] : 0.0,
                    'approximate' => false,
                    'box_source' => 'exact-word',
                ];
            }
        }

        return array_values(array_filter($boxes, function ($box) {
            return $box['width'] > 0 && $box['height'] > 0;
        }));
    }

    protected static function buildApproximateLineHighlightBoxes(array $layoutData, $effectiveText, $query = '')
    {
        $pattern = static::buildExactPattern($query);

        if (!$pattern || empty($layoutData['lines']) || !is_array($layoutData['lines'])) {
            return [];
        }

        $matchOffsets = static::collectMatchOffsets($effectiveText, $pattern);

        if (empty($matchOffsets)) {
            return [];
        }

        $lineSegments = static::buildLineSegments($layoutData['lines']);

        if (empty($lineSegments)) {
            return [];
        }

        $totalLayoutLength = array_reduce($lineSegments, function ($carry, $segment) {
            return $carry + max(1, (int) $segment['length']);
        }, 0);

        $totalEffectiveLength = max(1, static::stringLength((string) $effectiveText));
        $boxes = [];

        foreach ($matchOffsets as $offset) {
            $target = ((float) $offset / $totalEffectiveLength) * max(1, $totalLayoutLength - 1);
            $running = 0;
            $selected = null;

            foreach ($lineSegments as $index => $segment) {
                $running += max(1, (int) $segment['length']);

                if ($target <= $running || $index === array_key_last($lineSegments)) {
                    $selected = $segment;
                    break;
                }
            }

            if (!$selected || empty($selected['bbox'])) {
                continue;
            }

            $bbox = $selected['bbox'];
            $boxes[$selected['index']] = [
                'text' => isset($selected['text']) ? (string) $selected['text'] : '',
                'x' => isset($bbox['x']) ? (float) $bbox['x'] : 0.0,
                'y' => isset($bbox['y']) ? (float) $bbox['y'] : 0.0,
                'width' => isset($bbox['width']) ? (float) $bbox['width'] : 0.0,
                'height' => isset($bbox['height']) ? (float) $bbox['height'] : 0.0,
                'approximate' => true,
                'box_source' => 'approx-line',
            ];
        }

        return array_values(array_filter($boxes, function ($box) {
            return $box['width'] > 0 && $box['height'] > 0;
        }));
    }

    protected static function collectMatchOffsets($text, $pattern)
    {
        $found = [];
        $matches = preg_match_all($pattern, (string) $text, $found, PREG_OFFSET_CAPTURE);

        if (!$matches || empty($found[0])) {
            return [];
        }

        return array_values(array_map(function ($match) use ($text) {
            $byteOffset = isset($match[1]) ? (int) $match[1] : 0;

            return static::stringLength(substr((string) $text, 0, $byteOffset));
        }, $found[0]));
    }

    protected static function buildLineSegments(array $lines)
    {
        $segments = [];

        foreach ($lines as $index => $line) {
            $text = isset($line['text']) ? trim((string) $line['text']) : '';
            $bbox = isset($line['bbox']) && is_array($line['bbox']) ? $line['bbox'] : static::deriveLineBbox($line);

            if (empty($bbox)) {
                continue;
            }

            $segments[] = [
                'index' => (int) $index,
                'text' => $text,
                'length' => max(1, static::stringLength($text)),
                'bbox' => $bbox,
            ];
        }

        return $segments;
    }

    protected static function deriveLineBbox(array $line)
    {
        if (empty($line['words']) || !is_array($line['words'])) {
            return [];
        }

        $minX = null;
        $minY = null;
        $maxX = null;
        $maxY = null;

        foreach ($line['words'] as $word) {
            $bbox = isset($word['bbox']) && is_array($word['bbox']) ? $word['bbox'] : [];

            if (empty($bbox)) {
                continue;
            }

            $x = isset($bbox['x']) ? (float) $bbox['x'] : 0.0;
            $y = isset($bbox['y']) ? (float) $bbox['y'] : 0.0;
            $width = isset($bbox['width']) ? (float) $bbox['width'] : 0.0;
            $height = isset($bbox['height']) ? (float) $bbox['height'] : 0.0;

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            $minX = $minX === null ? $x : min($minX, $x);
            $minY = $minY === null ? $y : min($minY, $y);
            $maxX = $maxX === null ? ($x + $width) : max($maxX, $x + $width);
            $maxY = $maxY === null ? ($y + $height) : max($maxY, $y + $height);
        }

        if ($minX === null || $minY === null || $maxX === null || $maxY === null) {
            return [];
        }

        return [
            'x' => $minX,
            'y' => $minY,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
        ];
    }

    protected static function mergePagePayload(array $current, array $incoming)
    {
        $merged = array_merge($current, $incoming);

        if (empty($merged['raw_text'])) {
            if (!empty($incoming['raw_text'])) {
                $merged['raw_text'] = $incoming['raw_text'];
            } elseif (!empty($incoming['text'])) {
                $merged['raw_text'] = $incoming['text'];
            }
        }

        if (
            (!isset($merged['layout_data']) || !is_array($merged['layout_data']) || empty($merged['layout_data']['lines'])) &&
            isset($incoming['layout_data']) &&
            is_array($incoming['layout_data'])
        ) {
            $merged['layout_data'] = $incoming['layout_data'];
        }

        if (
            (!isset($merged['image_url']) || $merged['image_url'] === '') &&
            !empty($incoming['image_url'])
        ) {
            $merged['image_url'] = $incoming['image_url'];
        }

        return $merged;
    }

    protected static function resolveInitialPageNumber(array $pages, array $matches)
    {
        if (!empty($matches[0]['page_number'])) {
            return (int) $matches[0]['page_number'];
        }

        foreach ($pages as $page) {
            if (!empty($page['image_url'])) {
                return (int) $page['page_number'];
            }
        }

        return !empty($pages[0]['page_number']) ? (int) $pages[0]['page_number'] : 0;
    }

    protected static function buildExcerptHtml($text, $query, $radius = 70)
    {
        $pattern = static::buildExactPattern($query);

        if (!$pattern || !preg_match($pattern, $text, $firstMatch, PREG_OFFSET_CAPTURE)) {
            return static::buildHighlightedHtml($text, '');
        }

        $byteOffset = (int) $firstMatch[0][1];
        $matchLength = static::stringLength((string) $firstMatch[0][0]);
        $start = max(0, static::stringLength(substr($text, 0, $byteOffset)) - $radius);
        $totalLength = static::stringLength($text);
        $excerptLength = min($totalLength - $start, ($radius * 2) + $matchLength);
        $excerpt = static::stringSlice($text, $start, $excerptLength);
        $prefix = $start > 0 ? '...' : '';
        $suffix = ($start + $excerptLength) < $totalLength ? '...' : '';

        return $prefix . static::buildHighlightedHtml($excerpt, $query) . $suffix;
    }

    protected static function buildExactPattern($query)
    {
        $normalizedQuery = static::normalizeQuery($query);

        if ($normalizedQuery === '') {
            return null;
        }

        $parts = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($parts)) {
            return null;
        }

        return '/(?<![\pL\pN])(' . implode('\s+', array_map(function ($part) {
            return preg_quote($part, '/');
        }, $parts)) . ')(?![\pL\pN])/iu';
    }

    protected static function normalizeQuery($query)
    {
        $query = trim((string) $query);

        if ($query === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', $query) ?: '';
    }

    protected static function stringLength($value)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen((string) $value, 'UTF-8');
        }

        return strlen((string) $value);
    }

    protected static function stringSlice($value, $start, $length = null)
    {
        if (function_exists('mb_substr')) {
            return mb_substr((string) $value, (int) $start, $length, 'UTF-8');
        }

        return $length === null
            ? substr((string) $value, (int) $start)
            : substr((string) $value, (int) $start, (int) $length);
    }
}
