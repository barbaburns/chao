<?php

$functionsConfig = file_get_contents(dirname(__DIR__) . '/resources/functions.php');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($functionsConfig, "add_action('init', function () {\n    if (!class_exists('Woocommerce\\\\Pagarme\\\\Core')) {") !== false, 'Pagar.me workaround should be registered on init');
assertTrueValue(strpos($functionsConfig, "add_action('after_setup_theme', function () {\n    if (!class_exists('Woocommerce\\\\Pagarme\\\\Core')) {") === false, 'Pagar.me workaround should no longer run during after_setup_theme');
assertTrueValue(strpos($functionsConfig, "add_action('init', function () use (\$callback) {") !== false, 'Pagar.me workaround should still re-register the reflected callback on init');

echo 'pagarme_init_workaround_test: ok' . PHP_EOL;
