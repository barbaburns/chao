# Manuscript ZIP Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the manuscript `PDF + worker` pipeline with a WordPress-native `.zip` import flow that ingests page images plus paired `ALTO XML`, persists page snapshots, and powers frontend search/highlights without exposing transcription text.

**Architecture:** The implementation introduces a small import pipeline inside `app/Manuscripts`: one unit reads and validates the ZIP manifest, one unit parses `ALTO XML` into the internal `layout_data` contract, and the admin layer orchestrates explicit imports into post meta snapshots. The frontend keeps using `Search::buildViewModel()`, but its source of truth changes from worker snapshots to imported page snapshots, with the viewer payload restored to include highlight boxes.

**Tech Stack:** WordPress admin actions and post meta, ACF JSON, PHP `ZipArchive`/`SimpleXML`, existing manuscript `Search` + `Frontend` classes, Blade, OpenSeadragon route JS, PHP-based repository tests.

---

## File Structure

- Create: `resources/acf-json/group_20260704_manuscrito_zip.json` as a manuscript-specific ACF group containing the `.zip` source field.
- Modify: `app/Manuscripts/Admin.php` to replace worker-centric admin actions, notices, metabox copy, snapshot builders, and page table helpers with ZIP import equivalents.
- Modify: `app/Manuscripts/Frontend.php` to load imported page snapshots from post meta instead of worker response meta.
- Modify: `app/Manuscripts/Search.php` only where necessary to consume imported page payloads and preserve highlight-box output for the viewer.
- Modify: `resources/views/partials/content-single-manuscrito.blade.php` to serialize `highlightBoxes` again while still hiding full transcription from the public view.
- Modify: `resources/assets/scripts/routes/single-manuscrito.js` to render and clear OpenSeadragon overlays for the active page.
- Modify: `resources/assets/styles/layouts/_manuscripts.scss` to style viewer highlight overlays if required.
- Create: `app/Manuscripts/ZipImport.php` as the orchestration unit that opens the `.zip`, validates pairs, and returns a normalized snapshot payload.
- Create: `app/Manuscripts/AltoXml.php` as the parser that converts `ALTO XML` into `raw_text + layout_data`.
- Create: `tests/manuscripts/alto_xml_test.php` for parser coverage.
- Create: `tests/manuscripts/zip_import_test.php` for ZIP pairing, ordering, and validation coverage.
- Create: `tests/manuscripts/admin_zip_import_test.php` for admin snapshot shaping and page-row helpers.
- Modify: `tests/manuscripts/admin_process_url_test.php` to cover the new import action URL and request parsing.
- Modify: `tests/manuscripts/search_test.php` to keep highlight behavior aligned with imported page payloads.
- Modify: `tests/manuscripts/single_manuscrito_viewer_markup_test.php` to assert the viewer payload includes `highlightBoxes` and overlay hooks.

### Task 1: Replace the Manuscript Admin Contract

**Files:**
- Modify: `app/Manuscripts/Admin.php`
- Modify: `tests/manuscripts/admin_process_url_test.php`
- Create: `tests/manuscripts/admin_zip_import_test.php`
- Create: `resources/acf-json/group_20260704_manuscrito_zip.json`

- [ ] **Step 1: Write the failing admin action/notice tests**

```php
<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\Admin;

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$url = Admin::buildImportUrl(77, 'nonce-value', 'http://example.test/wp-admin/');
$editUrl = Admin::buildEditUrl(77, [
    'chao_manuscript_import_notice' => 'success',
], 'http://example.test/wp-admin/');
$requestData = Admin::extractImportRequestData([
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
]);
$notice = Admin::getNoticeConfig('success');

assertTrueValue(strpos($url, 'action=chao_import_manuscript_zip') !== false, 'Import URL should include the ZIP action');
assertTrueValue(strpos($url, 'chao_manuscript_import_nonce=nonce-value') !== false, 'Import URL should include the ZIP nonce');
assertTrueValue(strpos($editUrl, 'chao_manuscript_import_notice=success') !== false, 'Edit URL should include ZIP import notice status');
assertTrueValue($requestData['post_id'] === 77, 'Import request parser should read post_id');
assertTrueValue($requestData['nonce'] === 'nonce-value', 'Import request parser should read ZIP nonce');
assertTrueValue($notice['type'] === 'success', 'ZIP success notice should use success type');

echo 'admin_process_url_test: ok' . PHP_EOL;
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/manuscripts/admin_process_url_test.php`

