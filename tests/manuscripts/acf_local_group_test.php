<?php

$acfConfig = file_get_contents(dirname(__DIR__, 2) . '/app/acf.php');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($acfConfig, "'name' => 'manuscript_source_subfolder'") !== false, 'Local ACF config should expose the manuscript source subfolder field name');
assertTrueValue(strpos($acfConfig, "'label' => 'Subpasta fonte do manuscrito'") !== false, 'Local ACF config should label the field as manuscript source subfolder');
assertTrueValue(strpos($acfConfig, "'type' => 'text'") !== false, 'Local ACF config should use a text field for the manuscript source subfolder');
assertTrueValue(strpos($acfConfig, "'name' => 'manuscript_zip_file'") === false, 'Local ACF config should no longer expose the manuscript ZIP field name');

echo 'acf_local_group_test: ok' . PHP_EOL;
