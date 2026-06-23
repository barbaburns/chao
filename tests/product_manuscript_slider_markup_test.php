<?php

$template = file_get_contents(dirname(__DIR__) . '/resources/views/partials/product-extra.blade.php');
$sliderScript = file_get_contents(dirname(__DIR__) . '/resources/assets/scripts/partials/slider.js');
$productRouteScript = file_get_contents(dirname(__DIR__) . '/resources/assets/scripts/routes/product.js');
$productStyles = file_get_contents(dirname(__DIR__) . '/resources/assets/styles/components/_product.scss');

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

$manuscriptSectionPattern = '/@if\\(\\$manuscript\\).*?<div class="product-extra product-extra-manuscript">.*?<h2>Manuscrito<\\/h2>.*?<div class="swiper-container product-manuscript product-manuscript-slider">.*?<div class="swiper-wrapper">.*?<div class="swiper-slide">.*?<figure class="product-manuscript-slide">.*?<div class="swiper-pagination-product-manuscript"><\\/div>/s';
$productManuscriptSwiperPattern = '/function productManuscriptSwiper\\(\\)\\s*\\{(?P<body>.*?)\\n\\}\\n\\nexport/s';
$productRouteImportPattern = '/import\\s*\\{\\s*[^}]*\\bproductManuscriptSwiper\\b[^}]*\\}\\s*from\\s*[\'"]\\.\\.\\/partials\\/slider\\.js[\'"];/';
$productRouteFinalizePattern = '/finalize\\(\\)\\s*\\{(?P<body>.*?)\\n\\s*\\}/s';
$productManuscriptStylesPattern = '/\\.product-manuscript\\s*\\{(?P<body>.*?)\\}\\s*\\.product-manuscript-slider\\s*\\{/s';
$manuscriptImageBranchPattern = '/@if\\(\\$manuscript\\[\'url\'\\]\\)(?P<linked>.*?)@else(?P<bare>.*?)@endif/s';
$manuscriptLinkPattern = '/<a\\s+href="\\{\\{ \\$manuscript\\[\'url\'\\] \\}\\}"[^>]*>\\s*\\{!! \\$image\\[\'image\'\\] !!\\}/s';

assertTrueValue(preg_match($manuscriptSectionPattern, $template) === 1, 'Product manuscript template should render the slider markup inside the manuscript section structure');
assertTrueValue(strpos($template, 'swiper-container product-manuscript product-manuscript-slider') !== false, 'Product manuscript slider container should stay intact');
assertTrueValue(preg_match($manuscriptLinkPattern, $template) === 1, 'Product manuscript template should wrap slide images in a manuscript permalink when available');

$manuscriptImageBranchMatches = extractMatch($manuscriptImageBranchPattern, $template, 'Product manuscript template should keep both linked and bare image branches for manuscript slides');
$manuscriptBareImageBranch = $manuscriptImageBranchMatches['bare'];

assertTrueValue(strpos($manuscriptBareImageBranch, "{!! \$image['image'] !!}") !== false, 'Product manuscript template should render the bare slide image when the manuscript permalink is unavailable');
assertTrueValue(preg_match('/<a\\b/i', $manuscriptBareImageBranch) !== 1, 'Product manuscript template should not wrap the slide image in an anchor in the no-permalink branch');
assertTrueValue(strpos($template, 'product-manuscript-lightbox') === false, 'Product manuscript template should keep the slider image markup without manuscript lightbox links');

$productManuscriptSwiperMatches = extractMatch($productManuscriptSwiperPattern, $sliderScript, 'Slider script should define a productManuscriptSwiper function');
$productManuscriptSwiperBody = $productManuscriptSwiperMatches['body'];

assertTrueValue(strpos($productManuscriptSwiperBody, "new Swiper('.product-manuscript-slider'") !== false, 'productManuscriptSwiper should construct a Swiper for the manuscript slider');
assertTrueValue(strpos($productManuscriptSwiperBody, 'withBulletVisibility({') !== false, 'productManuscriptSwiper should wrap options with bullet visibility handling');
assertTrueValue(strpos($productManuscriptSwiperBody, "el: '.swiper-pagination-product-manuscript'") !== false, 'productManuscriptSwiper should scope pagination to the manuscript slider');
assertTrueValue(strpos($sliderScript, 'function productManuscriptLightbox()') === false, 'Slider script should not include a manuscript lightbox initializer');

assertTrueValue(preg_match($productRouteImportPattern, $productRouteScript) === 1, 'Product route should import productManuscriptSwiper from the slider partial');

$productRouteFinalizeMatches = extractMatch($productRouteFinalizePattern, $productRouteScript, 'Product route should define a finalize block');
$productRouteFinalizeBody = $productRouteFinalizeMatches['body'];

assertTrueValue(strpos($productRouteFinalizeBody, 'productManuscriptSwiper();') !== false, 'Product route should initialize the product manuscript slider in finalize()');
assertTrueValue(strpos($productRouteFinalizeBody, 'productManuscriptLightbox();') === false, 'Product route should not initialize a manuscript lightbox');

assertTrueValue(strpos($productStyles, '.product-manuscript-slider') !== false, 'Product styles should include slider-specific manuscript styles');
assertTrueValue(strpos($productStyles, '.product-manuscript-slide') !== false, 'Product styles should include manuscript slide styles');
assertTrueValue(strpos($productStyles, 'max-width: 400px;') !== false, 'Product manuscript slider styles should cap the slider width at 400px');

$productManuscriptStylesMatches = extractMatch($productManuscriptStylesPattern, $productStyles, 'Product styles should define a product manuscript block before the slider-specific styles');
$productManuscriptStylesBody = $productManuscriptStylesMatches['body'];

assertTrueValue(strpos($productManuscriptStylesBody, 'display: block;') !== false, 'Product manuscript styles should keep the manuscript block slider-oriented');
assertTrueValue(strpos($productManuscriptStylesBody, 'display: grid') === false, 'Product manuscript styles should not revert to a grid display');
assertTrueValue(strpos($productManuscriptStylesBody, 'grid-template') === false, 'Product manuscript styles should not restore grid template layout');

echo 'product_manuscript_slider_markup_test: ok' . PHP_EOL;
