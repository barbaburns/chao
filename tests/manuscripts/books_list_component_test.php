<?php

$component = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/books-list.blade.php');
$minimalCard = @file_get_contents(dirname(__DIR__, 2) . '/resources/views/woocommerce/content-product-related-manuscript.blade.php');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($component, '$variant') !== false, 'Books list component should support selecting a related-book card variant');
assertTrueValue(strpos($component, "content-product-related-manuscript") !== false, 'Books list component should support the simplified manuscript related-book card');
assertTrueValue(strpos($component, "content-product-simple") !== false, 'Books list component should preserve the default related-book card for other templates');
assertTrueValue($minimalCard !== false, 'Theme should provide a dedicated simplified related-book card template for manuscripts');
assertTrueValue(strpos($minimalCard, 'woocommerce-loop-product__title') !== false, 'Simplified manuscript related-book card should render the product title');
assertTrueValue(strpos($minimalCard, 'price') !== false, 'Simplified manuscript related-book card should render the product price');
assertTrueValue(strpos($minimalCard, 'btn-learn-more') === false, 'Simplified manuscript related-book card should not render the learn-more button');
assertTrueValue(strpos($minimalCard, 'woocommerce_after_shop_loop_item') === false, 'Simplified manuscript related-book card should not render the default after-loop hooks');
assertTrueValue(strpos($styles, '.product-related-manuscript') !== false, 'Manuscript styles should define the simplified related-book card layout');

echo 'books_list_component_test: ok' . PHP_EOL;
