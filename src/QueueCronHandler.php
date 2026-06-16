<?php

declare(strict_types=1);

namespace WPZylos\Framework\Queue;

use WPZylos\Framework\Core\Contracts\ContextInterface;

/**
 * WP-Cron handler for processing queued jobs.
 *
 * Registers a recurring cron event that triggers job processing.
 *
 * @package WPZylos\Framework\Queue
 */
class QueueCronHandler
{
    private ContextInterface $context;
    private Worker $worker;

    /**
     * Number of jobs to process per cron run.
     */
    private int $batchSize = 10;

    /**
     * Create cron handler.
     *
     * @param ContextInterface $context Plugin context
     * @param Worker $worker Queue worker
     */
    public function __construct(ContextInterface $context, Worker $worker)
    {
        $this->context = $context;
        $this->worker = $worker;
    }

    /**
     * Register the cron event and hooks.
     *
     * @return void
     */
    public function register(): void
    {
        $hookName = $this->getHookName();

        // Register custom cron schedule (every minute)
        add_filter('cron_schedules', [$this, 'addCronSchedule']);

        // Register the cron action
        add_action($hookName, [$this, 'processQueue']);

        // Schedule the event if not already scheduled
        if (!wp_next_scheduled($hookName)) {
            wp_schedule_event(time(), $this->getScheduleName(), $hookName);
        }
    }

    /**
     * Unregister the cron event.
     *
     * @return void
     */
    public function unregister(): void
    {
        $hookName = $this->getHookName();
        $timestamp = wp_next_scheduled($hookName);

        if ($timestamp) {
            wp_unschedule_event($timestamp, $hookName);
        }
    }

    /**
     * Add custom cron schedule (every minute).
     *
     * @param array $schedules Existing schedules
     *
     * @return array Modified schedules
     */
    public function addCronSchedule(array $schedules): array
    {
        $scheduleName = $this->getScheduleName();

        if (!isset($schedules[$scheduleName])) {
            $schedules[$scheduleName] = [
                'interval' => 60,
                'display' => __('Every Minute', $this->context->textDomain()),
            ];
        }

        return $schedules;
    }

    /**
     * Process the queue (called by WP-Cron).
     *
     * @return void
     */
    public function processQueue(): void
    {
        $this->worker->run($this->batchSize);
    }

    /**
     * Set the batch size for cron processing.
     *
     * @param int $size Number of jobs per batch
     *
     * @return void
     */
    public function setBatchSize(int $size): void
    {
        $this->batchSize = max(1, $size);
    }

    /**
     * Get the prefixed cron hook name.
     *
     * @return string
     */
    private function getHookName(): string
    {
        return $this->context->cronHook('process_queue');
    }

    /**
     * Get the custom cron schedule name.
     *
     * @return string
     */
    private function getScheduleName(): string
    {
        return $this->context->prefix() . 'every_minute';
    }
}
