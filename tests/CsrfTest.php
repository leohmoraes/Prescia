<?php

declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../prescia/lib/csrf.php';

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testTokenIsRandomAndStoredInSession(): void
    {
        $token = \presciaCsrfToken();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $_SESSION['prescia_csrf_token']);
        self::assertSame($token, \presciaCsrfToken());
    }

    public function testTokenIsInjectedIntoEveryForm(): void
    {
        $html = '<form method="post"><input name="name"></form><form action="/save"></form>';

        $result = \presciaInjectCsrfFields($html);

        self::assertSame(2, substr_count($result, 'name="csrf_token"'));
        self::assertStringContainsString('value="' . $_SESSION['prescia_csrf_token'] . '"', $result);
    }
}
