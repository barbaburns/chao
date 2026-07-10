<?php

namespace {
    $registeredPostTypes = [];

    function __($text, $domain = null)
    {
        return $text;
    }

    function register_post_type($postType, $args)
    {
        global $registeredPostTypes;

        $registeredPostTypes[$postType] = $args;
    }
}

namespace App\Manuscripts {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

    function assertSameValue($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            fwrite(STDERR, $message . PHP_EOL);
            fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
            fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
            exit(1);
        }
    }

    function assertTrueValue($condition, $message)
    {
        if (!$condition) {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }
    }

    PostType::register();

    global $registeredPostTypes;

    assertTrueValue(isset($registeredPostTypes['manuscrito']), 'Manuscript post type should be registered');
    assertSameValue(
        ['category', 'post_tag'],
        $registeredPostTypes['manuscrito']['taxonomies'],
        'Manuscript post type should support the native category and tag taxonomies'
    );

    echo 'post_type_test: ok' . PHP_EOL;
}
