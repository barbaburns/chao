<?php

$GLOBALS['registered_filters'] = [];

function add_filter($hook, $callback, $priority = 10, $acceptedArgs = 1)
{
    $GLOBALS['registered_filters'][$hook][] = [
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $acceptedArgs,
    ];
}

function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1)
{
}

function remove_action($hook, $callback, $priority = 10)
{
}

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

require_once dirname(__DIR__) . '/app/woocommerce.php';

$callbacks = $GLOBALS['registered_filters']['woocommerce_correios_email_tracking_code_url'] ?? [];

assertTrueValue($callbacks !== [], 'Expected WooCommerce Correios tracking-code email filter to be registered.');

$trackingFilter = $callbacks[0];

assertSameValue(3, $trackingFilter['accepted_args'], 'The tracking-code email filter should accept the HTML, code, and order arguments.');
assertTrueValue(is_callable($trackingFilter['callback']), 'The tracking-code email filter callback should be callable.');

$linkedHtml = '<a href="https://example.test/order/123#wc-correios-tracking">AB123456789BR</a>';
$trackingCode = 'AB123456789BR';
$order = (object) ['id' => 123];

assertSameValue(
    $trackingCode,
    call_user_func($trackingFilter['callback'], $linkedHtml, $trackingCode, $order),
    'The tracking-code email filter should return only the raw tracking code.'
);

echo 'correios_tracking_email_plain_code_test: ok' . PHP_EOL;
