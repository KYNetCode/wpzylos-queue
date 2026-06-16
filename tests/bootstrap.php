<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for WPZylos Queue tests.
 *
 * Mocks WordPress functions used by the queue package.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// ─── WordPress function mocks ────────────────────────────────────────────────

if (!function_exists('current_time')) {
    function current_time(string $type, bool $gmt = false): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return true;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled(string $hook, array $args = []): int|false
    {
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event(int $timestamp, string $recurrence, string $hook, array $args = []): bool
    {
        return true;
    }
}

if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event(int $timestamp, string $hook, array $args = []): bool
    {
        return true;
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail(string $to, string $subject, string $message, $headers = '', $attachments = []): bool
    {
        return true;
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata(int $user_id): object|false
    {
        return (object) [
            'ID'         => $user_id,
            'user_email' => "user{$user_id}@example.com",
            'user_login' => "user{$user_id}",
        ];
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
