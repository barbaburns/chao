<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\AltoXml;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<alto xmlns="http://www.loc.gov/standards/alto/ns-v4#">
  <Layout>
    <Page WIDTH="6304" HEIGHT="8739">
      <PrintSpace>
        <TextBlock>
          <TextLine HPOS="488" VPOS="371" WIDTH="3826" HEIGHT="3088">
            <String CONTENT="Os"/>
            <SP WIDTH="20"/>
            <String CONTENT="dias"/>
          </TextLine>
        </TextBlock>
      </PrintSpace>
    </Page>
  </Layout>
</alto>
XML;

$result = AltoXml::parse($xml);

assertSameValue('Os dias', $result['text'], 'Parser should concatenate line text');
assertSameValue(6304, $result['page']['width'], 'Parser should keep page width');
assertSameValue(8739, $result['page']['height'], 'Parser should keep page height');
assertSameValue('Os dias', $result['lines'][0]['text'], 'Parser should keep line text');
assertSameValue(488.0, $result['lines'][0]['bbox']['x'], 'Parser should keep line x');
assertSameValue('Os', $result['lines'][0]['words'][0]['text'], 'Parser should create word entries');

echo 'alto_xml_test: ok' . PHP_EOL;
