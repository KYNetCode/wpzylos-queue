# Overview

## Architecture

WPZylos Queue follows a producer-consumer pattern backed by WordPress database tables:

```
┌──────────┐     push()     ┌─────────────┐     process()     ┌────────────┐
│ Your Code│ ──────────────►│ queue_jobs   │ ◄────────────────►│   Worker   │
│          │     later()    │   (table)    │                   │            │
└──────────┘                └─────────────┘                   └────────────┘
                                   │                                │
                                   │            on failure          │
                                   │                                ▼
                                   │                         ┌─────────────┐
                                   └────────────────────────►│queue_failures│
                                                             │   (table)   │
                                                             └─────────────┘
```

## Design Patterns

### Serialization-Based Jobs

Jobs are PHP objects that are serialized and stored in the database. When the worker picks up a job, it unserializes the object and calls `handle()`. This means:

- Jobs must be serializable (no closures, no resources)
- Constructor arguments are preserved across serialization
- The job class must exist at execution time

### Atomic Reservation

The worker uses an atomic `UPDATE ... WHERE reserved_at IS NULL` query to claim jobs, preventing duplicate processing when multiple WP-Cron requests run concurrently.

### Retry with Backoff

Failed jobs are released back to the queue with a configurable delay (`retryAfter`). After exhausting all retry attempts (`tries`), the job is moved to the failures table with the full exception trace.

## How It Works

1. **Dispatch**: Your code calls `$queue->push($job)` or `$queue->later($delay, $job)`.
2. **Storage**: The job object is serialized and inserted into the `queue_jobs` table.
3. **Scheduling**: WP-Cron fires every minute via `QueueCronHandler`.
4. **Processing**: The `Worker` picks up available jobs, deserializes them, and calls `handle()`.
5. **Success**: Job is deleted from the `queue_jobs` table.
6. **Failure**: Job is retried (if attempts remain) or logged to `queue_failures`.

## Component Roles

| Component | Role |
|-----------|------|
| `Job` | Abstract base class — defines job logic, retries, timeouts |
| `Queue` | Dispatcher — inserts jobs into the database |
| `Worker` | Consumer — fetches, executes, retries, and cleans up jobs |
| `QueueTableInstaller` | DDL — creates/drops database tables |
| `QueueCronHandler` | Scheduler — hooks into WP-Cron for background execution |
| `QueueServiceProvider` | DI — registers all services in the container |

## Database Schema

### `{prefix}_queue_jobs`

| Column | Type | Description |
|--------|------|-------------|
| `id` | `BIGINT UNSIGNED` | Auto-increment primary key |
| `queue` | `VARCHAR(255)` | Queue name (default: `'default'`) |
| `payload` | `LONGTEXT` | Serialized job object |
| `attempts` | `TINYINT UNSIGNED` | Number of execution attempts |
| `reserved_at` | `DATETIME NULL` | When a worker claimed the job |
| `available_at` | `DATETIME` | Earliest time the job can be picked up |
| `created_at` | `DATETIME` | When the job was dispatched |

### `{prefix}_queue_failures`

| Column | Type | Description |
|--------|------|-------------|
| `id` | `BIGINT UNSIGNED` | Auto-increment primary key |
| `queue` | `VARCHAR(255)` | Queue name |
| `payload` | `LONGTEXT` | Serialized job object |
| `exception` | `LONGTEXT` | Exception class, message, and trace |
| `failed_at` | `DATETIME` | When the job failed permanently |
