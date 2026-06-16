<?php

declare(strict_types=1);

namespace WPZylos\Framework\Queue;

use WPZylos\Framework\Core\Contracts\ContextInterface;
use WPZylos\Framework\Database\Connection;

/**
 * Creates the queue database tables using dbDelta.
 *
 * @package WPZylos\Framework\Queue
 */
class QueueTableInstaller
{
    private Connection $db;
    private ContextInterface $context;

    /**
     * Create installer.
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
     * Create or update queue tables.
     *
     * @return void
     */
    public function install(): void
    {
        $this->createJobsTable();
        $this->createFailuresTable();
    }

    /**
     * Drop queue tables.
     *
     * @return void
     */
    public function uninstall(): void
    {
        $jobsTable = $this->context->tableName('queue_jobs');
        $failuresTable = $this->context->tableName('queue_failures');

        $this->db->query("DROP TABLE IF EXISTS `{$jobsTable}`");
        $this->db->query("DROP TABLE IF EXISTS `{$failuresTable}`");
    }

    /**
     * Create the jobs table.
     *
     * @return void
     */
    private function createJobsTable(): void
    {
        global $wpdb;

        $table = $this->context->tableName('queue_jobs');
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            queue varchar(255) NOT NULL DEFAULT 'default',
            payload longtext NOT NULL,
            attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
            reserved_at datetime DEFAULT NULL,
            available_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY queue_available (queue, available_at),
            KEY reserved_at (reserved_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Create the failures table.
     *
     * @return void
     */
    private function createFailuresTable(): void
    {
        global $wpdb;

        $table = $this->context->tableName('queue_failures');
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            queue varchar(255) NOT NULL,
            payload longtext NOT NULL,
            exception longtext NOT NULL,
            failed_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY queue (queue)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
