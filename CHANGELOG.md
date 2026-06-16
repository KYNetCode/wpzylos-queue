# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.0.0 - 2026-06-16

First stable release of wpzylos-queue

## [Unreleased]

### Added

- Abstract `Job` base class with retry, timeout, and queue configuration
- `Queue` class with `push()`, `later()`, `size()`, and `clear()` methods
- `Worker` class with retry logic and failure tracking
- `QueueTableInstaller` for creating `queue_jobs` and `queue_failures` tables via `dbDelta`
- `QueueCronHandler` for WP-Cron integration with automatic queue processing
- `QueueServiceProvider` for container registration and cron bootstrapping
- Failed job management: list, retry, and delete
- Database-backed persistent job storage
