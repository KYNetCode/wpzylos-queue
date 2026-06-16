<?php

declare(strict_types=1);

namespace WPZylos\Framework\Queue;

/**
 * Abstract base class for queueable jobs.
 *
 * Extend this class and implement handle() to define job logic.
 *
 * @package WPZylos\Framework\Queue
 */
abstract class Job
{
    /**
     * Maximum number of attempts before the job is marked as failed.
     */
    protected int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    protected int $retryAfter = 60;

    /**
     * Maximum execution time in seconds.
     */
    protected int $timeout = 60;

    /**
     * The queue name this job should be dispatched to.
     */
    protected string $queue = 'default';

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Handle a job failure.
     *
     * Called when the job has exhausted all retry attempts.
     *
     * @param \Throwable $exception The exception that caused the failure
     *
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // Override in subclass to handle failure
    }

    /**
     * Get the maximum number of attempts.
     *
     * @return int
     */
    public function getTries(): int
    {
        return $this->tries;
    }

    /**
     * Get the retry delay in seconds.
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Get the timeout in seconds.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the queue name.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }
}