Expected: FAIL with an error like `Call to undefined method App\Manuscripts\Admin::buildImportUrl()`.

- [ ] **Step 3: Write the failing admin snapshot/page-row test**

```php
<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\Admin;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$snapshot = Admin::buildImportSnapshot([
    'zip_name' => 'venancia.zip',
    'page_count' => 2,
    'pages' => [
        [
            'page_number' => 1,
            'basename' => 'venanciaCaderno_0051B',
            'filename' => 'venanciaCaderno_0051B.jpg',
            'image_url' => 'http://example.test/uploads/venanciaCaderno_0051B.jpg',
            'raw_text' => 'Os dias que passei com',
            'layout_data' => [
                'text' => 'Os dias que passei com',
                'lines' => [],
            ],
            'highlight_exact' => false,
            'import_status' => 'imported',
            'import_errors' => [],
        ],
    ],
    'errors' => [],
]);

$rows = Admin::buildImportedPageRows($snapshot, []);

assertSameValue('venancia.zip', $snapshot['zip_name'], 'Snapshot should keep ZIP filename');
assertSameValue(2, $snapshot['page_count'], 'Snapshot should keep page count');
assertSameValue('venanciaCaderno_0051B', $snapshot['pages'][0]['basename'], 'Snapshot should keep basename');
assertSameValue('imported', $rows[0]['import_status'], 'Rows should keep import status');
assertSameValue('aproximado', $rows[0]['highlight_status'], 'Rows should mark pages without exact word geometry as approximate');

echo 'admin_zip_import_test: ok' . PHP_EOL;
```

- [ ] **Step 4: Run test to verify it fails**

Run: `php tests/manuscripts/admin_zip_import_test.php`

Expected: FAIL with an error like `Call to undefined method App\Manuscripts\Admin::buildImportSnapshot()`.

- [ ] **Step 5: Write the minimal admin implementation**

```php
// app/Manuscripts/Admin.php
add_action('admin_post_chao_import_manuscript_zip', [static::class, 'importZip']);

public static function buildImportUrl($postId, $nonce, $adminBaseUrl = null)
{
    $adminBaseUrl = $adminBaseUrl ?: admin_url();
    $basePath = rtrim($adminBaseUrl, '/') . '/admin-post.php';
    $queryArgs = [
        'action' => 'chao_import_manuscript_zip',
        'post_id' => (int) $postId,
        'chao_manuscript_import_nonce' => $nonce,
    ];

    return function_exists('add_query_arg')
        ? add_query_arg($queryArgs, $basePath)
        : $basePath . '?' . http_build_query($queryArgs);
}

public static function extractImportRequestData(array $request)
{
    return [
        'post_id' => isset($request['post_id']) ? (int) $request['post_id'] : 0,
        'nonce' => isset($request['chao_manuscript_import_nonce']) ? (string) $request['chao_manuscript_import_nonce'] : '',
    ];
}

public static function buildImportSnapshot(array $result)
{
    return [
        'zip_name' => isset($result['zip_name']) ? (string) $result['zip_name'] : '',
        'page_count' => isset($result['page_count']) ? (int) $result['page_count'] : count((array) ($result['pages'] ?? [])),
        'pages' => array_values((array) ($result['pages'] ?? [])),
        'errors' => array_values((array) ($result['errors'] ?? [])),
        'stored_at' => static::currentTimestamp(),
    ];
}

public static function buildImportedPageRows(array $snapshot, array $reviewData = [])
{
    return array_map(function ($page) use ($reviewData) {
        $pageNumber = isset($page['page_number']) ? (int) $page['page_number'] : 0;
        $review = isset($reviewData[$pageNumber]) && is_array($reviewData[$pageNumber]) ? $reviewData[$pageNumber] : [];

        return [
            'page_number' => $pageNumber,
            'basename' => isset($page['basename']) ? (string) $page['basename'] : '',
            'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
            'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
            'raw_text' => isset($page['raw_text']) ? (string) $page['raw_text'] : '',
            'import_status' => isset($page['import_status']) ? (string) $page['import_status'] : 'pending',
            'highlight_status' => !empty($page['highlight_exact']) ? 'exato' : 'aproximado',
            'editorial_status' => !empty($review['status']) ? (string) $review['status'] : 'pendente',
        ];
    }, (array) ($snapshot['pages'] ?? []));
}
```

