# Contributing to Zen Framework

Thank you for your interest in contributing to Zen Framework!

## Code of Conduct

By participating in this project, you are expected to:
- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on what's best for the community
- Show empathy towards other community members

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported
2. Create a clear, descriptive issue
3. Include steps to reproduce
4. Include your environment details (PHP version, OS, etc.)

### Suggesting Features

1. Check if feature has been suggested
2. Describe the feature in detail
3. Explain why this feature would be useful
4. Provide code examples if possible

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following the code style
4. Run tests and lint before submitting
5. Commit with clear, descriptive messages
6. Push to your fork and submit a PR

## Development Setup

```bash
# Clone the repository
git clone https://github.com/zenithframework/zenithframework.git
cd zenithframework

# Install dependencies (if any)
composer install

# Run tests
php zen test

# Run lint
php zen lint

# Create migrations
php zen make:migration add_feature
```

## Code Style

- Use `declare(strict_types=1);` in all PHP files
- Use PSR-12 formatting
- Use top-level imports (no inline namespaces)
- Run `php zen lint` before committing

```php
<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class MyCommand extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $this->info("Done!");
    }
}
```

## Adding New Features

When adding new features:

1. Add CLI command in `core/Console/Commands/`
2. Register command in `zen` CLI file
3. Add to autoloader if new namespace
4. Update documentation
5. Add tests

## Testing

```bash
# Run all tests
php zen test

# Run specific test file
php zen test --filter=test_name
```

## Documentation

- Update README.md for user-facing changes
- Update GUIDE.md for significant changes
- Update SKILLS.md for CLI/API changes
- Add docstrings to new classes and methods

## Recognition

Contributors will be listed in:
- README.md contributors section
- CHANGELOG.md credits

## Questions?

- Open an issue for general questions
- Join discussions in the repository

Thank you for your contributions!