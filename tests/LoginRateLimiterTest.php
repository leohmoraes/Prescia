<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;
use PresciaLoginRateLimiter;

final class LoginRateLimiterTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/prescia-login-limiter-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->directory);
    }

    public function testBlocksAfterConfiguredFailuresAndUsesBackoff(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 3, 900);

        self::assertTrue($limiter->isAllowed('Admin', '203.0.113.10', 1000));
        $limiter->recordFailure('Admin', '203.0.113.10', 1000);
        $limiter->recordFailure('Admin', '203.0.113.10', 1001);
        self::assertTrue($limiter->isAllowed('Admin', '203.0.113.10', 1002));
        $limiter->recordFailure('Admin', '203.0.113.10', 1002);

        self::assertFalse($limiter->isAllowed('admin', '203.0.113.10', 1003));
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.10', 1062));
    }

    public function testFailuresExpireOutsideTheWindow(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 2, 60);

        $limiter->recordFailure('admin', '203.0.113.10', 1000);
        $limiter->recordFailure('admin', '203.0.113.10', 1061);
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.10', 1062));
    }

    public function testSuccessfulLoginCanClearTheKey(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 1, 900);

        $limiter->recordFailure('admin', '203.0.113.10', 1000);
        self::assertFalse($limiter->isAllowed('admin', '203.0.113.10', 1001));
        $limiter->clear('admin', '203.0.113.10');
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.10', 1001));
    }

    public function testAccountAndIpAreBothPartOfTheIsolationKey(): void
    {
        $limiter = new PresciaLoginRateLimiter($this->directory, 1, 900);

        $limiter->recordFailure('admin', '203.0.113.10', 1000);
        self::assertTrue($limiter->isAllowed('other', '203.0.113.10', 1001));
        self::assertTrue($limiter->isAllowed('admin', '203.0.113.11', 1001));
    }
}
