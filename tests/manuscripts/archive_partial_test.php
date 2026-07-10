<?php

$partialPath = dirname(__DIR__, 2) . '/resources/views/partials/content-manuscrito.blade.php';
$indexTemplate = file_get_contents(dirname(__DIR__, 2) . '/resources/views/index.blade.php');
$categoryTemplate = file_get_contents(dirname(__DIR__, 2) . '/resources/views/category-imprensa.blade.php');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(file_exists($partialPath), 'Manuscript archives should provide a dedicated content-manuscrito partial');

$partial = file_get_contents($partialPath);

assertTrueValue(strpos($indexTemplate, "@include('partials.content-'.get_post_type())") !== false, 'Index template should keep selecting archive partials by post type');
assertTrueValue(strpos($categoryTemplate, "@include('partials.content-'.get_post_type())") !== false, 'Category template should keep selecting archive partials by post type');
assertTrueValue(strpos($partial, "post_class('post')") !== false, 'Manuscript archive partial should opt into the shared archive post card class');
assertTrueValue(strpos($partial, 'the_excerpt()') !== false, 'Manuscript archive partial should expose an excerpt summary');
assertTrueValue(strpos($partial, '<time class="updated" datetime="{{ get_post_time(\'c\', true) }}">') !== false, 'Manuscript archive partial should reuse the shared archive date metadata block');
assertTrueValue(strpos($partial, 'Leia o manuscrito completo') !== false, 'Manuscript archive partial should provide a manuscript-specific call to action');

echo 'archive_partial_test: ok' . PHP_EOL;
