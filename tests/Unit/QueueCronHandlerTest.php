<?php

defined('ABSPATH') || exit;

declare(strict_types=1);

namespace WPZylos\Framework\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPZylos\Framework\Queue\QueueCronHandler;
use WPZylos\Framework\Queue\Worker;
use WPZylos\Framework\Core\Contracts\ContextInterface;

/**
 * Tests for the QueueCronHandler class.
 */
class QueueCronHandlerTest extends TestCase
{
    private QueueCronHandler $handler;
    private ContextInterface $context;
    private Worker $worker;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);
        $this->context->method('cronHook')
            ->with('process_queue')
            ->willReturn('myplugin_process_queue');
        $this->context->method('prefix')
            ->willReturn('myplugin_');
        $this->context->method('textDomain')
            ->willReturn('myplugin');

        $this->worker = $this->createMock(Worker::class);

        $this->handler = new QueueCronHandler($this->context, $this->worker);
    }

    public function testRegisterDoesNotThrow(): void
    {
        // register() calls add_filter, add_action, wp_next_scheduled, wp_schedule_event
        // All mocked in bootstrap — should not throw
        $this->handler->register();
        $this->assertTrue(true);
    }

    public function testUnregisterDoesNotThrow(): void
    {
        $this->handler->unregister();
        $this->assertTrue(true);
    }

    public function testAddCronScheduleAddsEntry(): void
    {
        $schedules = $this->handler->addCronSchedule([]);

        $this->assertArrayHasKey('myplugin_every_minute', $schedules);
        $this->assertSame(60, $schedules['myplugin_every_minute']['interval']);
    }

    public function testAddCronScheduleDoesNotOverrideExisting(): void
    {
        $existing = [
            'myplugin_every_minute' => [
                'interval' => 120,
                'display'  => 'Custom',
            ],
        ];

        $schedules = $this->handler->addCronSchedule($existing);

        // Should keep existing
        $this->assertSame(120, $schedules['myplugin_every_minute']['interval']);
    }

    public function testProcessQueueCallsWorkerRun(): void
    {
        $this->worker->expects($this->once())
            ->method('run')
            ->with(10)
            ->willReturn(5);

        $this->handler->processQueue();
    }

    public function testSetBatchSizeChangesBatchSize(): void
    {
        $this->handler->setBatchSize(25);

        $this->worker->expects($this->once())
            ->method('run')
            ->with(25)
            ->willReturn(3);

        $this->handler->processQueue();
    }

    public function testSetBatchSizeMinimumIsOne(): void
    {
        $this->handler->setBatchSize(0);

        $this->worker->expects($this->once())
            ->method('run')
            ->with(1)
            ->willReturn(0);

        $this->handler->processQueue();
    }
}
