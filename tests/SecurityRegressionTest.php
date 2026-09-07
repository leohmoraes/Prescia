<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class SecurityRegressionTest extends TestCase
{
    public function testForumPreviewUsesParameterizedQueriesForRequestIds(): void
    {
        $payload = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/preview.php');

        self::assertStringContainsString('$core->dbo->queryPrepared(', $payload);
        self::assertStringContainsString("'ii'", $payload);
        self::assertStringContainsString("'i'", $payload);
        self::assertStringNotContainsString(<<<'SQL'
WHERE id_forum=".$_POST['id_forum']
SQL, $payload);
        self::assertStringNotContainsString(<<<'SQL'
WHERE f.id=".$_POST['id_forum']
SQL, $payload);
    }
}
