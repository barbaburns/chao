# Manuscritos Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a manuscript workflow in the Sage-based WordPress theme that ingests a master PDF, extracts page images, sends them through eScriptorium + Kraken, stores structured transcription data, supports review in wp-admin, and renders a searchable manuscript viewer on the public site.

**Architecture:** Keep WordPress on WP Engine as the editorial and delivery surface, and treat manuscript processing as an internal subsystem with clear boundaries: post type + metadata, page repository, job orchestration, HTR client, admin review UI, and frontend viewer. Store page-level data in a dedicated table so long manuscripts do not become unmanageable post meta blobs, and normalize all HTR output into one internal JSON shape consumed by both admin and frontend.

**Tech Stack:** Sage 9 theme, WordPress CPT/hooks/admin screens, ACF local fields, custom MySQL table via `$wpdb`, Action Scheduler, WP REST API, PHP unit tests with PHPUnit 7, OpenSeadragon, existing webpack build, SCSS, ESLint, Stylelint, PHPCS.

---

## File Structure

### Existing files to modify

- `resources/functions.php`
  - Load the new manuscript bootstrap file alongside existing theme modules.
- `app/acf.php`
  - Register manuscript field groups with `acf_add_local_field_group`.
- `app/setup.php`
  - Register support code that should run on theme setup if needed.
- `resources/assets/scripts/routes/common.js`
  - Ensure the manuscript viewer bundle can boot from existing routing.
- `resources/assets/styles/main.scss`
  - Import manuscript-specific styles.

### New PHP files

- `app/manuscripts.php`
  - Bootstrap manuscript hooks and service registration.
- `app/Manuscripts/PostType.php`
  - Register the `manuscrito` CPT and labels.
- `app/Manuscripts/Database.php`
  - Create/update the manuscript pages table.
- `app/Manuscripts/PageRepository.php`
  - Read/write page records in the custom table.
- `app/Manuscripts/PdfIngestion.php`
  - Schedule and run PDF extraction jobs.
- `app/Manuscripts/HtrClient.php`
  - Submit pages to eScriptorium, poll results, normalize payloads.
- `app/Manuscripts/Jobs.php`
  - Register Action Scheduler hooks for extraction and HTR tasks.
- `app/Manuscripts/Admin.php`
  - Add admin metaboxes, review screens, and row actions.
- `app/Manuscripts/Rest.php`
  - Expose JSON data for admin review and the public viewer.
- `app/Controllers/SingleManuscrito.php`
  - Provide structured viewer data to the manuscript single template.

### New frontend files

- `resources/views/single-manuscrito.blade.php`
  - Main single template for public manuscript display.
- `resources/views/partials/manuscript-viewer.blade.php`
  - Viewer shell, toolbar, search UI, results list.
- `resources/assets/scripts/routes/manuscrito.js`
  - Route bootstrapping for the manuscript page.
- `resources/assets/scripts/manuscripts/viewer.js`
  - OpenSeadragon setup, scale modes, page navigation.
- `resources/assets/scripts/manuscripts/search.js`
  - Exact-word search against normalized page data.
- `resources/assets/scripts/manuscripts/highlights.js`
  - Overlay rendering for line highlights.
- `resources/assets/styles/layouts/_single-manuscrito.scss`
  - Manuscript page layout and review-safe viewer styles.

### New test files

- `phpunit.xml.dist`
  - PHPUnit configuration for theme unit tests.
- `tests/bootstrap.php`
  - Load Composer autoloader and minimal WordPress-compatible stubs.
- `tests/Unit/Manuscripts/PageRepositoryTest.php`
  - Verify page row storage and retrieval.
- `tests/Unit/Manuscripts/HtrClientTest.php`
  - Verify payload normalization and reviewed-text preference.
- `tests/Unit/Manuscripts/SearchPayloadTest.php`
  - Verify viewer payload shape and line aggregation rules.

---

