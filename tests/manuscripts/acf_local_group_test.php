<?php

$acfConfig = file_get_contents(dirname(__DIR__, 2) . '/app/acf.php');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($acfConfig, "'name' => 'manuscript_zip_file'") !== false, 'Local ACF config should expose the manuscript ZIP field name');
assertTrueValue(strpos($acfConfig, "'label' => 'Pacote ZIP do manuscrito'") !== false, 'Local ACF config should label the field as manuscript ZIP');
assertTrueValue(strpos($acfConfig, "'mime_types' => 'zip'") !== false, 'Local ACF config should restrict uploads to ZIP files');
assertTrueValue(strpos($acfConfig, "'label' => 'PDF mestre'") === false, 'Local ACF config should no longer expose the legacy master PDF label');

echo 'acf_local_group_test: ok' . PHP_EOL;
