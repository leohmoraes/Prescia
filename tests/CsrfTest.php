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
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
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

    public function testAllMutatingMethodsRequireProtection(): void
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            self::assertTrue(\presciaRequestIsMutating(), $method);
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::assertFalse(\presciaRequestIsMutating());
    }

    /** @dataProvider mutatingMethods */
    public function testMissingTokenIsRejectedForEveryMutatingMethod(string $method): void
    {
        $result = $this->runValidationProbe($method);

        self::assertSame(403, $result['status']);
        self::assertSame('rejected', $result['result']);
    }

    public function testGetWithoutTokenIsNotRejected(): void
    {
        self::assertSame(
            ['status' => 200, 'result' => 'accepted'],
            $this->runValidationProbe('GET')
        );
    }

    public function testEmptyFormFieldAndHeaderAreRejected(): void
    {
        self::assertSame(
            ['status' => 403, 'result' => 'rejected'],
            $this->runValidationProbe('POST', 'field', '')
        );
        self::assertSame(
            ['status' => 403, 'result' => 'rejected'],
            $this->runValidationProbe('POST', 'header', '')
        );
    }

    public function testValidTokenContinuesToBeAcceptedForMutatingRequests(): void
    {
        self::assertSame(
            ['status' => 200, 'result' => 'accepted'],
            $this->runValidationProbe('PUT', 'field', 'valid-token')
        );
        self::assertSame(
            ['status' => 200, 'result' => 'accepted'],
            $this->runValidationProbe('PATCH', 'header', 'valid-token')
        );
    }

    public function testMainValidatesBeforeTheRemainingBootstrapRuns(): void
    {
        $main = (string) file_get_contents(__DIR__ . '/../prescia/lib/main.php');
        $validation = strpos($main, 'presciaValidateCsrf();');
        $nextBootstrapStep = strpos($main, '# Ip handling');

        self::assertIsInt($validation);
        self::assertIsInt($nextBootstrapStep);
        self::assertLessThan($validation, $nextBootstrapStep);
    }

    public function testHeaderTokenIsAcceptedForApiRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $token = \presciaCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        self::assertSame($token, \presciaSubmittedCsrfToken());
        \presciaValidateCsrf();
    }

    public function testTokenRotationInvalidatesPreviousValue(): void
    {
        $oldToken = \presciaCsrfToken();
        $newToken = \presciaRotateCsrfToken();

        self::assertNotSame($oldToken, $newToken);
        self::assertSame($newToken, $_SESSION['prescia_csrf_token']);
    }

    /** @return iterable<string, array{string}> */
    public static function mutatingMethods(): iterable
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            yield $method => [$method];
        }
    }

    /** @return array{status: int, result: string} */
    private function runValidationProbe(string $method, string $source = '', string $token = ''): array
    {
        $script = <<<'PHP'
<?php
require $argv[1];
$_SESSION = ['prescia_csrf_token' => 'valid-token'];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = $argv[2];
if ($argv[3] === 'field') {
    $_POST['csrf_token'] = $argv[4];
} elseif ($argv[3] === 'header') {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $argv[4];
}
register_shutdown_function(static function (): void {
    if (http_response_code() === 403) {
        ob_end_clean();
        echo json_encode(['status' => 403, 'result' => 'rejected']);
    } else {
        ob_end_flush();
    }
});
ob_start();
presciaValidateCsrf();
echo json_encode(['status' => 200, 'result' => 'accepted']);
PHP;
        $temporaryScript = tempnam(sys_get_temp_dir(), 'prescia-csrf-');
        self::assertNotFalse($temporaryScript);
        file_put_contents($temporaryScript, $script);

        $command = sprintf(
            '%s %s %s %s %s %s 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($temporaryScript),
            escapeshellarg(__DIR__ . '/../prescia/lib/csrf.php'),
            escapeshellarg($method),
            escapeshellarg($source),
            escapeshellarg($token)
        );
        $output = shell_exec($command);
        unlink($temporaryScript);

        $decoded = json_decode((string) $output, true);
        self::assertIsArray($decoded, (string) $output);
        self::assertArrayHasKey('status', $decoded);
        self::assertArrayHasKey('result', $decoded);

        return [
            'status' => (int) $decoded['status'],
            'result' => (string) $decoded['result'],
        ];
    }
}
