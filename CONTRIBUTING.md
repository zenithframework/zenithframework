# Contributing to Zenith Framework

Thank you for your interest in contributing to Zenith Framework!

**Official Website:** [zenithframework.com](https://zenithframework.com)

## Code of Conduct

### Our Pledge

We as members, contributors, and leaders pledge to make participation in our community a harassment-free experience for everyone, regardless of age, body size, visible or invisible disability, ethnicity, sex characteristics, gender identity and expression, level of experience, education, socio-economic status, nationality, personal appearance, race, religion, or sexual identity and orientation.

We pledge to act and interact in ways that contribute to an open, welcoming, diverse, inclusive, and healthy community.

### Our Standards

Examples of behavior that contributes to a positive environment:

- **Being respectful** of differing viewpoints and experiences
- **Giving and gracefully accepting** constructive feedback
- **Accepting responsibility** and apologizing to those affected by our mistakes
- **Focusing on what's best** for the overall community
- **Welcoming newcomers** and helping them learn
- **Showing empathy** towards other community members
- **Using inclusive language** and avoiding discriminatory jokes or comments

Examples of unacceptable behavior:

- **Harassment**, intimidation, or discrimination in any form
- **Personal attacks**, insulting/derogatory comments, or trolling
- **Publishing others' private information** without explicit permission
- **Sexualized language or imagery**, unwanted sexual attention
- **Spam**, off-topic promotional content, or disruptive behavior
- **Other conduct** which could reasonably be considered inappropriate

### Enforcement Responsibilities

Project maintainers are responsible for clarifying and enforcing our standards of acceptable behavior and will take appropriate and fair corrective action in response to any behavior that they deem inappropriate, threatening, offensive, or harmful.

### Scope

This Code of Conduct applies within all community spaces, including:
- GitHub Issues and Pull Requests
- Discussions and comments
- Project documentation
- Community chat channels
- Social media interactions

### Reporting Violations

Instances of abusive, harassing, or otherwise unacceptable behavior may be reported to the project maintainers at **conduct@zenframework.dev**. All complaints will be reviewed and investigated promptly and fairly.

All project maintainers are obligated to respect the privacy and security of the reporter of any incident.

### Enforcement Guidelines

Project maintainers will follow these Community Impact Guidelines in determining the consequences for any action they deem in violation of this Code of Conduct:

1. **Correction** - Private warning about the violation
2. **Warning** - Warning with consequences for continued behavior
3. **Temporary Ban** - Temporary suspension from community spaces
4. **Permanent Ban** - Permanent removal from all community spaces

### Attribution

This Code of Conduct is adapted from the [Contributor Covenant](https://www.contributor-covenant.org), version 2.1.

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