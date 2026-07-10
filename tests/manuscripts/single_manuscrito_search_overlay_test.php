<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($template, 'class="manuscript-single__search"') === false, 'Manuscript template should remove the standalone search section');
assertTrueValue(strpos($template, 'data-manuscript-overlay-controls') !== false, 'Manuscript template should render overlay controls inside the viewer shell');
assertTrueValue(strpos($template, 'data-manuscript-search-toggle') !== false, 'Manuscript template should render a search toggle button in the overlay controls');
assertTrueValue(strpos($template, 'data-manuscript-search-overlay') !== false, 'Manuscript template should render a collapsible overlay search panel');
assertTrueValue(strpos($template, 'data-manuscript-search-input') !== false, 'Manuscript template should render a dedicated overlay search input');
assertTrueValue(strpos($template, 'data-manuscript-search-close') !== false, 'Manuscript template should render a close action for the overlay search panel');
assertTrueValue(strpos($template, 'data-manuscript-search-reset') !== false, 'Manuscript template should render a clear-search button beside the search toggle');
assertTrueValue(strpos($template, 'data-manuscript-tooltip="voltar ao manuscrito"') !== false, 'Manuscript clear-search button should expose the requested tooltip');
assertTrueValue(strpos($template, '>↩</button>') !== false, 'Manuscript clear-search button should use a return arrow icon');
assertTrueValue(
    preg_match('/data-manuscript-search-reset.*?@if\(!\$search\[\'has_query\'\]\) hidden @endif/s', $template) === 1,
    'Manuscript clear-search button should only be visible when a search is active'
);

assertTrueValue(strpos($script, 'data-manuscript-search-toggle') !== false, 'Single manuscript route should target the search toggle control');
assertTrueValue(strpos($script, 'data-manuscript-search-overlay') !== false, 'Single manuscript route should target the overlay search panel');
assertTrueValue(strpos($script, 'data-manuscript-search-input') !== false, 'Single manuscript route should target the overlay search input');
assertTrueValue(strpos($script, 'data-manuscript-search-close') !== false, 'Single manuscript route should target the overlay search close action');
assertTrueValue(strpos($script, 'data-manuscript-search-reset') !== false, 'Single manuscript route should target the clear-search button');
assertTrueValue(strpos($script, "searchToggleButton.addEventListener('click'") !== false, 'Single manuscript route should open the overlay search from the toggle button');
assertTrueValue(strpos($script, "searchCloseButton.addEventListener('click'") !== false, 'Single manuscript route should close the overlay search from the close button');
assertTrueValue(strpos($script, "searchResetButton.addEventListener('click'") !== false, 'Single manuscript route should handle the clear-search button');
assertTrueValue(strpos($script, 'updateSearchResetButton(requestUrl)') !== false, 'Single manuscript route should update clear-search button visibility after fullscreen searches');
assertTrueValue(strpos($script, 'function updateViewerFromSearch') !== false, 'Single manuscript route should update fullscreen search results without navigating away');
assertTrueValue(strpos($script, 'window.fetch(requestUrl.toString()') !== false, 'Single manuscript route should fetch fullscreen search results asynchronously');
assertTrueValue(
    preg_match('/const hasPages = replaceViewerPages.*?setSearchOpen\(!hasPages,\s*!hasPages\);.*?if\s*\(hasPages\)\s*\{.*?osdElement\.focus\(\);/s', $script) === 1,
    'Single manuscript route should keep the search form open and focused when a fullscreen search has no results'
);
assertTrueValue(
    preg_match('/searchOverlay\.addEventListener\(\'submit\'.*?if\s*\(!state\.isFullscreen\).*?event\.preventDefault\(\);.*?updateViewerFromSearch/s', $script) === 1,
    'Single manuscript route should prevent form navigation while searching in fullscreen'
);

echo 'single_manuscrito_search_overlay_test: ok' . PHP_EOL;
