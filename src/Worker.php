<?php

declare(strict_types=1);

namespace WPZylos\Framework\Queue;

use WPZylos\Framework\Core\Contracts\ContextInterface;
use WPZylos\Framework\Database\Connection;

/**
 * Queue worker — processes pending jobs from the database.
 *
 * @package WPZylos\Framework\Queue
 */
class Worker
{
    private Connection $db;
    private ContextInterface $context;
    private Queue $queue;

    /**
     * Create worker instance.
     *
     * @param Connection $db Database connection
     * @param ContextInterface $context Plugin context
     * @param Queue $queue Queue instance
     */
    public function __construct(Connection $db, ContextInterface $context, Queue $queue)
    {
        $this->db = $db;
        $this->context = $context;
        $this->queue = $queue;
    }

    /**
     * Process the next available job.
     *
     * @param string $queueName Queue to process
     *
     * @return bool True if a job was processed, false if queue is empty
     */
    public function process(string $queueName = 'default'): bool
    {
        $job = $this->getNextJob($queueName);

        if (!$job) {
            return false;
        }

        $this->executeJob($job);

        return true;
    }

    /**
     * Process up to N jobs from the queue.
     *
     * @param int $maxJobs Maximum number of jobs to process
     * @param string $queueName Queue to process
     *
     * @return int Number of jobs processed
     */
    public function run(int $maxJobs = 10, string $queueName = 'default'): int
    {
        $processed = 0;

        for ($i = 0; $i < $maxJobs; $i++) {
            if (!$this->process($queueName)) {
                break;
            }
            $processed++;
        }

        return $processed;
    }

    /**
     * Get and lock the next available job.
     *
     * @param string $queueName Queue name
     *
     * @return object|null Job row or null
     */
    private function getNextJob(string $queueName): ?object
    {
        $table = $this->queue->getJobsTable();
        $now = current_time('mysql', true);

        // Select next available job (not reserved, available now)
        $job = $this->db->getRow(
            "SELECT * FROM `{$table}` WHERE `queue` = %s AND `reserved_at` IS NULL AND `available_at` <= %s ORDER BY `id` ASC LIMIT 1",
            $queueName,
            $now
        );

        if (!$job) {
            return null;
        }

        // Reserve the job (atomic lock)
        $affected = $this->db->query(
            "UPDATE `{$table}` SET `reserved_at` = %s, `attempts` = `attempts` + 1 WHERE `id` = %d AND `reserved_at` IS NULL",
            $now,
            $job->id
        );

        // If another worker already reserved it, skip
        if (!$affected) {
            return null;
        }

        $job->reserved_at = $now;
        $job->attempts = (int) $job->attempts + 1;

        return $job;
    }

    /**
     * Execute a job row.
     *
     * @param object $jobRow Database row
     *
     * @return void
     */
    private function executeJob(object $jobRow): void
    {
        $table = $this->queue->getJobsTable();

        try {
            /** @var Job $job */
            $job = @unserialize($jobRow->payload, ['allowed_classes' => true]);

            if (!$job instanceof Job) {
                $this->logFailure($jobRow, new \RuntimeException('Failed to unserialize job payload'));
                $this->deleteJob($jobRow->id);
                return;
            }

            // Set timeout if available
            $timeout = $job->getTimeout();
            if ($timeout > 0 && function_exists('set_time_limit')) {
                @set_time_limit($timeout);
            }

            $job->handle();

            // Job succeeded — remove from queue
            $this->deleteJob($jobRow->id);
        } catch (\Throwable $e) {
            $this->handleFailedJob($jobRow, $job ?? null, $e);
        }
    }

    /**
     * Handle a failed job — retry or log failure.
     *
     * @param object $jobRow Database row
     * @param Job|null $job Deserialized job (may be null)
     * @param \Throwable $exception The exception
     *
     * @return void
     */
    private function handleFailedJob(object $jobRow, ?Job $job, \Throwable $exception): void
    {
        $maxTries = $job ? $job->getTries() : 3;

        if ((int) $jobRow->attempts < $maxTries) {
            // Release back to queue with delay
            $retryAfter = $job ? $job->getRetryAfter() : 60;
            $availableAt = gmdate('Y-m-d H:i:s', time() + $retryAfter);

            $this->db->update($this->queue->getJobsTable(), [
                'reserved_at' => null,
                'available_at' => $availableAt,
            ], ['id' => (int) $jobRow->id]);
        } else {
            // Max attempts reached — log failure
            if ($job) {
                try {
                    $job->failed($exception);
                } catch (\Throwable $e) {
                    // Ignore errors in failure handler
                }
            }

            $this->logFailure($jobRow, $exception);
            $this->deleteJob($jobRow->id);
        }
    }

    /**
     * Log a failed job to the failures table.
     *
     * @param object $jobRow Database row
     * @param \Throwable $exception The exception
     *
     * @return void
     */
    private function logFailure(object $jobRow, \Throwable $exception): void
    {
        $table = $this->queue->getFailedTable();

        $this->db->insert($table, [
            'queue' => $jobRow->queue,
            'payload' => $jobRow->payload,
            'exception' => mb_substr(
                $exception::class . ': ' . $exception->getMessage() . "\n" . $exception->getTraceAsString(),
                0,
                65535
            ),
            'failed_at' => current_time('mysql', true),
        ]);
    }

    /**
     * Delete a job from the queue.
     *
     * @param int|string $id Job ID
     *
     * @return void
     */
    private function deleteJob(int|string $id): void
    {
        $this->db->delete($this->queue->getJobsTable(), ['id' => (int) $id]);
    }
}
