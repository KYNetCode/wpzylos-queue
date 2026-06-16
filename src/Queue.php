<?php

declare(strict_types=1);

namespace WPZylos\Framework\Queue;

use WPZylos\Framework\Core\Contracts\ContextInterface;
use WPZylos\Framework\Database\Connection;

/**
 * Database-backed job queue.
 *
 * Pushes jobs to a database table for async processing via WP-Cron.
 *
 * @package WPZylos\Framework\Queue
 */
class Queue
{
    private Connection $db;
    private ContextInterface $context;

    /**
     * Create queue instance.
     *
     * @param Connection $db Database connection
     * @param ContextInterface $context Plugin context
     */
    public function __construct(Connection $db, ContextInterface $context)
    {
        $this->db = $db;
        $this->context = $context;
    }

    /**
     * Push a job onto the queue.
     *
     * @param Job $job The job to dispatch
     *
     * @return int|false The inserted job ID or false on failure
     */
    public function push(Job $job): int|false
    {
        return $this->pushToDatabase($job, 0);
    }

    /**
     * Push a job onto the queue with a delay.
     *
     * @param int $delay Delay in seconds
     * @param Job $job The job to dispatch
     *
     * @return int|false The inserted job ID or false on failure
     */
    public function later(int $delay, Job $job): int|false
    {
        return $this->pushToDatabase($job, $delay);
    }

    /**
     * Get the number of pending jobs.
     *
     * @param string $queue Queue name
     *
     * @return int
     */
    public function size(string $queue = 'default'): int
    {
        $table = $this->getJobsTable();
        $now = current_time('mysql', true);

        $result = $this->db->getVar(
            "SELECT COUNT(*) FROM `{$table}` WHERE `queue` = %s AND `reserved_at` IS NULL AND `available_at` <= %s",
            $queue,
            $now
        );

        return (int) ($result ?? 0);
    }

    /**
     * Clear all jobs from a queue.
     *
     * @param string $queue Queue name
     *
     * @return int|false Number of rows deleted or false
     */
    public function clear(string $queue = 'default'): int|false
    {
        $table = $this->getJobsTable();

        return $this->db->query(
            "DELETE FROM `{$table}` WHERE `queue` = %s",
            $queue
        );
    }

    /**
     * Clear all failed jobs.
     *
     * @return int|false
     */
    public function clearFailed(): int|false
    {
        $table = $this->getFailedTable();

        return $this->db->query("TRUNCATE TABLE `{$table}`");
    }

    /**
     * Get all failed jobs.
     *
     * @return array<object>
     */
    public function failed(): array
    {
        $table = $this->getFailedTable();

        return $this->db->getResults("SELECT * FROM `{$table}` ORDER BY `failed_at` DESC");
    }

    /**
     * Delete a specific failed job.
     *
     * @param int $id Failed job ID
     *
     * @return int|false
     */
    public function deleteFailed(int $id): int|false
    {
        return $this->db->delete($this->getFailedTable(), ['id' => $id]);
    }

    /**
     * Retry a failed job by re-queuing it.
     *
     * @param int $id Failed job ID
     *
     * @return int|false New job ID or false
     */
    public function retryFailed(int $id): int|false
    {
        $table = $this->getFailedTable();
        $failed = $this->db->getRow(
            "SELECT * FROM `{$table}` WHERE `id` = %d",
            $id
        );

        if (!$failed) {
            return false;
        }

        $jobsTable = $this->getJobsTable();
        $now = current_time('mysql', true);

        $result = $this->db->insert($jobsTable, [
            'queue' => $failed->queue,
            'payload' => $failed->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $now,
            'created_at' => $now,
        ]);

        if ($result !== false) {
            $this->deleteFailed($id);
        }

        return $result;
    }

    /**
     * Push job data to the database.
     *
     * @param Job $job Job instance
     * @param int $delay Delay in seconds
     *
     * @return int|false
     */
    private function pushToDatabase(Job $job, int $delay): int|false
    {
        $table = $this->getJobsTable();
        $now = current_time('mysql', true);
        $availableAt = $delay > 0
            ? gmdate('Y-m-d H:i:s', time() + $delay)
            : $now;

        return $this->db->insert($table, [
            'queue' => $job->getQueue(),
            'payload' => serialize($job),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $now,
        ]);
    }

    /**
     * Get the jobs table name.
     *
     * @return string
     */
    public function getJobsTable(): string
    {
        return $this->context->tableName('queue_jobs');
    }

    /**
     * Get the failed jobs table name.
     *
     * @return string
     */
    public function getFailedTable(): string
    {
        return $this->context->tableName('queue_failures');
    }
}
