# Contributing to WPZylos Queue

Thank you for your interest in contributing to the WPZylos Queue package! This guide will help you get started.

## Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/KYNetCode/wpzylos-queue.git
   cd wpzylos-queue
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Run the test suite:**
   ```bash
   composer test
   ```

## Quality Tools

This project uses several tools to maintain code quality:

| Tool | Command | Purpose |
|------|---------|---------|
| PHPUnit | `composer test` | Run tests |
| PHPStan | `composer analyze` | Static analysis (level 5) |
| PHP_CodeSniffer | `composer cs` | Check coding standards |
| PHP_CodeSniffer | `composer cs-fix` | Fix coding standards |
| All checks | `composer qa` | Run all quality checks |

## Pull Request Guidelines

1. **Fork** the repository and create a feature branch from `main`.
2. **Write tests** for any new functionality or bug fixes.
3. **Follow PSR-12** coding standards — run `composer cs` before submitting.
4. **Run all quality checks** with `composer qa` and ensure they pass.
5. **Write clear commit messages** following [Conventional Commits](https://www.conventionalcommits.org/).
6. **Update documentation** if your changes affect public APIs.
7. **One feature per PR** — keep pull requests focused and small.

## Reporting Bugs

- Use the [GitHub Issues](https://github.com/KYNetCode/wpzylos-queue/issues) tracker.
- Include PHP version, WordPress version, and steps to reproduce.
- Include relevant error messages and stack traces.

## Feature Requests

- Open an issue with the `enhancement` label.
- Describe the use case and expected behavior.

## Code of Conduct

Please be respectful and constructive in all interactions. We are committed to providing a welcoming and inclusive experience for everyone.

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