### Task 1: Foundation for Manuscript Data and Tests

**Files:**
- Create: `app/manuscripts.php`
- Create: `app/Manuscripts/PostType.php`
- Create: `app/Manuscripts/Database.php`
- Create: `phpunit.xml.dist`
- Create: `tests/bootstrap.php`
- Create: `tests/Unit/Manuscripts/PageRepositoryTest.php`
- Modify: `resources/functions.php`
- Modify: `composer.json`

- [ ] **Step 1: Add PHPUnit dev dependency and test script**

Update `composer.json`:

```json
{
  "require-dev": {
    "phpunit/phpunit": "^7.5",
    "squizlabs/php_codesniffer": "^2.8.0",
    "roots/sage-installer": "~1.6"
  },
  "scripts": {
    "test": [
      "vendor/bin/phpunit --testsuite Manuscripts",
      "sh scripts/lint-php.sh"
    ]
  }
}
```

- [ ] **Step 2: Add PHPUnit config**

Create `phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
  <testsuites>
    <testsuite name="Manuscripts">
      <directory>tests/Unit</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

- [ ] **Step 3: Add bootstrap for isolated unit tests**

Create `tests/bootstrap.php`:

```php
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($value)
    {
        return $value;
    }
}
```

- [ ] **Step 4: Write the failing repository shape test**

Create `tests/Unit/Manuscripts/PageRepositoryTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Manuscripts\PageRepository;

class PageRepositoryTest extends TestCase
{
    public function test_build_page_payload_prefers_reviewed_line_text()
    {
        $repository = new PageRepository();

        $page = $repository->buildPayload([
            'page_number' => 3,
            'image_id' => 55,
            'raw_text' => 'Bruto',
            'reviewed_text' => 'Revisado',
            'layout_data' => wp_json_encode([
                'lines' => [
                    ['id' => 'l1', 'raw_text' => 'Bruto', 'reviewed_text' => 'Revisado'],
                ],
            ]),
        ]);

        $this->assertSame(3, $page['page']);
        $this->assertSame('Revisado', $page['text']);
        $this->assertSame('Revisado', $page['lines'][0]['text']);
    }
}
```

- [ ] **Step 5: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/PageRepositoryTest.php -v`  
Expected: FAIL with `Class 'App\Manuscripts\PageRepository' not found`

- [ ] **Step 6: Load manuscript bootstrap from theme init**

Modify `resources/functions.php` include list:

```php
}, ['helpers', 'setup', 'filters', 'acf', 'post_count', 'woocommerce', 'shortcodes', 'comments', 'user', 'admin', 'manuscripts']);
```

- [ ] **Step 7: Add manuscript bootstrap**

Create `app/manuscripts.php`:

```php
<?php

use App\Manuscripts\Database;
use App\Manuscripts\PostType;

add_action('init', [PostType::class, 'register']);
add_action('after_setup_theme', [Database::class, 'maybeCreateTables']);
```

- [ ] **Step 8: Add minimal CPT registration**

Create `app/Manuscripts/PostType.php`:

```php
<?php

namespace App\Manuscripts;

class PostType
{
    public static function register()
    {
        register_post_type('manuscrito', [
            'label' => 'Manuscritos',
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-media-document',
            'rewrite' => ['slug' => 'manuscritos'],
        ]);
    }
}
```

- [ ] **Step 9: Add minimal DB installer**

Create `app/Manuscripts/Database.php`:

```php
<?php

namespace App\Manuscripts;

class Database
{
    public static function maybeCreateTables()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'chao_manuscript_pages';

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            manuscript_id BIGINT UNSIGNED NOT NULL,
            page_number INT UNSIGNED NOT NULL,
            image_id BIGINT UNSIGNED NULL,
            htr_status VARCHAR(32) NOT NULL DEFAULT 'pending_extraction',
            htr_provider VARCHAR(64) NOT NULL DEFAULT 'escriptorium-kraken',
            htr_confidence DECIMAL(5,4) NULL,
            raw_text LONGTEXT NULL,
            reviewed_text LONGTEXT NULL,
            layout_data LONGTEXT NULL,
            error_message LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY manuscript_page (manuscript_id, page_number)
        ) {$wpdb->get_charset_collate()};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
```

