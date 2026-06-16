<?php

declare(strict_types=1);

namespace WPZylos\Framework\Queue;

use WPZylos\Framework\Core\Contracts\ApplicationInterface;
use WPZylos\Framework\Core\Contracts\ServiceProviderInterface;
use WPZylos\Framework\Database\Connection;

/**
 * Queue service provider.
 *
 * Registers Queue, Worker, CronHandler and table installer in the container.
 *
 * @package WPZylos\Framework\Queue
 */
class QueueServiceProvider implements ServiceProviderInterface
{
    /**
     * Register queue services into the container.
     *
     * @param ApplicationInterface $app Application instance
     *
     * @return void
     */
    public function register(ApplicationInterface $app): void
    {
        $app->singleton(Queue::class, function () use ($app) {
            return new Queue(
                $app->make(Connection::class),
                $app->context()
            );
        });

        $app->singleton(Worker::class, function () use ($app) {
            return new Worker(
                $app->make(Connection::class),
                $app->context(),
                $app->make(Queue::class)
            );
        });

        $app->singleton(QueueCronHandler::class, function () use ($app) {
            return new QueueCronHandler(
                $app->context(),
                $app->make(Worker::class)
            );
        });

        $app->singleton(QueueTableInstaller::class, function () use ($app) {
            return new QueueTableInstaller(
                $app->make(Connection::class),
                $app->context()
            );
        });

        // Convenience aliases
        $app->singleton('queue', fn() => $app->make(Queue::class));
        $app->singleton('queue.worker', fn() => $app->make(Worker::class));
    }

    /**
     * Boot queue services.
     *
     * @param ApplicationInterface $app Application instance
     *
     * @return void
     */
    public function boot(ApplicationInterface $app): void
    {
        // Register cron handler for background processing
        $cronHandler = $app->make(QueueCronHandler::class);
        $cronHandler->register();
    }
}
