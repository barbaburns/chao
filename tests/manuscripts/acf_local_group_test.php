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
assertTrueValue(strpos($acfConfig, "add_action('acf/init'") !== false, 'Local ACF config should register options pages and local groups on acf/init');
assertTrueValue(strpos($acfConfig, "if (function_exists('acf_add_options_page')) {\n    acf_add_options_page") === false, 'Local ACF config should not register options pages at file load time');
assertTrueValue(strpos($acfConfig, "if (function_exists('acf_add_local_field_group')) {\n    acf_add_local_field_group") === false, 'Local ACF config should not register local field groups at file load time');

echo 'acf_local_group_test: ok' . PHP_EOL;
