# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within this package, please report it responsibly.

**Do NOT open a public issue for security vulnerabilities.**

### How to Report

Send an email to **diggerwp@gmail.com** with the following details:

1. **Description** of the vulnerability
2. **Steps to reproduce** the issue
3. **Impact assessment** — what could an attacker do?
4. **Suggested fix** (if any)

### Response Timeline

| Stage | Timeframe |
|-------|-----------|
| Acknowledgment | Within **48 hours** |
| Initial assessment | Within **1 week** |
| Fix & release | Within **2 weeks** (critical issues) |

### Scope

The following are in scope for security reports:

- Serialization/deserialization vulnerabilities in job payloads
- SQL injection in queue queries
- Unauthorized job execution or manipulation
- Privilege escalation through queue processing
- Denial of service via queue flooding
- Information disclosure through failed job data

### Out of Scope

- Issues in WordPress core or third-party plugins
- Issues requiring physical access to the server
- Social engineering attacks

## Security Best Practices

When using this package:

- Restrict queue table access to the WordPress database user
- Sanitize and validate all data within job `handle()` methods
- Use WordPress nonce verification if jobs are dispatched from user input
- Monitor the `queue_failures` table for unusual activity
- Keep the package updated to the latest version

## Acknowledgments

We appreciate the security research community and will credit reporters (with permission) in release notes.