- [ ] **Step 6: Add the manuscript ZIP field definition**

```json
{
  "key": "group_20260704_manuscrito_zip",
  "title": "Importacao de manuscrito",
  "fields": [
    {
      "key": "field_manuscript_zip_file",
      "label": "Pacote ZIP do manuscrito",
      "name": "manuscript_zip_file",
      "type": "file",
      "return_format": "id",
      "library": "all",
      "mime_types": "zip"
    }
  ],
  "location": [
    [
      {
        "param": "post_type",
        "operator": "==",
        "value": "manuscrito"
      }
    ]
  ],
  "position": "normal",
  "style": "default"
}
```

- [ ] **Step 7: Run the admin tests to verify they pass**

Run:

```bash
php tests/manuscripts/admin_process_url_test.php
php tests/manuscripts/admin_zip_import_test.php
```

Expected:

- `admin_process_url_test: ok`
- `admin_zip_import_test: ok`

- [ ] **Step 8: Commit**

```bash
git add app/Manuscripts/Admin.php tests/manuscripts/admin_process_url_test.php tests/manuscripts/admin_zip_import_test.php resources/acf-json/group_20260704_manuscrito_zip.json
git commit -m "feat: add manuscript zip import admin contract"
```

### Task 2: Build the ALTO XML Parser

**Files:**
- Create: `app/Manuscripts/AltoXml.php`
- Create: `tests/manuscripts/alto_xml_test.php`

- [ ] **Step 1: Write the failing ALTO parser test**

```php
<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\AltoXml;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<alto xmlns="http://www.loc.gov/standards/alto/ns-v4#">
  <Layout>
    <Page WIDTH="6304" HEIGHT="8739">
      <PrintSpace>
        <TextBlock>
          <TextLine HPOS="488" VPOS="371" WIDTH="3826" HEIGHT="3088">
            <String CONTENT="Os"/>
            <SP WIDTH="20"/>
            <String CONTENT="dias"/>
          </TextLine>
        </TextBlock>
      </PrintSpace>
    </Page>
  </Layout>
</alto>
XML;

$result = AltoXml::parse($xml);

assertSameValue('Os dias', $result['text'], 'Parser should concatenate line text');
assertSameValue(6304, $result['page']['width'], 'Parser should keep page width');
assertSameValue(8739, $result['page']['height'], 'Parser should keep page height');
assertSameValue('Os dias', $result['lines'][0]['text'], 'Parser should keep line text');
assertSameValue(488.0, $result['lines'][0]['bbox']['x'], 'Parser should keep line x');
assertSameValue('Os', $result['lines'][0]['words'][0]['text'], 'Parser should create word entries');

echo 'alto_xml_test: ok' . PHP_EOL;
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/manuscripts/alto_xml_test.php`

Expected: FAIL with `Class "App\Manuscripts\AltoXml" not found`.

- [ ] **Step 3: Write the minimal parser**

```php
<?php

namespace App\Manuscripts;

class AltoXml
{
    public static function parse($xml)
    {
        $document = simplexml_load_string((string) $xml);

        if (!$document) {
            throw new \InvalidArgumentException('Invalid ALTO XML.');
        }

        $document->registerXPathNamespace('a', 'http://www.loc.gov/standards/alto/ns-v4#');
        $pages = $document->xpath('//a:Page');
        $page = !empty($pages[0]) ? $pages[0] : null;
        $lines = [];
        $fullText = [];

        foreach ($document->xpath('//a:TextLine') as $lineNode) {
            $lineText = trim(implode(' ', array_map(function ($stringNode) {
                return trim((string) $stringNode['CONTENT']);
            }, iterator_to_array($lineNode->xpath('./a:String')))));

            $wordNodes = [];
            foreach ($lineNode->xpath('./a:String') as $stringNode) {
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/manuscripts/alto_xml_test.php`

Expected: `alto_xml_test: ok`

- [ ] **Step 5: Commit**

```bash
git add app/Manuscripts/AltoXml.php tests/manuscripts/alto_xml_test.php
git commit -m "feat: parse alto xml for manuscript pages"
```

### Task 3: Build the ZIP Import Pipeline and Snapshot Persistence

**Files:**
- Create: `app/Manuscripts/ZipImport.php`
- Modify: `app/Manuscripts/Admin.php`
- Create: `tests/manuscripts/zip_import_test.php`

- [ ] **Step 1: Write the failing ZIP import test**

