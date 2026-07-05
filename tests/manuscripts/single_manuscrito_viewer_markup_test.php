<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');
$gridStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_grid.scss');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');
$package = file_get_contents(dirname(__DIR__, 2) . '/package.json');
$lockfile = file_get_contents(dirname(__DIR__, 2) . '/yarn.lock');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function extractMatch($pattern, $subject, $message)
{
    if (preg_match($pattern, $subject, $matches) !== 1) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    return $matches;
}

assertTrueValue(strpos($template, 'class="post-header"') !== false, 'Manuscript template should adopt the single-post header block');
assertTrueValue(strpos($template, "class=\"feautured-image\"") !== false, 'Manuscript template should render a featured-image block when the manuscript has a thumbnail');
assertTrueValue(strpos($template, '@if(has_post_thumbnail())') !== false, 'Manuscript template should guard the featured image block');
assertTrueValue(strpos($template, "get_related_books") === false, 'Manuscript template should not depend on the single-post controller variable name');
assertTrueValue(strpos($template, "'books' => \$related_books") !== false, 'Manuscript template should pass manuscript related books to the shared books-list component');
assertTrueValue(strpos($template, 'class="manuscript-single__sidebar"') !== false, 'Manuscript template should group search and related books inside a sidebar container');
assertTrueValue(strpos($template, 'class="entry-content manuscript-single__content"') !== false, 'Manuscript template should separate manuscript text from the sidebar');
assertTrueValue(strpos($template, '<div class="entry-content">') === false, 'Manuscript template should not keep a redundant outer entry-content wrapper');
assertTrueValue(strpos($template, 'data-manuscript-viewer') !== false, 'Manuscript template should preserve the viewer hook');
assertTrueValue(strpos($template, 'data-manuscript-pages') !== false, 'Manuscript template should preserve the serialized viewer payload');

$contentStart = strpos($template, '<div class="entry-content manuscript-single__content">');
$contentBody = strpos($template, '@php the_content() @endphp');
$viewerPosition = strpos($template, 'data-manuscript-viewer');
$resultsPosition = strpos($template, 'class="manuscript-single__results"');

assertTrueValue($contentStart !== false, 'Manuscript template should define a single main content container');
assertTrueValue($contentBody !== false && $contentBody > $contentStart, 'Manuscript template should render the manuscript text first inside the main content container');
assertTrueValue($viewerPosition !== false && $viewerPosition > $contentBody, 'Manuscript template should place the viewer after the manuscript text inside the main content container');
assertTrueValue($resultsPosition !== false && $resultsPosition > $viewerPosition, 'Manuscript template should place search results after the viewer inside the main content container');

$viewerSectionMatches = extractMatch(
    '/(?P<section><section\b[^>]*class="[^"]*\bmanuscript-single__viewer\b[^"]*"[^>]*>(?P<body>.*?)<\/section>)/s',
    $template,
    'Manuscript template should define the viewer section'
);
$viewerSectionMarkup = $viewerSectionMatches['section'];
$viewerSection = $viewerSectionMatches['body'];

$resultsSectionMatches = extractMatch(
    '/<section\b[^>]*class="[^"]*\bmanuscript-single__results\b[^"]*"[^>]*>(?P<body>.*?)<\/section>/s',
    $template,
    'Manuscript template should define the search results section'
);
$resultsSection = $resultsSectionMatches['body'];

assertTrueValue(preg_match('/"openseadragon"\s*:\s*"\^5\.0\.1"/', $package) === 1, 'Theme package should declare openseadragon as a dependency');
assertTrueValue(preg_match('/^openseadragon@\^5\.0\.1:\s*$/m', $lockfile) === 1, 'Theme lockfile should pin the openseadragon dependency');

assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-viewer') !== false, 'Manuscript template should keep the viewer wrapper hook');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-osd') !== false, 'Manuscript template should expose a dedicated OpenSeadragon container');
assertTrueValue(strpos($viewerSectionMarkup, 'manuscript-single__viewer-osd') !== false, 'Manuscript template should render the OpenSeadragon container class');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-pages') !== false, 'Manuscript template should keep serialized page data for the viewer');
assertTrueValue(strpos($template, 'highlightBoxes') !== false, 'Manuscript template should serialize highlight boxes in the viewer payload');
assertTrueValue(strpos($template, 'pageWidth') !== false, 'Manuscript template should serialize page width for viewer highlight normalization');
assertTrueValue(strpos($template, 'pageHeight') !== false, 'Manuscript template should serialize page height for viewer highlight normalization');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-prev') !== false, 'Manuscript template should keep previous-page navigation');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-next') !== false, 'Manuscript template should keep next-page navigation');
assertTrueValue(strpos($viewerSectionMarkup, 'manuscript-single__viewer-edge-button') !== false, 'Manuscript template should render overlay edge navigation buttons inside the viewer');
assertTrueValue(strpos($viewerSectionMarkup, 'manuscript-single__viewer-edge-icon') !== false, 'Manuscript template should render a dedicated lightweight edge-arrow glyph inside the viewer navigation buttons');
assertTrueValue(strpos($viewerSectionMarkup, 'aria-label="Pagina anterior"') !== false, 'Manuscript template should label the previous-page arrow button accessibly');
assertTrueValue(strpos($viewerSectionMarkup, 'aria-label="Proxima pagina"') !== false, 'Manuscript template should label the next-page arrow button accessibly');
assertTrueValue(strpos($viewerSectionMarkup, "@include('icon::arrow-left')") === false, 'Manuscript template should no longer reuse the product-gallery chevron icon for the previous-page viewer arrow');
assertTrueValue(strpos($viewerSectionMarkup, "@include('icon::arrow-right')") === false, 'Manuscript template should no longer reuse the product-gallery chevron icon for the next-page viewer arrow');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-fit-page') !== false, 'Manuscript template should expose a fit-page action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-zoom-actual') !== false, 'Manuscript template should expose a 100% zoom action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-fit-width') !== false, 'Manuscript template should expose a fit-width action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-fullscreen') !== false, 'Manuscript template should expose a fullscreen action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-tooltip="Ajustar pagina inteira"') !== false, 'Manuscript template should expose a custom tooltip label for the fit-page action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-tooltip="Zoom 100%"') !== false, 'Manuscript template should expose a custom tooltip label for the 100% action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-tooltip="Ajustar largura"') !== false, 'Manuscript template should expose a custom tooltip label for the fit-width action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-manuscript-tooltip="Tela cheia"') !== false, 'Manuscript template should expose a custom tooltip label for the fullscreen action');
assertTrueValue(strpos($viewerSectionMarkup, 'data-initial-page-number') !== false, 'Manuscript template should preserve the initial-page seed for the viewer');
assertTrueValue(strpos($viewerSection, 'data-manuscript-page-label') !== false, 'Manuscript template should keep the current-page label');
assertTrueValue(strpos($viewerSection, 'data-manuscript-page-jump-form') !== false, 'Manuscript template should expose a compact page-jump form');
assertTrueValue(strpos($viewerSection, 'data-manuscript-page-input') !== false, 'Manuscript template should expose a page-jump input');
assertTrueValue(strpos($viewerSection, 'data-manuscript-page-button') === false, 'Manuscript template should remove the full page index buttons');
assertTrueValue(strpos($viewerSection, 'data-manuscript-scale-mode') === false, 'Manuscript template should remove legacy scale-mode controls');
assertTrueValue(strpos($viewerSection, 'data-manuscript-zoom-out') === false, 'Manuscript template should remove legacy zoom-out controls');
assertTrueValue(strpos($viewerSection, 'data-manuscript-zoom-in') === false, 'Manuscript template should remove legacy zoom-in controls');
assertTrueValue(strpos($viewerSection, 'data-manuscript-highlight-label') === false, 'Manuscript template should remove the legacy highlight label');
assertTrueValue(strpos($viewerSection, 'data-manuscript-frame') === false, 'Manuscript template should remove the legacy viewer frame');
assertTrueValue(strpos($viewerSection, 'data-manuscript-stage') === false, 'Manuscript template should remove the legacy viewer stage');
assertTrueValue(strpos($viewerSection, 'data-manuscript-canvas') === false, 'Manuscript template should remove the legacy viewer canvas');
assertTrueValue(strpos($viewerSection, 'data-manuscript-image') === false, 'Manuscript template should remove the legacy viewer image node');
assertTrueValue(strpos($viewerSection, 'data-manuscript-overlay') === false, 'Manuscript template should remove the legacy viewer overlay');

