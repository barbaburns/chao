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

$manuscriptSectionPattern = '/@if\\(\\$manuscript\\).*?<div class="product-extra product-extra-manuscript">.*?<h2>Manuscrito<\\/h2>.*?<div class="product-manuscript">\\s*\\{!! \\$manuscript\\[\'text\'\\] !!\\}\\s*@if\\(\\$manuscript\\[\'url\'\\]\\)\\s*<p class="product-manuscript-link">\\s*<a href="\\{\\{ \\$manuscript\\[\'url\'\\] \\}\\}">Veja o manuscrito<\\/a>\\s*<\\/p>\\s*@endif\\s*<\\/div>/s';

assertTrueValue(preg_match($manuscriptSectionPattern, $template) === 1, 'Product manuscript template should render the manuscript rich text inside a static manuscript block');
assertTrueValue(strpos($template, "Veja o manuscrito") !== false, 'Product manuscript template should render a CTA label for the manuscript link');
assertTrueValue(strpos($template, 'product-manuscript-slider') === false, 'Product manuscript template should not render a manuscript slider container');
assertTrueValue(strpos($template, 'swiper-container product-manuscript') === false, 'Product manuscript template should not reuse Swiper container markup for manuscript text');
assertTrueValue(strpos($template, 'swiper-wrapper') === false, 'Product manuscript template should not render Swiper wrappers for manuscript text');
assertTrueValue(strpos($template, 'swiper-slide') === false, 'Product manuscript template should not render Swiper slides for manuscript text');
assertTrueValue(strpos($template, 'swiper-pagination-product-manuscript') === false, 'Product manuscript template should not render manuscript pagination');

assertTrueValue(strpos($sliderScript, 'function productManuscriptSwiper()') === false, 'Slider helpers should not define a manuscript Swiper initializer anymore');
assertTrueValue(strpos($productRouteScript, 'productManuscriptSwiper') === false, 'Product route should not import or initialize a manuscript Swiper anymore');

assertTrueValue(strpos($productStyles, '.product-manuscript-slider') === false, 'Product styles should not keep manuscript slider-specific styles');
assertTrueValue(strpos($productStyles, '.product-manuscript-slide') === false, 'Product styles should not keep manuscript slide-specific styles');
assertTrueValue(strpos($productStyles, '.product-manuscript') !== false, 'Product styles should still include a manuscript content block');

echo 'product_manuscript_slider_markup_test: ok' . PHP_EOL;
