# Manuscript Viewer OpenSeadragon Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrar o viewer da `single-manuscrito` para `OpenSeadragon`, mantendo navegacao entre paginas e abrindo a pagina correta a partir dos resultados de busca, sem highlights nesta primeira fase.

**Architecture:** O backend continua fornecendo `pages` e `initial_page_number` via `Search::buildViewModel()`. O frontend troca o viewer custom por uma instancia unica de `OpenSeadragon`, atualizando a imagem aberta conforme a pagina ativa e sincronizando a navegacao do viewer com o indice e os links de resultado da busca. O markup e os estilos da `single-manuscrito` sao simplificados para remover overlay e controles custom de escala.

**Tech Stack:** Sage 9, webpack 3, Blade, SCSS, ES modules, OpenSeadragon, PHP tests existentes, verificacao via build/lint.

---

## File Structure

- Modify: `package.json`
- Modify: `yarn.lock`
- Modify: `resources/views/partials/content-single-manuscrito.blade.php`
- Modify: `resources/assets/scripts/routes/single-manuscrito.js`
- Modify: `resources/assets/styles/layouts/_manuscripts.scss`
- Create: `tests/manuscripts/single_manuscrito_viewer_markup_test.php`

`content-single-manuscrito.blade.php` continua sendo a fonte do markup e dos dados serializados do viewer.

`single-manuscrito.js` fica responsavel apenas por inicializar o `OpenSeadragon`, trocar a pagina aberta e sincronizar os controles.

`_manuscripts.scss` define o layout e remove os estilos mortos do viewer antigo.

`tests/manuscripts/single_manuscrito_viewer_markup_test.php` cobre o contrato estrutural da pagina e da rota JS para evitar regressao no viewer.

### Task 1: Add the dependency and write the failing source-based test

**Files:**
- Modify: `package.json`
- Modify: `yarn.lock`
- Create: `tests/manuscripts/single_manuscrito_viewer_markup_test.php`

- [ ] **Step 1: Write the failing viewer migration test**

Create `tests/manuscripts/single_manuscrito_viewer_markup_test.php` with assertions for the new contract:

```php
<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');
$package = file_get_contents(dirname(__DIR__, 2) . '/package.json');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($package, '"openseadragon"') !== false, 'Theme package should declare openseadragon as a dependency');
assertTrueValue(strpos($template, 'data-manuscript-osd') !== false, 'Manuscript template should expose a dedicated OpenSeadragon container');
assertTrueValue(strpos($template, 'data-manuscript-pages') !== false, 'Manuscript template should keep serialized page data for the viewer');
assertTrueValue(strpos($template, 'data-manuscript-prev') !== false, 'Manuscript template should keep previous-page navigation');
assertTrueValue(strpos($template, 'data-manuscript-next') !== false, 'Manuscript template should keep next-page navigation');
assertTrueValue(strpos($template, 'data-manuscript-scale-mode') === false, 'Manuscript template should remove legacy scale-mode controls');
assertTrueValue(strpos($template, 'data-manuscript-highlight-label') === false, 'Manuscript template should remove the legacy highlight label');
assertTrueValue(strpos($script, "from 'openseadragon'") !== false || strpos($script, 'from "openseadragon"') !== false, 'Single manuscript route should import OpenSeadragon');
assertTrueValue(strpos($script, 'OpenSeadragon({') !== false, 'Single manuscript route should initialize OpenSeadragon');
assertTrueValue(strpos($script, 'data-manuscript-osd') !== false, 'Single manuscript route should target the OpenSeadragon container');
assertTrueValue(strpos($script, 'data-manuscript-scale-mode') === false, 'Single manuscript route should remove scale-mode logic');
assertTrueValue(strpos($script, 'manuscript-single__viewer-highlight') === false, 'Single manuscript route should remove highlight overlay rendering');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-osd') !== false, 'Manuscript styles should define an OpenSeadragon viewer container');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-highlight') === false, 'Manuscript styles should remove highlight overlay styles');

echo 'single_manuscrito_viewer_markup_test: ok' . PHP_EOL;
```

- [ ] **Step 2: Run the new test to verify it fails**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL because `openseadragon` is absent, the template still exposes legacy controls, and the JS still contains the old custom viewer logic.

- [ ] **Step 3: Add the dependency declaration**

Update `package.json` dependencies block to include `openseadragon`:

