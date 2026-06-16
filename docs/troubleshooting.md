# Troubleshooting

## Jobs Are Not Being Processed

### WP-Cron is not running

**Symptom:** Jobs stay in `queue_jobs` table indefinitely.

**Cause:** WP-Cron relies on page visits to trigger. Low-traffic sites may not trigger cron often enough.

**Solution:** Set up a system cron job:

```bash
* * * * * wget -q -O /dev/null https://yoursite.com/wp-cron.php?doing_wp_cron
```

And disable WP-Cron's default behavior:

```php
// wp-config.php
define('DISABLE_WP_CRON', true);
```

### Service provider not registered

**Symptom:** No cron event is scheduled.

**Solution:** Ensure `QueueServiceProvider` is registered in your plugin bootstrap:

```php
$app->register(new QueueServiceProvider());
```

### Tables don't exist

**Symptom:** Database errors when pushing or processing jobs.

**Solution:** Run the table installer:

```php
$app->make(QueueTableInstaller::class)->install();
```

---

## Jobs Keep Failing

### Unserialize errors

**Symptom:** Jobs fail with "Failed to unserialize job payload".

**Cause:** The job class was renamed, moved, or deleted after dispatch.

**Solution:**
- Don't rename or move job classes after they've been dispatched
- Clear the queue before deploying class changes: `$queue->clear()`

### Timeout exceeded

**Symptom:** Jobs fail with timeout errors.

**Solution:** Increase the job's `$timeout` property:

```php
protected int $timeout = 300; // 5 minutes
```

### External service failures

**Symptom:** Jobs fail when calling APIs or sending emails.

**Solution:** Increase `$tries` and `$retryAfter` to handle transient failures:

```php
protected int $tries = 5;
protected int $retryAfter = 300; // 5 minutes between retries
```

---

## Duplicate Job Execution

**Symptom:** The same job is processed more than once.

**Cause:** This can happen if:
- The job takes longer than the cron interval (60s)
- Multiple concurrent WP-Cron requests fire

**Solution:**
- Ensure job execution time is under 60 seconds
- Make jobs idempotent (safe to run twice)
- The atomic locking (`reserved_at`) should prevent most duplicates

---

## Queue Table Growing Too Large

**Symptom:** The `queue_jobs` table has thousands of rows.

**Cause:** Jobs are being pushed faster than they're processed.

**Solution:**
- Increase batch size: `$cron->setBatchSize(50)`
- Set up system cron for more reliable execution
- Check if jobs are failing and being retried indefinitely

---

## Memory Issues

**Symptom:** "Allowed memory size exhausted" during job processing.

**Solution:**
- Keep job payloads small (pass IDs, not full objects)
- Process fewer jobs per batch: `$cron->setBatchSize(5)`
- Increase PHP memory limit for cron:

```php
// In a mu-plugin or wp-config.php
if (defined('DOING_CRON') && DOING_CRON) {
    ini_set('memory_limit', '512M');
}
```

---

## Debugging Jobs

### Check pending jobs

```sql
SELECT * FROM wp_yourprefix_queue_jobs ORDER BY created_at DESC;
```

### Check failed jobs

```sql
SELECT id, queue, failed_at, LEFT(exception, 200) as error
FROM wp_yourprefix_queue_failures
ORDER BY failed_at DESC;
```

### Manually process a job

```php
$worker = $app->make(Worker::class);
$result = $worker->process('default');
var_dump($result); // true = processed, false = empty queue
```

### Check cron schedule

```php
$hook = $context->cronHook('process_queue');
$next = wp_next_scheduled($hook);
echo $next ? date('Y-m-d H:i:s', $next) : 'Not scheduled';
```
