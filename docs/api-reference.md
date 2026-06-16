# API Reference

## Job (Abstract Class)

**Namespace:** `WPZylos\Framework\Queue\Job`

Abstract base class for all queueable jobs.

### Properties

| Property | Type | Default | Visibility | Description |
|----------|------|---------|------------|-------------|
| `$tries` | `int` | `3` | `protected` | Maximum retry attempts |
| `$retryAfter` | `int` | `60` | `protected` | Retry delay in seconds |
| `$timeout` | `int` | `60` | `protected` | Max execution time in seconds |
| `$queue` | `string` | `'default'` | `protected` | Queue name |

### Methods

#### `handle(): void` (abstract)

Execute the job logic. Must be implemented by subclasses.

#### `failed(\Throwable $exception): void`

Called when the job has exhausted all retry attempts. Override to add custom failure handling.

**Parameters:**
- `$exception` — The exception that caused the final failure

#### `getTries(): int`

Returns the maximum number of attempts.

#### `getRetryAfter(): int`

Returns the retry delay in seconds.

#### `getTimeout(): int`

Returns the timeout in seconds.

#### `getQueue(): string`

Returns the queue name.

---

## Queue

**Namespace:** `WPZylos\Framework\Queue\Queue`

Database-backed job queue dispatcher.

### Constructor

```php
public function __construct(Connection $db, ContextInterface $context)
```

### Methods

#### `push(Job $job): int|false`

Push a job onto the queue for immediate processing.

**Returns:** Inserted job ID on success, `false` on failure.

```php
$id = $queue->push(new SendWelcomeEmail(123));
```

#### `later(int $delay, Job $job): int|false`

Push a job onto the queue with a delay.

**Parameters:**
- `$delay` — Delay in seconds before the job becomes available

**Returns:** Inserted job ID on success, `false` on failure.

```php
$id = $queue->later(3600, new SendWelcomeEmail(123));
```

#### `size(string $queue = 'default'): int`

Get the number of pending (available, unreserved) jobs on a queue.

```php
$count = $queue->size('emails');
```

#### `clear(string $queue = 'default'): int|false`

Delete all jobs from a queue.

**Returns:** Number of deleted rows or `false`.

#### `failed(): array`

Get all failed jobs ordered by most recent.

**Returns:** Array of `object` rows from the failures table.

#### `clearFailed(): int|false`

Truncate the failures table.

#### `deleteFailed(int $id): int|false`

Delete a specific failed job by ID.

#### `retryFailed(int $id): int|false`

Re-queue a failed job with fresh attempts. Deletes the failure record on success.

**Returns:** New job ID or `false`.

#### `getJobsTable(): string`

Get the fully-prefixed jobs table name.

#### `getFailedTable(): string`

Get the fully-prefixed failures table name.

---

## Worker

**Namespace:** `WPZylos\Framework\Queue\Worker`

Processes pending jobs from the database.

### Constructor

```php
public function __construct(Connection $db, ContextInterface $context, Queue $queue)
```

### Methods

#### `process(string $queueName = 'default'): bool`

Process the next available job from a queue.

**Returns:** `true` if a job was processed, `false` if queue is empty.

```php
$wasProcessed = $worker->process('default');
```

#### `run(int $maxJobs = 10, string $queueName = 'default'): int`

Process up to N jobs from a queue.

**Returns:** Number of jobs actually processed.

```php
$processed = $worker->run(25, 'payments');
```

---

## QueueTableInstaller

**Namespace:** `WPZylos\Framework\Queue\QueueTableInstaller`

Creates and drops the queue database tables.

### Constructor

```php
public function __construct(Connection $db, ContextInterface $context)
```

### Methods

#### `install(): void`

Create or update the `queue_jobs` and `queue_failures` tables using `dbDelta`.

#### `uninstall(): void`

Drop both queue tables.

---

## QueueCronHandler

**Namespace:** `WPZylos\Framework\Queue\QueueCronHandler`

WP-Cron integration for automatic background queue processing.

### Constructor

```php
public function __construct(ContextInterface $context, Worker $worker)
```

### Methods

#### `register(): void`

Register the cron schedule and hook. Schedules the event if not already scheduled.

#### `unregister(): void`

Unschedule the cron event.

#### `addCronSchedule(array $schedules): array`

Filter callback for `cron_schedules`. Adds a custom "every minute" interval.

#### `processQueue(): void`

Process the queue (called by WP-Cron). Delegates to `Worker::run()`.

#### `setBatchSize(int $size): void`

Set the number of jobs to process per cron run. Minimum value is 1.

---

## QueueServiceProvider

**Namespace:** `WPZylos\Framework\Queue\QueueServiceProvider`

Registers all queue services in the application container.

### Methods

#### `register(ApplicationInterface $app): void`

Registers singletons for:
- `Queue::class`
- `Worker::class`
- `QueueCronHandler::class`
- `QueueTableInstaller::class`
- `'queue'` alias → `Queue::class`
- `'queue.worker'` alias → `Worker::class`

#### `boot(ApplicationInterface $app): void`

Boots the `QueueCronHandler` to register the WP-Cron event.