```json
"dependencies": {
  "@fortawesome/fontawesome-svg-core": "^1.2.29",
  "@fortawesome/free-brands-svg-icons": "^5.13.1",
  "bootstrap": "v4.3.1",
  "jquery": "^3.3.1",
  "openseadragon": "^5.0.1",
  "popper.js": "^1.14.7",
  "swiper": "6.0.4"
}
```

Then refresh `yarn.lock` with `yarn add openseadragon@^5.0.1`.

- [ ] **Step 4: Re-run the focused test to confirm it still fails for markup/JS**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL, now only because the template, route, and styles still describe the old viewer.

- [ ] **Step 5: Commit**

```bash
git add package.json yarn.lock tests/manuscripts/single_manuscrito_viewer_markup_test.php
git commit -m "test: cover manuscript viewer openseadragon migration"
```

### Task 2: Replace the viewer markup with an OpenSeadragon container

**Files:**
- Modify: `resources/views/partials/content-single-manuscrito.blade.php`
- Test: `tests/manuscripts/single_manuscrito_viewer_markup_test.php`

- [ ] **Step 1: Update the failing test with exact markup expectations**

Add assertions that the template keeps page navigation but swaps the viewer body:

```php
assertTrueValue(strpos($template, 'class="manuscript-single__viewer-osd"') !== false, 'Manuscript template should render the OpenSeadragon viewer element');
assertTrueValue(strpos($template, 'data-manuscript-osd') !== false, 'Manuscript template should expose the OpenSeadragon mount point');
assertTrueValue(strpos($template, 'data-manuscript-frame') === false, 'Manuscript template should remove the legacy frame wrapper');
assertTrueValue(strpos($template, 'data-manuscript-image') === false, 'Manuscript template should remove the legacy image node');
assertTrueValue(strpos($template, 'data-manuscript-overlay') === false, 'Manuscript template should remove the legacy overlay node');
```

- [ ] **Step 2: Run the focused test and verify it fails on the old template**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL with missing `data-manuscript-osd` / legacy nodes still present.

- [ ] **Step 3: Simplify the Blade viewer section**

Update the viewer markup in `resources/views/partials/content-single-manuscrito.blade.php` to this shape:

```blade
<section
  class="manuscript-single__viewer"
  id="manuscript-viewer"
  aria-labelledby="manuscript-viewer-title"
  data-manuscript-viewer
  data-initial-page-number="{{ $search['initial_page_number'] }}"
>
  <script type="application/json" data-manuscript-pages>{!! wp_json_encode($viewerPages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

  <div class="manuscript-single__viewer-header">
    <h3 id="manuscript-viewer-title">Visualizador</h3>
    <p>Use o mouse, trackpad ou gesto de toque para aproximar e navegar pela imagem.</p>
  </div>

  <div class="manuscript-single__viewer-toolbar">
    <div class="manuscript-single__viewer-nav">
      <button type="button" class="btn" data-manuscript-prev>Anterior</button>
      <span data-manuscript-page-label>Pagina</span>
      <button type="button" class="btn" data-manuscript-next>Proxima</button>
    </div>
  </div>

  <div class="manuscript-single__viewer-osd" data-manuscript-osd></div>

  <ol class="manuscript-single__viewer-index">
    @foreach($viewerPages as $page)
      <li>
        <button
          type="button"
          class="manuscript-single__viewer-index-button"
          data-manuscript-page-button
          data-page-number="{{ $page['pageNumber'] }}"
        >
          Pagina {{ $page['pageNumber'] }}
        </button>
      </li>
    @endforeach
  </ol>
</section>
```

- [ ] **Step 4: Re-run the focused test to verify the markup portion passes**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL only on the remaining JS/style assertions.

- [ ] **Step 5: Commit**

```bash
git add resources/views/partials/content-single-manuscrito.blade.php tests/manuscripts/single_manuscrito_viewer_markup_test.php
git commit -m "refactor: simplify manuscript viewer markup"
```

### Task 3: Replace the custom viewer script with OpenSeadragon

**Files:**
- Modify: `resources/assets/scripts/routes/single-manuscrito.js`
- Test: `tests/manuscripts/single_manuscrito_viewer_markup_test.php`

- [ ] **Step 1: Tighten the JS assertions before implementation**

Extend the test with route-specific expectations:

```php
assertTrueValue(strpos($script, 'viewer.open(') !== false, 'Single manuscript route should open the active page in OpenSeadragon');
assertTrueValue(strpos($script, 'goToPage(') !== false, 'Single manuscript route should expose a page-navigation helper');
assertTrueValue(strpos($script, 'scaleMode') === false, 'Single manuscript route should remove legacy scale state');
assertTrueValue(strpos($script, 'manualScale') === false, 'Single manuscript route should remove legacy manual zoom state');
assertTrueValue(strpos($script, 'renderHighlights') === false, 'Single manuscript route should remove highlight rendering');
```

- [ ] **Step 2: Run the focused test and verify it fails on the old route**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL because the route still implements the old image/overlay viewer.

- [ ] **Step 3: Replace the route implementation**

Rewrite `resources/assets/scripts/routes/single-manuscrito.js` around `OpenSeadragon`:

```js
import OpenSeadragon from 'openseadragon';

function setButtonState(buttons, activePageNumber) {
  buttons.forEach((button) => {
    const isActive = Number(button.dataset.pageNumber) === activePageNumber;

    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
}

export default {
  init() {
    const viewerRoot = document.querySelector('[data-manuscript-viewer]');

    if (!viewerRoot) {
      return;
    }

    const osdElement = viewerRoot.querySelector('[data-manuscript-osd]');
    const pagesScript = viewerRoot.querySelector('[data-manuscript-pages]');
    const pageLabel = viewerRoot.querySelector('[data-manuscript-page-label]');
    const pageButtons = Array.from(viewerRoot.querySelectorAll('[data-manuscript-page-button]'));
    const pageLinks = Array.from(document.querySelectorAll('[data-manuscript-go-page]'));
    const previousButton = viewerRoot.querySelector('[data-manuscript-prev]');
    const nextButton = viewerRoot.querySelector('[data-manuscript-next]');
    const initialPageNumber = Number(viewerRoot.dataset.initialPageNumber || 0);
    const pages = pagesScript ? JSON.parse(pagesScript.textContent || '[]') : [];

    if (!osdElement || !pageLabel || !previousButton || !nextButton || !pages.length) {
      return;
    }

    const viewer = OpenSeadragon({
      element: osdElement,
      prefixUrl: '/app/themes/chao/dist/images/openseadragon/',
      showNavigationControl: true,
      showHomeControl: true,
      showFullPageControl: false,
      showRotationControl: false,
      animationTime: 0.2,
      maxZoomPixelRatio: 2,
    });

    const state = { activeIndex: 0 };

    function updateNavState() {
      previousButton.disabled = state.activeIndex <= 0;
      nextButton.disabled = state.activeIndex >= pages.length - 1;
    }

    function goToPage(index) {
      const boundedIndex = Math.min(Math.max(index, 0), pages.length - 1);
      const page = pages[boundedIndex];

      state.activeIndex = boundedIndex;
      viewer.open({ type: 'image', url: page.imageUrl });
      pageLabel.textContent = `Pagina ${page.pageNumber} de ${pages.length}`;
      setButtonState(pageButtons, page.pageNumber);
      updateNavState();
    }

    pageButtons.forEach((button, index) => {
      button.addEventListener('click', () => goToPage(index));
    });

    pageLinks.forEach((link) => {
      link.addEventListener('click', () => {
        const pageNumber = Number(link.dataset.manuscriptGoPage || 0);
        const pageIndex = pages.findIndex((page) => page.pageNumber === pageNumber);

        if (pageIndex >= 0) {
          goToPage(pageIndex);
        }
      });
    });

    previousButton.addEventListener('click', () => goToPage(state.activeIndex - 1));
    nextButton.addEventListener('click', () => goToPage(state.activeIndex + 1));

    const startIndex = Math.max(0, pages.findIndex((page) => page.pageNumber === initialPageNumber));

    goToPage(startIndex);
  },
};
```

Note: if the chosen `OpenSeadragon` version requires a different image-open signature or asset path, adapt the exact call without reintroducing the legacy viewer logic.

- [ ] **Step 4: Re-run the focused test to verify the JS migration passes**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL only on remaining style assertions, or PASS if styles were already updated alongside the script.

- [ ] **Step 5: Commit**

```bash
git add resources/assets/scripts/routes/single-manuscrito.js tests/manuscripts/single_manuscrito_viewer_markup_test.php
git commit -m "feat: migrate manuscript viewer to openseadragon"
```

### Task 4: Refresh styles and remove legacy viewer CSS