```php
<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\ZipImport;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$zipPath = tempnam(sys_get_temp_dir(), 'manuscript-zip-') . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('venanciaCaderno_0051B.jpg', 'fake-image');
$zip->addFromString('venanciaCaderno_0051B.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Casa"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');
$zip->close();

$result = ZipImport::fromZipPath($zipPath);

assertSameValue(1, $result['page_count'], 'ZIP import should find one valid page pair');
assertSameValue('venanciaCaderno_0051B', $result['pages'][0]['basename'], 'ZIP import should keep basename');
assertSameValue('Casa', $result['pages'][0]['raw_text'], 'ZIP import should parse text from ALTO XML');
assertSameValue([], $result['errors'], 'ZIP import should not report errors for a valid pair');

@unlink($zipPath);

echo 'zip_import_test: ok' . PHP_EOL;
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/manuscripts/zip_import_test.php`

Expected: FAIL with `Class "App\Manuscripts\ZipImport" not found`.

- [ ] **Step 3: Write the minimal ZIP importer**

```php
<?php

namespace App\Manuscripts;

class ZipImport
{
    public static function fromZipPath($zipPath)
    {
        $zip = new \ZipArchive();

        if ($zip->open((string) $zipPath) !== true) {
            throw new \RuntimeException('Unable to open manuscript ZIP.');
        }

        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index += 1) {
            $name = $zip->getNameIndex($index);
            $basename = pathinfo($name, PATHINFO_FILENAME);
            $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

            if (!isset($entries[$basename])) {
                $entries[$basename] = ['image' => null, 'xml' => null];
            }

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $entries[$basename]['image'] = $name;
            }

            if ($extension === 'xml') {
                $entries[$basename]['xml'] = $name;
            }
        }

        ksort($entries, SORT_NATURAL);
        $pages = [];
        $errors = [];
        $pageNumber = 1;

        foreach ($entries as $basename => $entry) {
            if (!$entry['image'] || !$entry['xml']) {
                $errors[] = 'Missing pair for ' . $basename;
                continue;
            }

            $layout = AltoXml::parse((string) $zip->getFromName($entry['xml']));
            $pages[] = [
                'page_number' => $pageNumber,
                'basename' => $basename,
                'filename' => $entry['image'],
                'raw_text' => isset($layout['text']) ? (string) $layout['text'] : '',
                'layout_data' => [
                    'text' => isset($layout['text']) ? (string) $layout['text'] : '',
                    'lines' => isset($layout['lines']) ? (array) $layout['lines'] : [],
                ],
                'highlight_exact' => !empty($layout['lines'][0]['words'][0]['bbox']['width']),
                'import_status' => 'imported',
                'import_errors' => [],
            ];
            $pageNumber += 1;
        }

        $zip->close();

        return [
            'zip_name' => basename((string) $zipPath),
            'page_count' => count($pages),
            'pages' => $pages,
            'errors' => $errors,
        ];
    }
}
```

- [ ] **Step 4: Add the explicit import action orchestration**

```php
// app/Manuscripts/Admin.php
public static function importZip()
{
    $requestData = static::extractImportRequestData(array_merge(
        is_array($_GET) ? $_GET : [],
        is_array($_POST) ? $_POST : []
    ));
    $postId = $requestData['post_id'];
    $nonce = $requestData['nonce'];

    if (!$postId || !$nonce || !wp_verify_nonce($nonce, 'chao_import_manuscript_zip_' . $postId)) {
        wp_die(__('Solicitacao invalida.', 'chao'));
    }

    $zipId = (int) get_field('manuscript_zip_file', $postId);
    $zipPath = $zipId ? (string) get_attached_file($zipId) : '';

    if ($zipPath === '' || !is_readable($zipPath)) {
        update_post_meta($postId, 'chao_manuscript_import_last_errors', ['ZIP ausente ou invalido.']);
        static::redirectBack($postId, 'missing_zip');
    }

    $result = ZipImport::fromZipPath($zipPath);
    update_post_meta($postId, 'chao_manuscript_import_source', [
        'zip_id' => $zipId,
        'zip_path' => $zipPath,
    ]);
    update_post_meta($postId, 'chao_manuscript_import_last_snapshot', static::buildImportSnapshot($result));
    update_post_meta($postId, 'chao_manuscript_import_last_errors', (array) ($result['errors'] ?? []));

    static::redirectBack($postId, 'success');
}
```

