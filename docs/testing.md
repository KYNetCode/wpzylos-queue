# Testing

## Testing Your Jobs

### Unit Testing a Job

Test your job's `handle()` logic in isolation:

```php
use PHPUnit\Framework\TestCase;

class SendWelcomeEmailTest extends TestCase
{
    public function testHandleSendsEmail(): void
    {
        $job = new SendWelcomeEmail(123);
        
        // handle() calls wp_mail() which is mocked in bootstrap
        $job->handle();
        
        // Assert side effects (e.g., via a spy or mock)
        $this->assertTrue(true);
    }

    public function testJobProperties(): void
    {
        $job = new SendWelcomeEmail(123);
        
        $this->assertSame(3, $job->getTries());
        $this->assertSame('default', $job->getQueue());
    }

    public function testJobIsSerialized(): void
    {
        $job = new SendWelcomeEmail(123);
        $restored = unserialize(serialize($job));
        
        $this->assertInstanceOf(SendWelcomeEmail::class, $restored);
    }
}
```

### Testing Queue Dispatch

Mock the `Queue` class to verify jobs are dispatched:

```php
use PHPUnit\Framework\TestCase;
use WPZylos\Framework\Queue\Queue;

class OrderServiceTest extends TestCase
{
    public function testOrderCreationQueuesEmail(): void
    {
        $queue = $this->createMock(Queue::class);
        
        $queue->expects($this->once())
            ->method('push')
            ->with($this->isInstanceOf(SendWelcomeEmail::class))
            ->willReturn(1);

        $service = new OrderService($queue);
        $service->createOrder(['user_id' => 123]);
    }
}
```

### Testing the Worker

Mock the database to test worker behavior:

```php
use PHPUnit\Framework\TestCase;
use WPZylos\Framework\Queue\Worker;
use WPZylos\Framework\Queue\Queue;
use WPZylos\Framework\Database\Connection;
use WPZylos\Framework\Core\Contracts\ContextInterface;

class WorkerTest extends TestCase
{
    public function testProcessReturnsFalseOnEmptyQueue(): void
    {
        $db = $this->createMock(Connection::class);
        $context = $this->createMock(ContextInterface::class);
        $queue = $this->createMock(Queue::class);

        $queue->method('getJobsTable')->willReturn('wp_queue_jobs');
        
        $db->method('getRow')->willReturn(null);

        $worker = new Worker($db, $context, $queue);
        
        $this->assertFalse($worker->process());
    }
}
```

## Running the Test Suite

```bash
# Run all tests
composer test

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run a specific test
vendor/bin/phpunit --filter JobTest

# Run static analysis
composer analyze

# Run all quality checks
composer qa
```

## Mocking WordPress Functions

The `tests/bootstrap.php` file provides mocks for all WordPress functions used by this package:

| Function | Mock Behavior |
|----------|--------------|
| `current_time()` | Returns current UTC datetime |
| `add_filter()` | Returns `true` |
| `add_action()` | Returns `true` |
| `wp_next_scheduled()` | Returns `false` |
| `wp_schedule_event()` | Returns `true` |
| `wp_unschedule_event()` | Returns `true` |
| `wp_mail()` | Returns `true` |
| `get_userdata()` | Returns mock user object |
| `__()` | Returns untranslated string |

## Testing Tips

1. **Keep jobs serializable** — Test that your job can be serialized and unserialized.
2. **Test failure handling** — Verify your `failed()` method handles exceptions correctly.
3. **Mock external services** — Don't make real HTTP calls or send real emails in tests.
4. **Test idempotency** — Jobs may be processed more than once in edge cases.
