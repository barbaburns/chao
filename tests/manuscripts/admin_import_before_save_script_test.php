<?php

$scriptPath = dirname(__DIR__, 2) . '/resources/assets/scripts/admin/manuscript-import-before-save.js';

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(file_exists($scriptPath), 'Import-before-save admin script should exist');

$scriptContents = file_get_contents($scriptPath);

assertTrueValue($scriptContents !== false, 'Import-before-save admin script should be readable');
assertTrueValue(strpos($scriptContents, '#manuscript-import .button') !== false, 'Import-before-save admin script should target the manuscript import metabox button');
assertTrueValue(strpos($scriptContents, 'getCurrentPostId') !== false, 'Import-before-save admin script should read the current Gutenberg post ID when available');
assertTrueValue(strpos($scriptContents, 'response_format') !== false, 'Import-before-save admin script should request JSON import responses');
assertTrueValue(strpos($scriptContents, 'history.replaceState') !== false, 'Import-before-save admin script should update the editor URL after draft creation');
assertTrueValue(strpos($scriptContents, 'pages_metabox') !== false, 'Import-before-save admin script should replace the imported-pages metabox HTML after import');

echo 'admin_import_before_save_script_test: ok' . PHP_EOL;
