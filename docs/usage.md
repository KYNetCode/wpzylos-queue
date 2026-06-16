# Usage

## Creating Jobs

Every job must extend `WPZylos\Framework\Queue\Job` and implement the `handle()` method:

```php
use WPZylos\Framework\Queue\Job;

class ProcessPayment extends Job
{
    protected int $tries = 3;
    protected int $retryAfter = 300;  // 5 minutes
    protected int $timeout = 60;      // 1 minute
    protected string $queue = 'payments';

    public function __construct(
        private int $orderId,
        private float $amount
    ) {}

    public function handle(): void
    {
        $order = wc_get_order($this->orderId);
        $gateway = $order->get_payment_method();
        
        // Process payment...
        $result = PaymentGateway::charge($gateway, $this->amount);
        
        if ($result->success) {
            $order->update_status('completed');
        }
    }

    public function failed(\Throwable $exception): void
    {
        $order = wc_get_order($this->orderId);
        $order->update_status('failed');
        error_log("Payment failed for order {$this->orderId}: {$exception->getMessage()}");
    }
}
```

### Job Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$tries` | `int` | `3` | Maximum retry attempts |
| `$retryAfter` | `int` | `60` | Seconds to wait before retrying |
| `$timeout` | `int` | `60` | Max execution time in seconds |
| `$queue` | `string` | `'default'` | Queue name for routing |

## Dispatching Jobs

### Immediate Dispatch

```php
use WPZylos\Framework\Queue\Queue;

$queue = $app->make(Queue::class);
$jobId = $queue->push(new ProcessPayment(42, 99.99));
```

### Delayed Dispatch

```php
// Process after 30 minutes
$queue->later(1800, new ProcessPayment(42, 99.99));

// Process after 1 hour
$queue->later(3600, new SendInvoiceEmail(42));
```

### Using the Alias

```php
$queue = $app->make('queue');
$queue->push(new SendWelcomeEmail(123));
```

## Queue Management

### Check Queue Size

```php
// Count all pending jobs on 'default' queue
$pending = $queue->size();

// Count on a specific queue
$paymentJobs = $queue->size('payments');
```

### Clear a Queue

```php
// Remove all jobs from 'default' queue
$queue->clear();

// Remove all jobs from a specific queue
$queue->clear('payments');
```

## Failed Job Management

### List Failed Jobs

```php
$failures = $queue->failed();

foreach ($failures as $failure) {
    echo "ID: {$failure->id}\n";
    echo "Queue: {$failure->queue}\n";
    echo "Failed At: {$failure->failed_at}\n";
    echo "Exception: {$failure->exception}\n\n";
}
```

### Retry a Failed Job

```php
// Re-queues the failed job with fresh attempts
$newJobId = $queue->retryFailed($failureId);
```

### Delete a Failed Job

```php
$queue->deleteFailed($failureId);
```

### Clear All Failed Jobs

```php
$queue->clearFailed();
```

## Manual Processing

Use the `Worker` to process jobs manually (useful for CLI or testing):

```php
use WPZylos\Framework\Queue\Worker;

$worker = $app->make(Worker::class);

// Process a single job
$wasProcessed = $worker->process('default');

// Process up to 50 jobs from 'payments' queue
$count = $worker->run(50, 'payments');
echo "Processed {$count} jobs";
```

## Named Queues

Use named queues to prioritize different job types:

```php
class UrgentNotification extends Job
{
    protected string $queue = 'urgent';
    // ...
}

class WeeklyDigest extends Job
{
    protected string $queue = 'low';
    // ...
}

// Process urgent jobs first
$worker->run(20, 'urgent');
$worker->run(5, 'low');
```

## WP-Cron Integration

The `QueueCronHandler` is automatically booted by the service provider. It:

1. Registers a custom "every minute" cron schedule
2. Hooks into the cron event to call `Worker::run()`
3. Processes 10 jobs per batch by default

### Customize Batch Size

```php
$cron = $app->make(QueueCronHandler::class);
$cron->setBatchSize(25);
```

### Disable Cron Processing

If you want to process jobs manually (e.g., via WP-CLI), skip the service provider's `boot()` and manage the worker yourself.