- [ ] **Step 5: Run ZIP/admin tests to verify they pass**

Run:

```bash
php tests/manuscripts/zip_import_test.php
php tests/manuscripts/admin_zip_import_test.php
php tests/manuscripts/admin_process_url_test.php
```

Expected:

- `zip_import_test: ok`
- `admin_zip_import_test: ok`
- `admin_process_url_test: ok`

- [ ] **Step 6: Commit**

```bash
git add app/Manuscripts/ZipImport.php app/Manuscripts/Admin.php tests/manuscripts/zip_import_test.php
git commit -m "feat: import manuscript zip snapshots"
```

### Task 4: Switch Frontend/Search to Imported Page Snapshots

**Files:**
- Modify: `app/Manuscripts/Frontend.php`
- Modify: `app/Manuscripts/Search.php`
- Modify: `tests/manuscripts/search_test.php`

- [ ] **Step 1: Write the failing search test for imported payloads**

```php
$job = [
    'pages' => [
        [
            'page_number' => 1,
            'basename' => 'venanciaCaderno_0051B',
            'filename' => 'venanciaCaderno_0051B.jpg',
            'image_url' => 'http://example.test/uploads/venanciaCaderno_0051B.jpg',
            'raw_text' => 'Casa grande. Outra casa na margem.',
            'layout_data' => [
                'text' => 'Casa grande. Outra casa na margem.',
                'lines' => [
                    [
                        'text' => 'Casa grande.',
                        'bbox' => ['x' => 20, 'y' => 30, 'width' => 180, 'height' => 28],
                        'words' => [
                            ['text' => 'Casa', 'bbox' => ['x' => 20, 'y' => 30, 'width' => 54, 'height' => 28]],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

$result = Search::buildViewModel($job, [], 'casa');

assertSameValue(1, $result['match_count'], 'Imported page payloads should be searchable');
assertSameValue(1, count($result['pages'][0]['highlight_boxes']), 'Imported page payloads should still expose exact word boxes');
```

- [ ] **Step 2: Run search test to verify current behavior or expose gaps**

Run: `php tests/manuscripts/search_test.php`

Expected: if it fails, the failure should point to assumptions still tied to worker-only payloads.

- [ ] **Step 3: Switch the frontend source of truth**

```php
// app/Manuscripts/Frontend.php
public static function withSingleTemplateData($data, $template = null)
{
    $postId = get_the_ID();

    if (!$postId) {
        return $data;
    }

    $snapshot = get_post_meta($postId, 'chao_manuscript_import_last_snapshot', true);
    $review = get_post_meta($postId, 'chao_manuscript_page_review', true);
    $query = isset($_GET['busca']) ? static::wpUnslash($_GET['busca']) : '';

    $data['manuscript_search'] = Search::buildViewModel(
        is_array($snapshot) ? $snapshot : ['pages' => []],
        is_array($review) ? $review : [],
        is_string($query) ? $query : ''
    );

    return $data;
}
```

- [ ] **Step 4: Keep highlight-box output in the search view model**

```php
// app/Manuscripts/Search.php inside buildPages()
$pages[] = [
    'page_number' => $pageNumber,
    'basename' => isset($page['basename']) ? (string) $page['basename'] : '',
    'filename' => isset($page['filename']) ? (string) $page['filename'] : '',
    'image_url' => isset($page['image_url']) ? (string) $page['image_url'] : '',
    'raw_text' => $rawText,
    'layout_data' => $layoutData,
    'effective_text' => $effectiveText,
    'effective_text_html' => static::buildHighlightedHtml($effectiveText, $query),
    'highlight_boxes' => $highlightBoxes,
    'highlight_box_count' => count($highlightBoxes),
];
```

- [ ] **Step 5: Run search test to verify it passes**

Run: `php tests/manuscripts/search_test.php`

Expected: `search_test: ok`

- [ ] **Step 6: Commit**

```bash
git add app/Manuscripts/Frontend.php app/Manuscripts/Search.php tests/manuscripts/search_test.php
git commit -m "refactor: read manuscript search data from zip imports"
```

### Task 5: Restore Viewer Highlight Overlays on Imported Pages

**Files:**
- Modify: `resources/views/partials/content-single-manuscrito.blade.php`
- Modify: `resources/assets/scripts/routes/single-manuscrito.js`
- Modify: `resources/assets/styles/layouts/_manuscripts.scss`
- Modify: `tests/manuscripts/single_manuscrito_viewer_markup_test.php`