assertTrueValue(strpos($template, 'manuscript-single__search-meta') !== false, 'Manuscript template should render a search summary block');
assertTrueValue(strpos($template, 'Limpar busca') !== false, 'Manuscript template should expose a clear-search action');
assertTrueValue(strpos($resultsSection, 'data-manuscript-go-page') !== false, 'Manuscript results should preserve page-jump links for the viewer');

assertTrueValue(preg_match('/import\s+OpenSeadragon\s+from\s+[\'"]openseadragon[\'"]\s*;?/', $script) === 1, 'Single manuscript route should import OpenSeadragon');
assertTrueValue(strpos($script, 'data-manuscript-viewer') !== false, 'Single manuscript route should still target the manuscript viewer');
assertTrueValue(preg_match('/\bOpenSeadragon\s*\(/', $script) === 1, 'Single manuscript route should initialize OpenSeadragon');
assertTrueValue(strpos($script, "drawer: ['html', 'canvas', 'webgl']") !== false, 'Single manuscript route should prefer the HTML drawer for large manuscript images');
assertTrueValue(strpos($script, 'data-manuscript-osd') !== false, 'Single manuscript route should target the OpenSeadragon container');
assertTrueValue(preg_match('/function\s+goToPage\s*\(/', $script) === 1, 'Single manuscript route should define goToPage for page changes');
assertTrueValue(preg_match('/\bosd\.open\s*\(/', $script) === 1, 'Single manuscript route should open manuscript pages through the OpenSeadragon instance');
assertTrueValue(strpos($script, 'initialPageNumber') !== false, 'Single manuscript route should preserve the initial page number handoff');
assertTrueValue(strpos($script, "previousButton.addEventListener('click'") !== false, 'Single manuscript route should keep previous-page button wiring');
assertTrueValue(strpos($script, "nextButton.addEventListener('click'") !== false, 'Single manuscript route should keep next-page button wiring');
assertTrueValue(strpos($script, 'data-manuscript-fit-page') !== false, 'Single manuscript route should target the fit-page control');
assertTrueValue(strpos($script, 'data-manuscript-zoom-actual') !== false, 'Single manuscript route should target the 100% zoom control');
assertTrueValue(strpos($script, 'data-manuscript-fit-width') !== false, 'Single manuscript route should target the fit-width control');
assertTrueValue(strpos($script, 'data-manuscript-fullscreen') !== false, 'Single manuscript route should target the fullscreen control');
assertTrueValue(strpos($script, "fitPageButton.addEventListener('click'") !== false, 'Single manuscript route should handle the fit-page action');
assertTrueValue(strpos($script, "zoomActualButton.addEventListener('click'") !== false, 'Single manuscript route should handle the 100% zoom action');
assertTrueValue(strpos($script, "fitWidthButton.addEventListener('click'") !== false, 'Single manuscript route should handle the fit-width action');
assertTrueValue(strpos($script, "fullscreenButton.addEventListener('click'") !== false, 'Single manuscript route should handle the fullscreen action');
assertTrueValue(strpos($script, 'requestFullscreen') !== false, 'Single manuscript route should request fullscreen mode for the viewer');
assertTrueValue(strpos($script, 'fullscreenchange') !== false, 'Single manuscript route should react to fullscreen changes');
assertTrueValue(strpos($script, 'goHome(true)') !== false, 'Single manuscript route should use goHome for the fit-page action');
assertTrueValue(strpos($script, 'imageToViewportZoom(1)') !== false, 'Single manuscript route should convert the 100% zoom level through the active tiled image');
assertTrueValue(strpos($script, 'fitHorizontally(true)') !== false, 'Single manuscript route should fit the manuscript page to the viewer width');
assertTrueValue(strpos($script, 'data-manuscript-go-page') !== false, 'Single manuscript route should preserve search-result page jumps');
assertTrueValue(strpos($script, 'data-manuscript-page-jump-form') !== false, 'Single manuscript route should target the compact page-jump form');
assertTrueValue(strpos($script, 'data-manuscript-page-input') !== false, 'Single manuscript route should target the page-jump input');
assertTrueValue(strpos($script, "pageLinks.forEach((link) => {") !== false, 'Single manuscript route should keep search-result page-jump wiring');
assertTrueValue(strpos($script, "pageJumpForm.addEventListener('submit'") !== false, 'Single manuscript route should handle compact page-jump submits');
assertTrueValue(strpos($script, "osdElement.setAttribute('tabindex', '0')") !== false, 'Single manuscript route should make the OpenSeadragon mount keyboard focusable');
assertTrueValue(strpos($script, "osdElement.setAttribute('role', 'region')") !== false, 'Single manuscript route should expose an accessible viewer region');
assertTrueValue(strpos($script, "pageLabel.setAttribute('aria-live', 'polite')") !== false, 'Single manuscript route should expose the page label as a live region');
assertTrueValue(strpos($script, "pageLabel.setAttribute('aria-atomic', 'true')") !== false, 'Single manuscript route should make live page announcements atomic');
assertTrueValue(strpos($script, "osdElement.addEventListener('keydown'") !== false, 'Single manuscript route should support keyboard viewer controls');
assertTrueValue(
    preg_match('/pageLinks\.forEach\(\(link\)\s*=>\s*\{.*?if\s*\(pageIndex\s*>=\s*0\)\s*\{.*?goToPage\(pageIndex\);.*?osdElement\.focus\(\);.*?\}.*?\}\);/s', $script) === 1,
    'Single manuscript route should focus the viewer within the search-result page-jump handler'
);
assertTrueValue(strpos($script, 'console.error(') !== false, 'Single manuscript route should log invalid manuscript page payloads');
assertTrueValue(strpos($script, 'data-manuscript-scale-mode') === false, 'Single manuscript route should remove scale-mode logic');
assertTrueValue(strpos($script, 'manuscript-single__viewer-highlight') !== false, 'Single manuscript route should render viewer highlight overlays');
assertTrueValue(strpos($script, 'clearOverlays') !== false, 'Single manuscript route should clear overlays between page changes');
assertTrueValue(strpos($script, 'addOverlay(') !== false, 'Single manuscript route should add OpenSeadragon overlays for search highlights');
assertTrueValue(strpos($script, 'x / pageWidth') !== false, 'Single manuscript route should normalize highlight X coordinates by the original page width');
assertTrueValue(strpos($script, 'y / pageWidth') !== false, 'Single manuscript route should normalize highlight Y coordinates by the original page width');
assertTrueValue(strpos($script, 'width / pageWidth') !== false, 'Single manuscript route should normalize highlight widths by the original page width');
assertTrueValue(strpos($script, 'height / pageWidth') !== false, 'Single manuscript route should normalize highlight heights by the original page width');

