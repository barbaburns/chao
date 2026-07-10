<?php

$root = dirname(__DIR__, 2);
$template = file_get_contents($root . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents($root . '/resources/assets/scripts/routes/single-manuscrito.js');
$styles = file_get_contents($root . '/resources/assets/styles/layouts/_manuscripts.scss');
$package = file_get_contents($root . '/package.json');
$lockfile = file_get_contents($root . '/yarn.lock');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function assertContains($needle, $haystack, $message)
{
    assertTrueValue(strpos($haystack, $needle) !== false, $message);
}

assertContains('class="post-header"', $template, 'Manuscript template should render the shared post header');
assertContains('class="post-categories"', $template, 'Manuscript template should expose native categories');
assertContains('class="post-tags"', $template, 'Manuscript template should expose native tags');
assertContains('class="entry-content manuscript-single__content"', $template, 'Manuscript template should define its content container');
assertContains("@include('components.books-list'", $template, 'Manuscript template should render related books');
assertContains("'variant' => 'manuscript'", $template, 'Manuscript template should request manuscript book cards');
assertTrueValue(strpos($template, 'class="manuscript-single__results"') === false, 'Manuscript template should not render a standalone results list');
assertTrueValue(strpos($template, 'class="manuscript-single__search"') === false, 'Manuscript template should not render a standalone search section');

assertTrueValue(preg_match('/"openseadragon"\s*:\s*"\^5\.0\.1"/', $package) === 1, 'Theme should declare OpenSeadragon');
assertTrueValue(preg_match('/^openseadragon@\^5\.0\.1:\s*$/m', $lockfile) === 1, 'Theme lockfile should pin OpenSeadragon');

$viewerHooks = [
    'data-manuscript-viewer',
    'data-manuscript-pages',
    'data-manuscript-osd',
    'data-manuscript-prev',
    'data-manuscript-next',
    'data-manuscript-page-label',
    'data-manuscript-page-jump-form',
    'data-manuscript-page-input',
    'data-manuscript-search-toggle',
    'data-manuscript-search-overlay',
    'data-manuscript-search-reset',
    'data-manuscript-fit-page',
    'data-manuscript-zoom-out',
    'data-manuscript-zoom-in',
    'data-manuscript-fit-width',
    'data-manuscript-fullscreen',
];

foreach ($viewerHooks as $hook) {
    assertContains($hook, $template, "Manuscript template should expose {$hook}");
}

assertContains('highlightBoxes', $template, 'Manuscript template should serialize highlight boxes');
assertContains('pageWidth', $template, 'Manuscript template should serialize page width');
assertContains('pageHeight', $template, 'Manuscript template should serialize page height');
assertContains('aria-label="Pagina anterior"', $template, 'Previous-page control should be labelled');
assertContains('aria-label="Proxima pagina"', $template, 'Next-page control should be labelled');
assertTrueValue(strpos($template, 'data-manuscript-zoom-actual') === false, 'Manuscript template should not restore the removed 1:1 control');
assertTrueValue(strpos($template, 'data-manuscript-page-button') === false, 'Manuscript template should not render the full page index');

assertTrueValue(preg_match('/import\s+OpenSeadragon\s+from\s+[\'"]openseadragon[\'"]/', $script) === 1, 'Viewer route should import OpenSeadragon');
assertContains("drawer: ['html', 'canvas', 'webgl']", $script, 'Viewer route should configure compatible drawers');
assertContains('function goToPage', $script, 'Viewer route should define page navigation');
assertContains('osd.open(', $script, 'Viewer route should open manuscript images through OpenSeadragon');
assertContains("previousButton.addEventListener('click'", $script, 'Viewer route should handle previous-page clicks');
assertContains("nextButton.addEventListener('click'", $script, 'Viewer route should handle next-page clicks');
assertContains("fitPageButton.addEventListener('click'", $script, 'Viewer route should handle fit-page clicks');
assertContains("zoomOutButton.addEventListener('click'", $script, 'Viewer route should handle zoom-out clicks');
assertContains("zoomInButton.addEventListener('click'", $script, 'Viewer route should handle zoom-in clicks');
assertContains("fitWidthButton.addEventListener('click'", $script, 'Viewer route should handle fit-width clicks');
assertContains("fullscreenButton.addEventListener('click'", $script, 'Viewer route should handle fullscreen clicks');
assertContains('goHome(true)', $script, 'Viewer route should fit the whole page');
assertContains('fitHorizontally(true)', $script, 'Viewer route should fit the page width');
assertContains('renderHighlightOverlays', $script, 'Viewer route should render search highlights');
assertContains('addOverlay(', $script, 'Viewer route should add OpenSeadragon overlays');
assertContains("osdElement.setAttribute('tabindex', '0')", $script, 'Viewer should be keyboard focusable');
assertContains("osdElement.setAttribute('role', 'region')", $script, 'Viewer should expose an accessible region');
assertContains("pageLabel.setAttribute('aria-live', 'polite')", $script, 'Viewer should announce page changes');
assertContains("osdElement.addEventListener('keydown'", $script, 'Viewer should support keyboard controls');
assertContains("osdElement.addEventListener('touchstart'", $script, 'Viewer should support mobile swipe navigation');
assertContains('webkitRequestFullscreen', $script, 'Viewer should support Safari fullscreen');
assertContains('setPseudoFullscreen', $script, 'Viewer should provide a mobile fullscreen fallback');

$styleSelectors = [
    '.manuscript-single__viewer-osd',
    '.manuscript-single__viewer-overlay-controls',
    '.manuscript-single__viewer-actions',
    '.manuscript-single__viewer-compact-button',
    '.manuscript-single__viewer-edge-button',
    '.manuscript-single__viewer-edge-icon',
    '.manuscript-single__viewer-jump',
    '.manuscript-single__viewer-search-form',
    '.manuscript-single__viewer-highlight',
    '.manuscript-single__viewer.is-fullscreen',
    '.manuscript-single__viewer.is-pseudo-fullscreen',
];

foreach ($styleSelectors as $selector) {
    assertContains($selector, $styles, "Manuscript styles should define {$selector}");
}

assertTrueValue(preg_match('/\.manuscript-single__viewer-frame\b/', $styles) === 0, 'Manuscript styles should not restore the legacy viewer frame');
assertTrueValue(preg_match('/\.manuscript-single__viewer-canvas\b/', $styles) === 0, 'Manuscript styles should not restore the legacy viewer canvas');

echo 'single_manuscrito_viewer_markup_test: ok' . PHP_EOL;