- [ ] **Step 1: Write the failing viewer markup contract**

```php
assertTrueValue(strpos($viewerSectionMarkup, 'highlightBoxes') !== false, 'Viewer payload should serialize highlight boxes again');
assertTrueValue(strpos($script, 'data-manuscript-highlight-layer') !== false || strpos($script, 'addOverlay(') !== false, 'Viewer route should render highlight overlays');
assertTrueValue(strpos($script, 'clearOverlays') !== false, 'Viewer route should clear overlays between page changes');
```

- [ ] **Step 2: Run markup test to verify it fails**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL because the current viewer payload no longer includes highlights.

- [ ] **Step 3: Serialize highlight boxes in the Blade payload**

```php
// resources/views/partials/content-single-manuscrito.blade.php
$viewerPages = array_values(array_map(function ($page) {
    return [
        'pageNumber' => (int) ($page['page_number'] ?? 0),
        'imageUrl' => (string) ($page['image_url'] ?? ''),
        'imageAlt' => 'Pagina ' . (int) ($page['page_number'] ?? 0) . ' de ' . get_the_title(),
        'highlightBoxes' => array_values((array) ($page['highlight_boxes'] ?? [])),
    ];
}, array_filter($search['pages'], function ($page) {
    return !empty($page['image_url']);
})));
```

- [ ] **Step 4: Add overlay rendering to the viewer route**

```js
function clearHighlightOverlays(osd, overlayIds) {
  overlayIds.forEach((overlayId) => {
    osd.removeOverlay(overlayId);
  });

  return [];
}

function renderHighlightOverlays(osd, page, overlayIds) {
  const nextOverlayIds = clearHighlightOverlays(osd, overlayIds);

  (page.highlightBoxes || []).forEach((box, index) => {
    const element = document.createElement('span');
    const overlayId = `manuscript-highlight-${page.pageNumber}-${index}`;

    element.className = 'manuscript-single__viewer-highlight';
    element.dataset.manuscriptHighlightLayer = 'true';
    element.id = overlayId;

    osd.addOverlay({
      element,
      location: new OpenSeadragon.Rect(box.x, box.y, box.width, box.height),
    });

    nextOverlayIds.push(overlayId);
  });

  return nextOverlayIds;
}
```

- [ ] **Step 5: Style the overlay**

```scss
.manuscript-single__viewer-highlight {
  display: block;
  border: 2px solid rgba(222, 84, 32, 0.95);
  background: rgba(255, 196, 0, 0.2);
  border-radius: 4px;
  box-sizing: border-box;
  pointer-events: none;
}
```

- [ ] **Step 6: Run the viewer markup test to verify it passes**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: `single_manuscrito_viewer_markup_test: ok`

- [ ] **Step 7: Run the manuscript verification set**

Run:

```bash
php tests/manuscripts/alto_xml_test.php
php tests/manuscripts/zip_import_test.php
php tests/manuscripts/admin_process_url_test.php
php tests/manuscripts/admin_zip_import_test.php
php tests/manuscripts/search_test.php
php tests/manuscripts/single_manuscrito_viewer_markup_test.php
./node_modules/.bin/eslint resources/assets/scripts/routes/single-manuscrito.js
./node_modules/.bin/stylelint resources/assets/styles/layouts/_manuscripts.scss
```

Expected:

- all PHP tests print `: ok`
- `eslint` exits `0`
- `stylelint` exits `0`

- [ ] **Step 8: Commit**

```bash
git add resources/views/partials/content-single-manuscrito.blade.php resources/assets/scripts/routes/single-manuscrito.js resources/assets/styles/layouts/_manuscripts.scss tests/manuscripts/single_manuscrito_viewer_markup_test.php
git commit -m "feat: render manuscript search highlights from zip imports"
```

## Self-Review

- Spec coverage: the plan covers the ACF ZIP source, explicit import action, ZIP validation/pairing, `ALTO XML` parsing, internal snapshot persistence, frontend search integration, and viewer highlights.
- Placeholder scan: no `TODO`/`TBD` placeholders remain; every task lists concrete files, commands, and code.
- Type consistency: the plan consistently uses `manuscript_zip_file`, `buildImportSnapshot()`, `buildImportedPageRows()`, `chao_manuscript_import_last_snapshot`, and `highlightBoxes` across admin, backend, and frontend tasks.
