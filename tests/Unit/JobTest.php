<?php

defined('ABSPATH') || exit;

declare(strict_types=1);

namespace WPZylos\Framework\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPZylos\Framework\Queue\Job;

/**
 * Tests for the abstract Job base class.
 */
class JobTest extends TestCase
{
    private Job $job;

    protected function setUp(): void
    {
        $this->job = new class () extends Job {
            protected int $tries = 5;
            protected int $retryAfter = 120;
            protected int $timeout = 30;
            protected string $queue = 'emails';

            public bool $handled = false;
            public ?\Throwable $failedException = null;

            public function handle(): void
            {
                $this->handled = true;
            }

            public function failed(\Throwable $exception): void
            {
                $this->failedException = $exception;
            }
        };
    }

    public function testGetTriesReturnsConfiguredValue(): void
    {
        $this->assertSame(5, $this->job->getTries());
    }

    public function testGetRetryAfterReturnsConfiguredValue(): void
    {
        $this->assertSame(120, $this->job->getRetryAfter());
    }

    public function testGetTimeoutReturnsConfiguredValue(): void
    {
        $this->assertSame(30, $this->job->getTimeout());
    }

    public function testGetQueueReturnsConfiguredValue(): void
    {
        $this->assertSame('emails', $this->job->getQueue());
    }

    public function testHandleExecutesJobLogic(): void
    {
        $this->job->handle();
        $this->assertTrue($this->job->handled);
    }

    public function testFailedReceivesException(): void
    {
        $exception = new \RuntimeException('Something went wrong');
        $this->job->failed($exception);
        $this->assertSame($exception, $this->job->failedException);
    }

    public function testDefaultValuesForBaseJob(): void
    {
        $defaultJob = new class () extends Job {
            public function handle(): void
            {
                // no-op
            }
        };

        $this->assertSame(3, $defaultJob->getTries());
        $this->assertSame(60, $defaultJob->getRetryAfter());
        $this->assertSame(60, $defaultJob->getTimeout());
        $this->assertSame('default', $defaultJob->getQueue());
    }

    public function testFailedMethodDefaultIsNoOp(): void
    {
        $defaultJob = new class () extends Job {
            public function handle(): void
            {
                // no-op
            }
        };

        // Should not throw
        $defaultJob->failed(new \RuntimeException('test'));
        $this->assertTrue(true);
    }

    public function testJobIsSerializable(): void
    {
        $serialized = serialize($this->job);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Job::class, $unserialized);
        $this->assertSame(5, $unserialized->getTries());
        $this->assertSame('emails', $unserialized->getQueue());
    }
}
