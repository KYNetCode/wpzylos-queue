# Installation

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.0 |
| WordPress | >= 6.0 |
| wpzylos-core | ^1.0 |
| wpzylos-database | ^1.0 |

## Composer Install

```bash
composer require KYNetCode/wpzylos-queue
```

This will also install the required `wpzylos-core` and `wpzylos-database` packages if not already present.

## Register the ServiceProvider

Add the `QueueServiceProvider` to your plugin bootstrap:

```php
use WPZylos\Framework\Queue\QueueServiceProvider;

// In your plugin's main file or bootstrap
$app->register(new QueueServiceProvider());
```

The service provider automatically:

1. Registers `Queue`, `Worker`, `QueueCronHandler`, and `QueueTableInstaller` as singletons
2. Creates convenience aliases (`queue`, `queue.worker`)
3. Boots the WP-Cron handler for background processing

## Create Database Tables

Run the table installer on plugin activation:

```php
use WPZylos\Framework\Queue\QueueTableInstaller;

register_activation_hook(__FILE__, function () use ($app) {
    $installer = $app->make(QueueTableInstaller::class);
    $installer->install();
});
```

This creates two tables using WordPress `dbDelta`:

- `{prefix}_queue_jobs` — Pending and in-progress jobs
- `{prefix}_queue_failures` — Failed jobs with exception details

## Cleanup on Uninstall

Optionally drop the tables on plugin uninstall:

```php
// uninstall.php
use WPZylos\Framework\Queue\QueueTableInstaller;

$installer = $app->make(QueueTableInstaller::class);
$installer->uninstall();
```

## Verify Installation

After activation, verify the tables exist:

```php
global $wpdb;
$table = $wpdb->prefix . 'yourprefix_queue_jobs';
$exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
```
