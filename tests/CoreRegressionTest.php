<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class CoreRegressionTest extends TestCase
{
    private const CORE = __DIR__ . '/../prescia/core.php';

    public function testAddLinkChecksResolvedJavascriptAndStylesheetFiles(): void
    {
        $core = (string) file_get_contents(self::CORE);

        self::assertStringContainsString("else if (!is_file(\$file)) {", $core);
        self::assertStringContainsString("else if (!is_file(\$tfile)) {", $core);
        self::assertStringContainsString("'Javascript not found',$file,\"addLink\"", $core);
        self::assertStringContainsString("'Style not found',$file,\"addLink\"", $core);
        self::assertStringNotContainsString("'File not found',$file,\"addLink\"", $core);
    }

    public function testMetadataReadsMetaXmlOnlyWhenTheFileExists(): void
    {
        $core = (string) file_get_contents(self::CORE);

        self::assertStringContainsString(
            "if (is_file(CONS_PATH_PAGES.\$_SESSION['CODE'].\"/template/_meta.xml\"))",
            $core
        );
        self::assertStringNotContainsString(
            "if (CONS_PATH_PAGES.\$_SESSION['CODE'].\"/template/_meta.xml\")",
            $core
        );
    }
}
