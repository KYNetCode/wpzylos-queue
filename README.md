# WPZylos Queue

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![GitHub Issues](https://img.shields.io/github/issues/KYNetCode/wpzylos-queue.svg)](https://github.com/KYNetCode/wpzylos-queue/issues)
[![GitHub Stars](https://img.shields.io/github/stars/KYNetCode/wpzylos-queue.svg)](https://github.com/KYNetCode/wpzylos-queue)

**Lightweight background job queue for WordPress — powered by WPZylos Framework.**

Process time-consuming tasks like sending emails, generating reports, or syncing data in the background using a database-backed queue with WP-Cron integration.

---

## ✨ Features

- 🚀 **Simple Job API** — Extend the abstract `Job` class and implement `handle()`
- 📦 **Database-Backed Queue** — Persistent storage via `queue_jobs` and `queue_failures` tables
- ⏱️ **Delayed Dispatch** — Schedule jobs to run after a specified delay
- 🔄 **Automatic Retry** — Configurable retry attempts with backoff delay
- 💀 **Failure Tracking** — Failed jobs logged with full exception details
- ⏰ **WP-Cron Integration** — Automatic background processing every minute
- 🛡️ **Atomic Job Locking** — Prevents duplicate processing in concurrent environments
- 🔧 **Batch Processing** — Process multiple jobs per cron run with configurable batch size
- 📋 **Failed Job Management** — List, retry, or delete failed jobs
- 🏗️ **Service Provider** — Zero-config container registration

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.0 |
| WordPress | >= 6.0 |
| WPZylos Core | ^1.0 |
| WPZylos Database | ^1.0 |

## 📦 Installation

```bash
composer require KYNetCode/wpzylos-queue
```

## 🚀 Quick Start

### 1. Register the Service Provider

```php
use WPZylos\Framework\Queue\QueueServiceProvider;

// In your plugin bootstrap
$app->register(new QueueServiceProvider());
```

### 2. Create a Job

```php
use WPZylos\Framework\Queue\Job;

class SendWelcomeEmail extends Job
{
    protected int $tries = 3;
    protected int $retryAfter = 120; // 2 minutes
    protected int $timeout = 30;

    public function __construct(private int $userId) {}

    public function handle(): void
    {
        $user = get_userdata($this->userId);
        wp_mail($user->user_email, 'Welcome!', 'Thanks for joining.');
    }

    public function failed(\Throwable $exception): void
    {
        error_log("Failed to send welcome email to user {$this->userId}: {$exception->getMessage()}");
    }
}
```

### 3. Dispatch Jobs

```php
use WPZylos\Framework\Queue\Queue;

$queue = $app->make(Queue::class);

// Dispatch immediately
$queue->push(new SendWelcomeEmail(123));

// Dispatch with 1-hour delay
$queue->later(3600, new SendWelcomeEmail(456));
```

That's it! WP-Cron will automatically process your queued jobs in the background.

---

## 📖 Core Features

### Defining Jobs

Every job extends the `Job` base class and implements the `handle()` method:

```php
use WPZylos\Framework\Queue\Job;

class GenerateReport extends Job
{
    protected int $tries = 5;
    protected int $retryAfter = 300; // 5 minutes
    protected int $timeout = 120;    // 2 minutes max
    protected string $queue = 'reports';

    public function __construct(
        private int $reportId,
        private string $format
    ) {}

    public function handle(): void
    {
        // Generate report...
        $report = ReportBuilder::generate($this->reportId, $this->format);
        
        // Save to uploads
        file_put_contents(
            wp_upload_dir()['basedir'] . "/reports/{$this->reportId}.{$this->format}",
            $report
        );
    }

    public function failed(\Throwable $exception): void
    {
        update_post_meta($this->reportId, '_report_status', 'failed');
    }
}
```

### Queue Operations

```php
$queue = $app->make(Queue::class);

// Push a job
$jobId = $queue->push(new GenerateReport(42, 'pdf'));

// Delayed dispatch (seconds)
$queue->later(1800, new GenerateReport(42, 'csv')); // 30 min delay

// Check queue size
$pending = $queue->size('default'); // Count pending jobs
$reports = $queue->size('reports'); // Count by queue name

// Clear a queue
$queue->clear('default');
```

### Failed Job Management

```php
$queue = $app->make(Queue::class);

// Get all failed jobs
$failures = $queue->failed();

foreach ($failures as $failure) {
    echo "{$failure->queue}: {$failure->exception}\n";
}

// Retry a failed job (re-queues it)
$queue->retryFailed($failureId);

// Delete a specific failed job
$queue->deleteFailed($failureId);

// Clear all failed jobs
$queue->clearFailed();
```

### Manual Worker Processing

```php
use WPZylos\Framework\Queue\Worker;

$worker = $app->make(Worker::class);

// Process a single job
$worker->process('default');

// Process up to 25 jobs
$processed = $worker->run(25, 'default');
echo "Processed {$processed} jobs";
```

### Database Table Installation

```php
use WPZylos\Framework\Queue\QueueTableInstaller;

$installer = $app->make(QueueTableInstaller::class);

// Create tables (runs dbDelta)
$installer->install();

// Drop tables (on uninstall)
$installer->uninstall();
```

### Cron Handler Configuration

```php
use WPZylos\Framework\Queue\QueueCronHandler;

$cron = $app->make(QueueCronHandler::class);

// Change batch size (default: 10)
$cron->setBatchSize(25);

// Manual registration (auto-done by ServiceProvider)
$cron->register();

// Cleanup on plugin deactivation
$cron->unregister();
```

---

## 📦 Related Packages

| Package | Description | Link |
|---------|-------------|------|
| wpzylos-core | Core framework | [GitHub](https://github.com/KYNetCode/wpzylos-core) |
| wpzylos-database | Database connection | [GitHub](https://github.com/KYNetCode/wpzylos-database) |
| wpzylos-scheduler | Task scheduling | [GitHub](https://github.com/KYNetCode/wpzylos-scheduler) |
| wpzylos-mail | Email sending | [GitHub](https://github.com/KYNetCode/wpzylos-mail) |

## 📖 Documentation

Full documentation is available at **[wpzylos.com/docs/latest/packages/wpzylos-queue](https://wpzylos.com/docs/latest/packages/wpzylos-queue)**.

## Support the Project

- [GitHub Sponsors](https://github.com/sponsors/KYNetCode)
- [PayPal Donate](https://www.paypal.com/donate/?hosted_button_id=66U4L3HG4TLCC)

## 📄 License

This package is open-sourced software licensed under the [MIT License](LICENSE).

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute.

---

Made with ❤️ by [KYNetCode](https://github.com/KYNetCode)
