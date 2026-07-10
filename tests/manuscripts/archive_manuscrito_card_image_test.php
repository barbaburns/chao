<?php

$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_posts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(
    preg_match('/\.archive\.category \.type-manuscrito\.post,\s*\.archive\.tag \.type-manuscrito\.post,\s*\.blog \.type-manuscrito\.post,\s*\.archive\.date \.type-manuscrito\.post\s*\{.*?\.feautured-image\s*\{[^}]*overflow:\s*hidden;[^}]*position:\s*relative;.*?&::before\s*\{[^}]*content:\s*\'\';[^}]*display:\s*block;[^}]*padding-top:\s*100%;.*?img\s*\{[^}]*height:\s*100%;[^}]*left:\s*0;[^}]*object-fit:\s*cover;[^}]*position:\s*absolute;[^}]*top:\s*0;[^}]*width:\s*100%;/s', $styles) === 1,
    'Archive post styles should force manuscript card images into the same square crop ratio as the other cards'
);

echo 'archive_manuscrito_card_image_test: ok' . PHP_EOL;
