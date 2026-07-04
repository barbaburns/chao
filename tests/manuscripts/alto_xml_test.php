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

function assertThrows($callable, $expectedClass, $expectedMessage, $message)
{
    try {
        $callable();
    } catch (\Throwable $throwable) {
        assertSameValue($expectedClass, get_class($throwable), $message . ' should throw the expected exception class');
        assertSameValue($expectedMessage, $throwable->getMessage(), $message . ' should throw the expected exception message');

        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected exception: ' . $expectedClass . PHP_EOL);
    exit(1);
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

$v3Xml = str_replace('ns-v4#', 'ns-v3#', $xml);
$v3Result = AltoXml::parse($v3Xml);

assertSameValue('Os dias', $v3Result['text'], 'Parser should discover supported ALTO namespaces dynamically');
assertSameValue(6304, $v3Result['page']['width'], 'Parser should keep page width for other supported ALTO versions');

$previousErrorHandler = set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

try {
    assertThrows(function () {
        AltoXml::parse('<alto');
    }, 'InvalidArgumentException', 'Invalid ALTO XML.', 'Parser should reject invalid XML without emitting PHP warnings');
} finally {
    restore_error_handler();
}

$unsupportedNamespaceXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<alto xmlns="http://example.com/alto">
  <Layout>
    <Page WIDTH="6304" HEIGHT="8739">
      <PrintSpace>
        <TextBlock>
          <TextLine HPOS="488" VPOS="371" WIDTH="3826" HEIGHT="3088">
            <String CONTENT="Os"/>
          </TextLine>
        </TextBlock>
      </PrintSpace>
    </Page>
  </Layout>
</alto>
XML;

assertThrows(function () use ($unsupportedNamespaceXml) {
    AltoXml::parse($unsupportedNamespaceXml);
}, 'InvalidArgumentException', 'Unsupported ALTO XML namespace.', 'Parser should fail explicitly for unsupported namespaces');

$multiPageXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<alto xmlns="http://www.loc.gov/standards/alto/ns-v4#">
  <Layout>
    <Page WIDTH="6304" HEIGHT="8739">
      <PrintSpace>
        <TextBlock>
          <TextLine HPOS="488" VPOS="371" WIDTH="3826" HEIGHT="3088">
            <String CONTENT="Primeira"/>
          </TextLine>
        </TextBlock>
      </PrintSpace>
    </Page>
    <Page WIDTH="6304" HEIGHT="8739">
      <PrintSpace>
        <TextBlock>
          <TextLine HPOS="488" VPOS="371" WIDTH="3826" HEIGHT="3088">
            <String CONTENT="Segunda"/>
          </TextLine>
        </TextBlock>
      </PrintSpace>
    </Page>
  </Layout>
</alto>
XML;

assertThrows(function () use ($multiPageXml) {
    AltoXml::parse($multiPageXml);
}, 'InvalidArgumentException', 'ALTO XML must contain exactly one page.', 'Parser should reject multi-page ALTO XML');

echo 'alto_xml_test: ok' . PHP_EOL;