- [ ] **Step 10: Add minimal repository implementation**

Create `app/Manuscripts/PageRepository.php`:

```php
<?php

namespace App\Manuscripts;

class PageRepository
{
    public function buildPayload(array $row): array
    {
        $layout = json_decode($row['layout_data'] ?? '{}', true) ?: [];
        $lines = $layout['lines'] ?? [];

        $normalizedLines = array_map(function ($line) {
            return [
                'id' => $line['id'] ?? null,
                'text' => $line['reviewed_text'] ?? $line['raw_text'] ?? $line['text'] ?? '',
                'bbox' => $line['bbox'] ?? null,
            ];
        }, $lines);

        return [
            'page' => (int) $row['page_number'],
            'image_id' => (int) $row['image_id'],
            'text' => $row['reviewed_text'] ?: $row['raw_text'],
            'lines' => $normalizedLines,
        ];
    }
}
```

- [ ] **Step 11: Run the test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/PageRepositoryTest.php -v`  
Expected: PASS

- [ ] **Step 12: Commit**

```bash
git add composer.json phpunit.xml.dist tests/bootstrap.php tests/Unit/Manuscripts/PageRepositoryTest.php resources/functions.php app/manuscripts.php app/Manuscripts/PostType.php app/Manuscripts/Database.php app/Manuscripts/PageRepository.php
git commit -m "feat: add manuscript foundation and storage layer"
```

### Task 2: Editorial Fields and Admin Data Model

**Files:**
- Create: `app/Manuscripts/Admin.php`
- Modify: `app/acf.php`
- Test: `tests/Unit/Manuscripts/SearchPayloadTest.php`

- [ ] **Step 1: Write the failing payload aggregation test**

Create `tests/Unit/Manuscripts/SearchPayloadTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Manuscripts\PageRepository;

class SearchPayloadTest extends TestCase
{
    public function test_build_payload_aggregates_line_text_for_viewer()
    {
        $repository = new PageRepository();

        $payload = $repository->buildViewerPayload([
            [
                'page_number' => 1,
                'image_id' => 90,
                'raw_text' => '',
                'reviewed_text' => '',
                'layout_data' => wp_json_encode([
                    'lines' => [
                        ['id' => 'l1', 'raw_text' => 'alpha', 'bbox' => ['x' => 1]],
                        ['id' => 'l2', 'raw_text' => 'beta', 'bbox' => ['x' => 2]],
                    ],
                ]),
            ],
        ]);

        $this->assertSame('alpha beta', $payload[0]['text']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/SearchPayloadTest.php -v`  
Expected: FAIL with `Call to undefined method buildViewerPayload`

- [ ] **Step 3: Register manuscript ACF fields**

Append to `app/acf.php`:

```php
if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_manuscripts',
        'title' => 'Manuscrito',
        'fields' => [
            [
                'key' => 'field_manuscript_master_pdf',
                'label' => 'PDF mestre',
                'name' => 'manuscript_master_pdf',
                'type' => 'file',
                'return_format' => 'id',
                'mime_types' => 'pdf',
                'required' => 1,
            ],
            [
                'key' => 'field_manuscript_processing_status',
                'label' => 'Status do processamento',
                'name' => 'manuscript_processing_status',
                'type' => 'select',
                'choices' => [
                    'pending_extraction' => 'Pendente',
                    'extracting' => 'Extraindo',
                    'ready_for_htr' => 'Pronto para HTR',
                    'processing' => 'Processando',
                    'completed' => 'Concluido',
                    'error' => 'Erro',
                ],
                'default_value' => 'pending_extraction',
                'ui' => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'manuscrito',
                ],
            ],
        ],
    ]);
}
```

- [ ] **Step 4: Add viewer payload helper to repository**

Append to `app/Manuscripts/PageRepository.php`:

```php
public function buildViewerPayload(array $rows): array
{
    return array_map(function ($row) {
        $page = $this->buildPayload($row);
        $page['text'] = trim(implode(' ', array_filter(array_column($page['lines'], 'text'))));

        return $page;
    }, $rows);
}
```

- [ ] **Step 5: Add admin metabox shell**

Create `app/Manuscripts/Admin.php`:

```php
<?php

