# WPZylos Queue

**Lightweight background job queue for WordPress — powered by WPZylos Framework.**

WPZylos Queue provides a simple, database-backed job queue that lets you defer time-consuming tasks to background processing via WP-Cron. No external services, no Redis, no extra infrastructure — just your WordPress database.

## Quick Start

```php
use WPZylos\Framework\Queue\Job;
use WPZylos\Framework\Queue\Queue;
use WPZylos\Framework\Queue\QueueServiceProvider;

// 1. Register the service provider
$app->register(new QueueServiceProvider());

// 2. Define a job
class SendWelcomeEmail extends Job
{
    protected int $tries = 3;

    public function __construct(private int $userId) {}

    public function handle(): void
    {
        $user = get_userdata($this->userId);
        wp_mail($user->user_email, 'Welcome!', 'Thanks for joining.');
    }
}

// 3. Dispatch it
$queue = $app->make(Queue::class);
$queue->push(new SendWelcomeEmail(123));
```

## What You Get

| Feature | Description |
|---------|-------------|
| **Job Base Class** | Abstract class with retry, timeout, and queue config |
| **Queue Dispatcher** | Push jobs immediately or with a delay |
| **Worker Processor** | Process jobs with retry logic and failure tracking |
| **Database Tables** | Auto-created via `dbDelta` |
| **WP-Cron Integration** | Automatic background processing |
| **Failed Job Management** | List, retry, or delete failed jobs |

## Documentation

- [Overview](overview.md) — Architecture and design
- [Installation](installation.md) — Setup guide
- [Usage](usage.md) — Detailed usage guide
- [Configuration](configuration.md) — Configuration options
- [API Reference](api-reference.md) — All classes and methods
- [Examples](examples.md) — Real-world examples
- [Testing](testing.md) — Testing with this package
- [Security](security.md) — Security considerations
- [Troubleshooting](troubleshooting.md) — Common issues