**Files:**
- Modify: `resources/assets/styles/layouts/_manuscripts.scss`
- Test: `tests/manuscripts/single_manuscrito_viewer_markup_test.php`

- [ ] **Step 1: Add failing style assertions for the new container**

Ensure the test checks for the new class and absence of dead selectors:

```php
assertTrueValue(strpos($styles, '.manuscript-single__viewer-osd') !== false, 'Manuscript styles should include the OpenSeadragon container');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-frame') === false, 'Manuscript styles should remove the legacy viewer frame');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-overlay') === false, 'Manuscript styles should remove the legacy viewer overlay');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-zoom') === false, 'Manuscript styles should remove the legacy zoom toolbar group');
```

- [ ] **Step 2: Run the focused test and verify it fails on old styles**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: FAIL because `_manuscripts.scss` still contains the old viewer selectors.

- [ ] **Step 3: Replace the old viewer styles**

Update the viewer portion of `resources/assets/styles/layouts/_manuscripts.scss` to a structure like:

```scss
.manuscript-single__viewer-toolbar,
.manuscript-single__viewer-nav {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: rem(10px);
}

.manuscript-single__viewer-toolbar {
  justify-content: space-between;
  margin-bottom: rem(18px);
}

.manuscript-single__viewer-osd {
  background: #f3f0ea;
  border: solid 1px rgba($black, 0.15);
  height: min(75vh, rem(760px));
  margin-bottom: rem(18px);
  width: 100%;
}

.manuscript-single__viewer-osd .openseadragon-container {
  height: 100%;
  width: 100%;
}
```

Remove the now-dead blocks for:

```scss
.manuscript-single__viewer-frame
.manuscript-single__viewer-stage
.manuscript-single__viewer-canvas
.manuscript-single__viewer-overlay
.manuscript-single__viewer-highlight
.manuscript-single__viewer-highlight--approximate
.manuscript-single__viewer-highlight-label
.manuscript-single__viewer-zoom
```

- [ ] **Step 4: Run the focused test and stylelint**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: PASS

Run: `./node_modules/.bin/stylelint resources/assets/styles/layouts/_manuscripts.scss`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/assets/styles/layouts/_manuscripts.scss tests/manuscripts/single_manuscrito_viewer_markup_test.php
git commit -m "style: refresh manuscript viewer layout"
```

### Task 5: Verify the full integration and document residual risks

**Files:**
- Modify: `tests/manuscripts/single_manuscrito_viewer_markup_test.php` (only if a small assertion adjustment is needed after real build output)

- [ ] **Step 1: Run the focused manuscript search regression**

Run: `php tests/manuscripts/search_test.php`

Expected: PASS, confirming backend search/page resolution still works after the frontend migration.

- [ ] **Step 2: Run the focused viewer markup test again**

Run: `php tests/manuscripts/single_manuscrito_viewer_markup_test.php`

Expected: PASS

- [ ] **Step 3: Run the theme verification commands**

Run: `composer test`

Expected: PASS (allowing the existing Composer cache/plugin warnings if exit code remains `0`)

Run: `npm run build`

Expected: PASS with `OpenSeadragon` bundled into the theme assets

- [ ] **Step 4: Perform manual verification in the browser**

Verify on a real `single-manuscrito` page:

- the viewer loads the initial page
- `Anterior` and `Proxima` change the open page
- clicking a page in the index changes the open page
- clicking a search result opens the corresponding page in the viewer
- mouse wheel / trackpad / touch gestures perform native `OpenSeadragon` zoom and pan

If `OpenSeadragon` control icons require additional static assets beyond JS bundling, capture that as a follow-up before marking the task complete.

- [ ] **Step 5: Commit**

```bash
git add tests/manuscripts/single_manuscrito_viewer_markup_test.php package.json yarn.lock resources/views/partials/content-single-manuscrito.blade.php resources/assets/scripts/routes/single-manuscrito.js resources/assets/styles/layouts/_manuscripts.scss
git commit -m "test: verify manuscript openseadragon viewer"
```

## Self-Review

- Spec coverage: the plan covers dependency installation, viewer markup migration, JS migration, style cleanup, preserved page navigation, and search-result page sync. Highlights are explicitly excluded from this phase.
- Placeholder scan: commands, files, and expected markup/script structure are concrete for each task.
- Type consistency: the plan consistently uses `data-manuscript-osd`, `activeIndex`, `pageNumber`, `imageUrl`, and `initial_page_number`.
