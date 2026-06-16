# Examples

## Email Notification Queue

Send emails in the background to avoid blocking the request:

```php
use WPZylos\Framework\Queue\Job;

class SendOrderConfirmation extends Job
{
    protected int $tries = 3;
    protected int $retryAfter = 120;
    protected string $queue = 'emails';

    public function __construct(
        private int $orderId,
        private string $recipientEmail
    ) {}

    public function handle(): void
    {
        $order = wc_get_order($this->orderId);
        
        $subject = "Order #{$this->orderId} Confirmed";
        $body = $this->buildEmailBody($order);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        if (!wp_mail($this->recipientEmail, $subject, $body, $headers)) {
            throw new \RuntimeException("Failed to send email to {$this->recipientEmail}");
        }
    }

    private function buildEmailBody($order): string
    {
        ob_start();
        wc_get_template('emails/customer-completed-order.php', ['order' => $order]);
        return ob_get_clean();
    }

    public function failed(\Throwable $exception): void
    {
        error_log("Order confirmation email failed for #{$this->orderId}: {$exception->getMessage()}");
    }
}

// Usage
$queue->push(new SendOrderConfirmation(123, 'customer@example.com'));
```

## Image Processing

Resize uploaded images in the background:

```php
use WPZylos\Framework\Queue\Job;

class ResizeImage extends Job
{
    protected int $tries = 2;
    protected int $timeout = 120;
    protected string $queue = 'media';

    public function __construct(
        private int $attachmentId,
        private int $width,
        private int $height
    ) {}

    public function handle(): void
    {
        $filePath = get_attached_file($this->attachmentId);
        
        if (!$filePath || !file_exists($filePath)) {
            throw new \RuntimeException("Attachment file not found: {$this->attachmentId}");
        }

        $editor = wp_get_image_editor($filePath);
        
        if (is_wp_error($editor)) {
            throw new \RuntimeException($editor->get_error_message());
        }

        $editor->resize($this->width, $this->height, true);
        
        $newFile = str_replace('.', "-{$this->width}x{$this->height}.", $filePath);
        $editor->save($newFile);

        // Update attachment metadata
        $metadata = wp_get_attachment_metadata($this->attachmentId);
        $metadata['sizes']["custom-{$this->width}x{$this->height}"] = [
            'file'   => basename($newFile),
            'width'  => $this->width,
            'height' => $this->height,
        ];
        wp_update_attachment_metadata($this->attachmentId, $metadata);
    }
}

// Usage
$queue->push(new ResizeImage(456, 800, 600));
$queue->push(new ResizeImage(456, 400, 300));
```

## API Data Sync

Sync data with an external API in the background:

```php
use WPZylos\Framework\Queue\Job;

class SyncProductToApi extends Job
{
    protected int $tries = 5;
    protected int $retryAfter = 300; // 5 minutes
    protected int $timeout = 30;
    protected string $queue = 'sync';

    public function __construct(
        private int $productId,
        private string $action // 'create', 'update', 'delete'
    ) {}

    public function handle(): void
    {
        $product = wc_get_product($this->productId);
        
        if (!$product && $this->action !== 'delete') {
            throw new \RuntimeException("Product {$this->productId} not found");
        }

        $response = wp_remote_request('https://api.example.com/products', [
            'method'  => $this->getHttpMethod(),
            'body'    => wp_json_encode($this->buildPayload($product)),
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . get_option('api_token'),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 400) {
            throw new \RuntimeException("API returned HTTP {$code}");
        }

        update_post_meta($this->productId, '_api_synced_at', current_time('mysql'));
    }

    private function getHttpMethod(): string
    {
        return match ($this->action) {
            'create' => 'POST',
            'update' => 'PUT',
            'delete' => 'DELETE',
            default  => 'POST',
        };
    }

    private function buildPayload($product): array
    {
        return [
            'id'    => $this->productId,
            'name'  => $product->get_name(),
            'price' => $product->get_price(),
            'sku'   => $product->get_sku(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        update_post_meta($this->productId, '_api_sync_error', $exception->getMessage());
    }
}

// Usage — sync product when updated
add_action('woocommerce_update_product', function (int $productId) use ($queue) {
    $queue->push(new SyncProductToApi($productId, 'update'));
});
```

## Bulk Operations

Process a large batch of items using multiple jobs:

```php
use WPZylos\Framework\Queue\Job;

class ProcessBulkImport extends Job
{
    protected int $tries = 2;
    protected int $timeout = 300;
    protected string $queue = 'imports';

    public function __construct(
        private array $rows,   // chunk of CSV rows
        private int $batchNum
    ) {}

    public function handle(): void
    {
        foreach ($this->rows as $row) {
            wp_insert_post([
                'post_title'   => sanitize_text_field($row['title']),
                'post_content' => wp_kses_post($row['content']),
                'post_status'  => 'draft',
                'post_type'    => 'product',
            ]);
        }
    }
}

// Dispatch in chunks of 50
$csvRows = parse_csv_file($uploadedFile);
$chunks = array_chunk($csvRows, 50);

foreach ($chunks as $i => $chunk) {
    $queue->push(new ProcessBulkImport($chunk, $i + 1));
}
```

## Scheduled Cleanup

Dispatch a cleanup job with a delay:

```php
use WPZylos\Framework\Queue\Job;

class CleanupExpiredTokens extends Job
{
    protected int $tries = 1;
    protected string $queue = 'maintenance';

    public function handle(): void
    {
        global $wpdb;
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}api_tokens WHERE expires_at < %s",
                current_time('mysql', true)
            )
        );

        if ($deleted !== false) {
            error_log("Cleaned up {$deleted} expired tokens");
        }
    }
}

// Run cleanup in 1 hour
$queue->later(3600, new CleanupExpiredTokens());
```