assertTrueValue(preg_match('/\.manuscript-single__viewer-osd\b/', $styles) === 1, 'Manuscript styles should define an OpenSeadragon viewer container');
assertTrueValue(preg_match('/\.manuscript-single__search-clear\b/', $styles) === 1, 'Manuscript styles should define the clear-search action');
assertTrueValue(preg_match('/\.manuscript-single__viewer-osd\s+\.openseadragon-container\b/', $styles) === 1, 'Manuscript styles should size the nested OpenSeadragon container');
assertTrueValue(preg_match('/\.manuscript-single__viewer-actions\b/', $styles) === 1, 'Manuscript styles should define the viewer zoom action group');
assertTrueValue(preg_match('/\.manuscript-single__viewer-compact-button\b/', $styles) === 1, 'Manuscript styles should define custom tooltip behavior for compact viewer buttons');
assertTrueValue(preg_match('/\.manuscript-single__viewer-edge-button\b/', $styles) === 1, 'Manuscript styles should define the overlay edge navigation buttons');
assertTrueValue(preg_match('/\.manuscript-single__viewer-edge-icon\b/', $styles) === 1, 'Manuscript styles should define the lightweight viewer edge-arrow glyph');
assertTrueValue(preg_match('/\.manuscript-single__viewer-jump\b/', $styles) === 1, 'Manuscript styles should define the compact page-jump controls');
assertTrueValue(strpos($styles, '.manuscript-single__viewer.is-fullscreen') !== false, 'Manuscript styles should define the fullscreen viewer state');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-edge-button:hover') === false, 'Manuscript styles should avoid hover-specific styling on the manuscript viewer arrows');
assertTrueValue(preg_match('/\.manuscript-single__viewer-frame\b/', $styles) === 0, 'Manuscript styles should remove the legacy viewer frame');
assertTrueValue(preg_match('/\.manuscript-single__viewer-stage\b/', $styles) === 0, 'Manuscript styles should remove the legacy viewer stage');
assertTrueValue(preg_match('/\.manuscript-single__viewer-canvas\b/', $styles) === 0, 'Manuscript styles should remove the legacy viewer canvas');
assertTrueValue(preg_match('/\.manuscript-single__viewer-overlay\b/', $styles) === 0, 'Manuscript styles should remove the legacy viewer overlay');
assertTrueValue(preg_match('/\.manuscript-single__viewer-highlight\b/', $styles) === 1, 'Manuscript styles should define highlight overlay styles');
assertTrueValue(preg_match('/\.manuscript-single__viewer-zoom\b/', $styles) === 0, 'Manuscript styles should remove the legacy zoom control group');
assertTrueValue(preg_match('/\.manuscript-single__viewer-index\b/', $styles) === 0, 'Manuscript styles should remove the full page index grid');
assertTrueValue(preg_match('/\.manuscript-single__title\b/', $styles) === 0, 'Manuscript styles should remove the stale manuscript title wrapper selector');
assertTrueValue(preg_match('/\.manuscript-single__page-header\b/', $styles) === 0, 'Manuscript styles should remove the stale manuscript page header selector');
assertTrueValue(preg_match('/\.manuscript-single__viewer-edge-button\s*\{\s*svg\s*\{/s', $styles) === 0, 'Manuscript styles should not keep the stale mobile SVG arrow size override');
assertTrueValue(
    preg_match('/\.single-post \.post,\s*\.single-manuscrito \.post,\s*\.blog-highlights-content,\s*\.featured-book,\s*\.single-product \.main-product,\s*\.woocommerce-cart \.main,\s*\.woocommerce-checkout \.main,\s*\.woocommerce-account \.main,\s*\.page-template-template-static \.main,\s*\.search-results \.main\s*\{[^}]*display:\s*grid;/s', $gridStyles) === 1,
    'Grid styles should include single manuscripts in the base grid container rule'
);
assertTrueValue(
    preg_match('/\.single-post \.post,\s*\.single-manuscrito \.post\s*\{[^}]*grid-template-areas:\s*"header\s+title"\s*"image\s+content"\s*"side\s+content";/s', $gridStyles) === 1,
    'Grid styles should define the manuscript desktop grid-template-areas contract'
);
assertTrueValue(
    preg_match('/@include media-breakpoint-down\(md\)\s*\{.*?\.single-post \.post,\s*\.single-manuscrito \.post,\s*\.woocommerce-checkout \.main,\s*\.woocommerce-account \.main,\s*\.page-template-template-static \.main,\s*\.search-results \.main\s*\{[^}]*grid-template-columns:\s*100%;[^}]*grid-gap:\s*0;/s', $gridStyles) === 1,
    'Grid styles should include single manuscripts in the shared mobile one-column foundation'
);
assertTrueValue(
    preg_match('/@include media-breakpoint-down\(md\)\s*\{.*?\.single-manuscrito \.post\s*\{[^}]*grid-template-areas:\s*"header"\s*"image"\s*"title"\s*"content"\s*"side";/s', $gridStyles) === 1,
    'Grid styles should define the manuscript mobile grid-template-areas contract'
);
assertTrueValue(
    preg_match('/\.grid-header,\s*\.single-post \.page-header,\s*\.single-manuscrito \.page-header,\s*\.woocommerce-cart \.page-header,\s*\.woocommerce-checkout \.page-header,\s*\.woocommerce-account \.page-header\s*\{\s*grid-area:\s*header;/s', $gridStyles) === 1,
    'Grid styles should map the manuscript page header to the shared header area'
);
assertTrueValue(strpos($gridStyles, '.single-manuscrito .post-header') !== false, 'Grid styles should place the manuscript header in the shared title area');
assertTrueValue(strpos($gridStyles, '.single-manuscrito .feautured-image') !== false, 'Grid styles should place the manuscript featured image in the left rail');
assertTrueValue(strpos($gridStyles, '.single-manuscrito .manuscript-single__sidebar') !== false, 'Grid styles should place the manuscript sidebar in the side column');
assertTrueValue(strpos($styles, '.manuscript-single__sidebar') !== false, 'Manuscript styles should define the new sidebar stack');
assertTrueValue(strpos($styles, '.manuscript-single__content') !== false, 'Manuscript styles should define the content block that precedes the viewer');

echo 'single_manuscrito_viewer_markup_test: ok' . PHP_EOL;