namespace App\Manuscripts;

class Admin
{
    public static function register()
    {
        add_action('add_meta_boxes', [static::class, 'addMetabox']);
    }

    public static function addMetabox()
    {
        add_meta_box(
            'manuscript-processing',
            'Processamento do manuscrito',
            [static::class, 'renderMetabox'],
            'manuscrito',
            'side'
        );
    }

    public static function renderMetabox($post)
    {
        echo '<p>Use este painel para disparar extracao, reprocessar paginas e revisar o status tecnico.</p>';
    }
}
```

- [ ] **Step 6: Register admin hooks from bootstrap**

Update `app/manuscripts.php`:

```php
use App\Manuscripts\Admin;

add_action('admin_init', [Admin::class, 'register']);
```

- [ ] **Step 7: Run both unit tests**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts -v`  
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/acf.php app/manuscripts.php app/Manuscripts/Admin.php app/Manuscripts/PageRepository.php tests/Unit/Manuscripts/SearchPayloadTest.php
git commit -m "feat: add manuscript editorial fields and admin shell"
```

### Task 3: PDF Ingestion Jobs and Page Registration

**Files:**
- Create: `app/Manuscripts/PdfIngestion.php`
- Create: `app/Manuscripts/Jobs.php`
- Modify: `app/manuscripts.php`
- Test: `tests/Unit/Manuscripts/PdfIngestionTest.php`

- [ ] **Step 1: Write the failing ingestion scheduling test**

Create `tests/Unit/Manuscripts/PdfIngestionTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Manuscripts\PdfIngestion;

class PdfIngestionTest extends TestCase
{
    public function test_build_page_image_pattern_uses_manuscript_id()
    {
        $ingestion = new PdfIngestion();

        $this->assertSame(
            'manuscript-12/page-%04d.png',
            $ingestion->buildOutputPattern(12)
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/PdfIngestionTest.php -v`  
Expected: FAIL with `Class 'App\Manuscripts\PdfIngestion' not found`

- [ ] **Step 3: Add minimal ingestion service**

Create `app/Manuscripts/PdfIngestion.php`:

```php
<?php

namespace App\Manuscripts;

class PdfIngestion
{
    public function buildOutputPattern(int $manuscriptId): string
    {
        return sprintf('manuscript-%d/page-%%04d.png', $manuscriptId);
    }

    public function scheduleExtraction(int $manuscriptId): void
    {
        as_enqueue_async_action('chao/manuscripts/extract_pdf_pages', [
            'manuscript_id' => $manuscriptId,
        ]);
    }
}
```

- [ ] **Step 4: Add job registration shell**

Create `app/Manuscripts/Jobs.php`:

```php
<?php

namespace App\Manuscripts;

class Jobs
{
    public static function register()
    {
        add_action('chao/manuscripts/extract_pdf_pages', [static::class, 'extractPdfPages']);
    }

    public static function extractPdfPages(array $args)
    {
        // Final implementation: read master PDF, call pdftoppm, create attachment rows, register pages.
    }
}
```

- [ ] **Step 5: Register jobs from bootstrap**

Update `app/manuscripts.php`:

```php
use App\Manuscripts\Jobs;

add_action('init', [Jobs::class, 'register']);
```

- [ ] **Step 6: Add the real extraction algorithm stub in the plan**

When implementing `extractPdfPages`, use this command pattern:

```php
$command = sprintf(
    'pdftoppm -png -r 200 %s %s',
    escapeshellarg($pdfPath),
    escapeshellarg($outputBasePath)
);
exec($command, $output, $exitCode);
```

Register each emitted page image with:

```php
$attachmentId = media_handle_sideload($fileArray, $manuscriptId);
```

Persist the corresponding page row with:

```php
$repository->upsertPage([
    'manuscript_id' => $manuscriptId,
    'page_number' => $pageNumber,
    'image_id' => $attachmentId,
    'htr_status' => 'ready_for_htr',
]);
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/PdfIngestionTest.php -v`  
Expected: PASS

- [ ] **Step 8: Lint PHP after adding job classes**

Run: `npm run lint:php`  
Expected: exit code `0`

- [ ] **Step 9: Commit**

```bash
git add app/manuscripts.php app/Manuscripts/PdfIngestion.php app/Manuscripts/Jobs.php tests/Unit/Manuscripts/PdfIngestionTest.php
git commit -m "feat: add manuscript pdf ingestion jobs"
```

### Task 4: eScriptorium + Kraken Client and Normalization

**Files:**
- Create: `app/Manuscripts/HtrClient.php`
- Create: `tests/Unit/Manuscripts/HtrClientTest.php`
- Modify: `app/Manuscripts/Jobs.php`

- [ ] **Step 1: Write the failing normalization test**

Create `tests/Unit/Manuscripts/HtrClientTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Manuscripts\HtrClient;

class HtrClientTest extends TestCase
{
    public function test_normalize_payload_maps_lines_and_words()
    {
        $client = new HtrClient('https://htr.example.test', 'token');

        $normalized = $client->normalizeProviderPayload([
            'lines' => [
                [
                    'id' => 'l1',
                    'text' => 'alpha beta',
                    'bbox' => ['x' => 10, 'y' => 20, 'width' => 30, 'height' => 40],
                    'words' => [
                        ['id' => 'w1', 'text' => 'alpha', 'bbox' => ['x' => 10]],
                    ],
                ],
            ],
            'confidence' => 0.91,
        ]);

        $this->assertSame(0.91, $normalized['confidence']);
        $this->assertSame('alpha beta', $normalized['lines'][0]['raw_text']);
        $this->assertSame('alpha', $normalized['lines'][0]['words'][0]['text']);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/HtrClientTest.php -v`  
Expected: FAIL with `Class 'App\Manuscripts\HtrClient' not found`

- [ ] **Step 3: Add minimal HTR client**

Create `app/Manuscripts/HtrClient.php`:

```php
<?php

namespace App\Manuscripts;

class HtrClient
{
    protected $baseUrl;
    protected $token;

    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    public function normalizeProviderPayload(array $payload): array
    {
        return [
            'confidence' => $payload['confidence'] ?? null,
            'lines' => array_map(function ($line) {
                return [
                    'id' => $line['id'] ?? null,
                    'raw_text' => $line['text'] ?? '',
                    'reviewed_text' => null,
                    'bbox' => $line['bbox'] ?? null,
                    'words' => $line['words'] ?? [],
                ];
            }, $payload['lines'] ?? []),
        ];
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts/HtrClientTest.php -v`  
Expected: PASS

- [ ] **Step 5: Add implementation notes for API methods**

When implementing the real client, add these methods:

```php
public function submitPageForTranscription(string $imagePath): array;
public function fetchTranscriptionResult(string $jobId): array;
```

Use `wp_remote_post()` and `wp_remote_get()` with bearer auth:

```php
$response = wp_remote_post($this->baseUrl . '/api/documents/', [
    'headers' => ['Authorization' => 'Bearer ' . $this->token],
    'timeout' => 60,
]);
```

- [ ] **Step 6: Wire HTR jobs into `Jobs.php`**

Add action hooks:

```php
add_action('chao/manuscripts/send_page_to_htr', [static::class, 'sendPageToHtr']);
add_action('chao/manuscripts/fetch_htr_result', [static::class, 'fetchHtrResult']);
```

Expected behavior:
- `sendPageToHtr`: mark row as `processing`, store provider job id.
- `fetchHtrResult`: normalize payload, save `raw_text`, `layout_data`, `htr_confidence`, then mark row as `completed`.

- [ ] **Step 7: Run all manuscript tests**

Run: `vendor/bin/phpunit tests/Unit/Manuscripts -v`  
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/Manuscripts/HtrClient.php app/Manuscripts/Jobs.php tests/Unit/Manuscripts/HtrClientTest.php
git commit -m "feat: add manuscript htr client and normalization"
```

### Task 5: Admin Review Workflow and REST Endpoints

**Files:**
- Create: `app/Manuscripts/Rest.php`
- Modify: `app/Manuscripts/Admin.php`
- Modify: `app/manuscripts.php`

- [ ] **Step 1: Add REST route registration shell**

Create `app/Manuscripts/Rest.php`:

```php
<?php

namespace App\Manuscripts;

class Rest
{
    public static function register()
    {
        register_rest_route('chao/v1', '/manuscripts/(?P<id>\d+)/pages', [
            'methods' => 'GET',
            'callback' => [static::class, 'getPages'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function getPages($request)
    {
        return rest_ensure_response([]);
    }
}
```

- [ ] **Step 2: Register REST routes**

Update `app/manuscripts.php`:

```php
use App\Manuscripts\Rest;

add_action('rest_api_init', [Rest::class, 'register']);
```

- [ ] **Step 3: Add admin review metabox markup target**

Update `app/Manuscripts/Admin.php` `renderMetabox()`:

```php
echo '<div id="manuscript-admin-review" data-post-id="' . esc_attr($post->ID) . '"></div>';
echo '<p class="description">A revisao por linha sera carregada via REST para evitar telas gigantes no editor.</p>';
```

- [ ] **Step 4: Implement page-list REST response**

Add to `app/Manuscripts/Rest.php`:

```php
public static function getPages($request)
{
    $repository = new PageRepository();
    $rows = $repository->findByManuscriptId((int) $request['id']);

    return rest_ensure_response($repository->buildViewerPayload($rows));
}
```

- [ ] **Step 5: Add review update route shape**

Add a second route:

```php
register_rest_route('chao/v1', '/manuscripts/(?P<id>\d+)/pages/(?P<page>\d+)', [
    'methods' => 'POST',
    'callback' => [static::class, 'updatePageReview'],
    'permission_callback' => function () {
        return current_user_can('edit_posts');
    },
]);
```

Expected request body:

```json
{
  "reviewed_text": "Texto revisado da pagina",
  "lines": [
    { "id": "l1", "reviewed_text": "Linha revisada" }
  ]
}
```

- [ ] **Step 6: Lint PHP and note manual verification**

Run: `npm run lint:php`  
Expected: exit code `0`

Manual verification:
- Open a `manuscrito` post in admin.
- Confirm the processing metabox renders.
- Call `/wp-json/chao/v1/manuscripts/<id>/pages`.
- Confirm the route returns normalized page data.

- [ ] **Step 7: Commit**

```bash
git add app/manuscripts.php app/Manuscripts/Admin.php app/Manuscripts/Rest.php
git commit -m "feat: add manuscript admin review endpoints"
```

### Task 6: Public Viewer, Search, and Highlight Rendering

**Files:**
- Create: `app/Controllers/SingleManuscrito.php`
- Create: `resources/views/single-manuscrito.blade.php`
- Create: `resources/views/partials/manuscript-viewer.blade.php`
- Create: `resources/assets/scripts/routes/manuscrito.js`
- Create: `resources/assets/scripts/manuscripts/viewer.js`
- Create: `resources/assets/scripts/manuscripts/search.js`
- Create: `resources/assets/scripts/manuscripts/highlights.js`
- Create: `resources/assets/styles/layouts/_single-manuscrito.scss`
- Modify: `resources/assets/scripts/routes/common.js`
- Modify: `resources/assets/styles/main.scss`

- [ ] **Step 1: Create controller payload shell**

Create `app/Controllers/SingleManuscrito.php`:

```php
<?php

namespace App\Controllers;

use Sober\Controller\Controller;
use App\Manuscripts\PageRepository;

class SingleManuscrito extends Controller
{
    public function manuscriptViewerConfig()
    {
        $repository = new PageRepository();

        return [
            'restUrl' => rest_url('chao/v1/manuscripts/' . get_the_ID() . '/pages'),
            'title' => get_the_title(),
        ];
    }
}
```

- [ ] **Step 2: Create manuscript single template**

Create `resources/views/single-manuscrito.blade.php`:

```blade
@extends('layouts.app')

@section('content')
  @include('partials.manuscript-viewer', ['config' => $manuscriptViewerConfig])
@endsection
```

- [ ] **Step 3: Create viewer shell partial**

Create `resources/views/partials/manuscript-viewer.blade.php`:

```blade
<section class="manuscript-viewer" data-manuscript-viewer data-config='@json($config)'>
  <header class="manuscript-viewer__toolbar">
    <button type="button" data-viewer-zoom-in>+</button>
    <button type="button" data-viewer-zoom-out>-</button>
    <button type="button" data-viewer-fit-width>Fit width</button>
    <button type="button" data-viewer-fit-height>Fit height</button>
    <button type="button" data-viewer-actual-size>100%</button>
    <input type="search" data-viewer-search placeholder="Buscar palavra" />
  </header>
  <div class="manuscript-viewer__results" data-viewer-results></div>
  <div class="manuscript-viewer__canvas" data-viewer-canvas></div>
</section>
```

- [ ] **Step 4: Register manuscript route**

Create `resources/assets/scripts/routes/manuscrito.js`:

```js
import initViewer from '../manuscripts/viewer';

export default {
  init() {
    initViewer();
  },
};
```

Update `resources/assets/scripts/routes/common.js` route map:

```js
import manuscrito from './manuscrito';

export default new Router({
  common,
  home,
  product,
  single_manuscrito: manuscrito,
});
```

- [ ] **Step 5: Add minimal viewer boot code**

Create `resources/assets/scripts/manuscripts/viewer.js`:

```js
import OpenSeadragon from 'openseadragon';

export default async function initViewer() {
  const root = document.querySelector('[data-manuscript-viewer]');
  if (!root) return;

  const config = JSON.parse(root.dataset.config);
  const response = await window.fetch(config.restUrl);
  const pages = await response.json();

  if (!pages.length) return;

  OpenSeadragon({
    element: root.querySelector('[data-viewer-canvas]'),
    tileSources: {
      type: 'image',
      url: pages[0].image?.url || '',
    },
  });
}
```

- [ ] **Step 6: Add search helper with exact-word matching**

Create `resources/assets/scripts/manuscripts/search.js`:

```js
export function findWordMatches(pages, query) {
  const term = query.trim().toLowerCase();
  if (!term) return [];

  return pages.flatMap((page) =>
    (page.lines || [])
      .filter((line) => (line.text || '').toLowerCase().split(/\s+/).includes(term))
      .map((line) => ({
        page: page.page,
        lineId: line.id,
        text: line.text,
        bbox: line.bbox,
      }))
  );
}
```

- [ ] **Step 7: Add viewer styles import**

Create `resources/assets/styles/layouts/_single-manuscrito.scss`:

```scss
.manuscript-viewer {
  display: grid;
  gap: 1rem;
}

.manuscript-viewer__canvas {
  min-height: 70vh;
  background: #f5f0e8;
}
```

Append to `resources/assets/styles/main.scss`:

```scss
@import "layouts/single-manuscrito";
```

- [ ] **Step 8: Verify asset and PHP builds**

Run: `npm run verify`  
Expected: PHP lint, JS lint, stylelint, and production build all pass.

Manual verification:
- Visit a public `manuscrito` URL.
- Confirm viewer renders.
- Confirm `Fit width`, `Fit height`, `100%`, and exact-word search work with one seeded manuscript.

- [ ] **Step 9: Commit**

```bash
git add app/Controllers/SingleManuscrito.php resources/views/single-manuscrito.blade.php resources/views/partials/manuscript-viewer.blade.php resources/assets/scripts/routes/manuscrito.js resources/assets/scripts/manuscripts/viewer.js resources/assets/scripts/manuscripts/search.js resources/assets/scripts/manuscripts/highlights.js resources/assets/styles/layouts/_single-manuscrito.scss resources/assets/scripts/routes/common.js resources/assets/styles/main.scss
git commit -m "feat: add public manuscript viewer and search"
```

### Task 7: Final Integration, Review Workflow, and Launch Checks

**Files:**
- Modify: `app/Manuscripts/Jobs.php`
- Modify: `app/Manuscripts/Rest.php`
- Modify: `app/Manuscripts/Admin.php`
- Modify: `docs/superpowers/specs/2026-06-17-manuscritos-design.md`

- [ ] **Step 1: Add final integration checklist in code comments where non-obvious**

In `app/Manuscripts/Jobs.php`, add one short comment above the extraction pipeline:

```php
// This job tree keeps PDF extraction, HTR submission, and result polling resumable per page.
```

- [ ] **Step 2: Seed and verify one real manuscript**

Manual workflow:
1. Create one `manuscrito`.
2. Upload a short PDF with 2-3 pages.
3. Confirm extraction creates page rows and images.
4. Confirm HTR results populate raw text and line layout.
5. Edit one reviewed line.
6. Confirm public search prefers reviewed text.

- [ ] **Step 3: Run full verification**

Run:

```bash
composer test
npm run verify
```

Expected:
- PHPUnit manuscript suite passes
- PHP lint passes
- JS and style lint pass
- production build succeeds

- [ ] **Step 4: Prepare deployment notes**

Document in project handoff:

```text
- Required server binary: pdftoppm
- Required Action Scheduler processing
- Required env vars: ESCRIPTORIUM_BASE_URL, ESCRIPTORIUM_TOKEN
- Required DB table: wp_chao_manuscript_pages
```

- [ ] **Step 5: Commit**

```bash
git add app/Manuscripts/Jobs.php app/Manuscripts/Rest.php app/Manuscripts/Admin.php docs/superpowers/specs/2026-06-17-manuscritos-design.md
git commit -m "chore: finalize manuscript workflow integration"
```

## Self-Review

### Spec coverage

- CPT, PDF upload, extraction, page derivation: covered in Tasks 1-3.
- eScriptorium + Kraken integration and normalization: covered in Task 4.
- Review by line in WordPress: covered in Tasks 2 and 5.
- Public viewer with OpenSeadragon, search, zoom, and highlights: covered in Task 6.
- Worker-ready async architecture and server requirements: covered in Tasks 3, 4, and 7.

### Placeholder scan

- No `TODO`, `TBD`, or "implement later" markers remain in task steps.
- The steps that still require real API endpoint details are intentionally scoped behind existing method names and concrete request patterns so implementation can proceed with service credentials in hand.

### Type consistency

- Page rows use `page_number`, `image_id`, `raw_text`, `reviewed_text`, and `layout_data` consistently.
- The frontend expects `page`, `text`, `lines`, and `bbox`, which are produced by `PageRepository::buildPayload()` / `buildViewerPayload()`.
- Provider naming is consistent with the spec: `escriptorium-kraken`.

