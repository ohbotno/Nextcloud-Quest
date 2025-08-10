# Contributing to Quest

Thank you for considering contributing to Quest! This document provides guidelines and information for contributors.

## 🤝 Ways to Contribute

### 🐛 Bug Reports
- Use the [GitHub Issues](https://github.com/nextcloud/nextcloud-quest/issues) page
- Search existing issues before creating new ones
- Provide detailed reproduction steps
- Include system information (Nextcloud version, PHP version, browser)
- Attach logs when applicable

### 💡 Feature Requests
- Open a [GitHub Issue](https://github.com/nextcloud/nextcloud-quest/issues) with the "enhancement" label
- Describe the problem you're trying to solve
- Explain how your feature would benefit users
- Consider backward compatibility and performance implications

### 🔧 Code Contributions
- Fork the repository and create a feature branch
- Follow our coding standards (see below)
- Include tests for new functionality
- Update documentation as needed
- Submit a pull request with detailed description

### 📚 Documentation
- Improve README, code comments, or inline documentation
- Create tutorials or guides
- Translate the app into other languages
- Update API documentation

### 🧪 Testing
- Test beta releases and report issues
- Verify bug fixes
- Test on different platforms and browsers
- Performance testing with large datasets

## 🚀 Development Setup

### Prerequisites
- Nextcloud development environment (31.0+)
- PHP 8.3+
- Node.js 20+ and npm 10+
- Composer
- Git

### Local Development
```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/nextcloud-quest.git
cd nextcloud-quest

# Install dependencies
composer install
npm install

# Start development build with watch
npm run dev

# Enable the app in Nextcloud
sudo -u www-data php occ app:enable nextcloudquest
```

### Development Workflow
1. Create a feature branch: `git checkout -b feature/your-feature-name`
2. Make your changes following our coding standards
3. Test your changes thoroughly
4. Commit with descriptive messages
5. Push to your fork and open a pull request

## 📋 Coding Standards

### PHP Code
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use Nextcloud's coding conventions
- Add comprehensive PHPDoc comments
- Use type hints for all method parameters and return types
- Follow SOLID principles

Example:
```php
<?php
/**
 * Calculate XP for a completed task
 * 
 * @param string $priority Task priority: 'high', 'medium', 'low'
 * @param int $currentStreak User's current streak
 * @return int Calculated XP amount
 */
public function calculateXP(string $priority, int $currentStreak): int {
    // Implementation here
}
```

### JavaScript/Vue.js
- Follow ESLint configuration
- Use Vue.js best practices
- Add JSDoc comments for complex functions
- Use TypeScript-style prop definitions
- Follow component naming conventions

Example:
```javascript
/**
 * Calculate total XP including bonuses
 * @param {number} baseXP - Base experience points
 * @param {number} bonus - Priority bonus
 * @param {number} multiplier - Streak multiplier
 * @returns {number} Total calculated XP
 */
calculateTotalXP(baseXP, bonus, multiplier) {
    return Math.round((baseXP + bonus) * multiplier)
}
```

### CSS/SCSS
- Use Nextcloud's design system variables
- Follow BEM naming convention
- Write responsive styles (mobile-first)
- Use semantic class names
- Add comments for complex layouts

Example:
```scss
.quest-achievement-card {
    &__icon {
        width: 48px;
        height: 48px;
    }
    
    &__title {
        font-weight: 600;
        color: var(--color-main-text);
    }
    
    &--unlocked {
        border-color: var(--color-success);
    }
}
```

## 🧪 Testing

### Unit Tests
```bash
# Run PHP tests
composer run test:unit

# Run with coverage
composer run test:coverage
```

### Integration Tests
```bash
# Run integration tests
composer run test:integration
```

### Frontend Tests
```bash
# Run JavaScript tests (when available)
npm run test
```

### Manual Testing Checklist
- [ ] Task completion awards correct XP
- [ ] Streaks update properly across day boundaries
- [ ] Achievements unlock at correct milestones
- [ ] Level progression works correctly
- [ ] Notifications display properly
- [ ] Settings save and load correctly
- [ ] Mobile responsiveness works
- [ ] Both themes render correctly
- [ ] Performance is acceptable with large datasets

## 📝 Pull Request Guidelines

### Before Submitting
- [ ] Code follows our style guidelines
- [ ] Tests pass locally
- [ ] Documentation is updated
- [ ] Commit messages are descriptive
- [ ] No merge conflicts with main branch

### PR Title Format
Use one of these prefixes:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code formatting changes
- `refactor:` Code refactoring
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

Example: `feat: add weekly challenge system`

### PR Description Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed

## Screenshots (if applicable)
Add screenshots for UI changes

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
```

## 🏗️ Architecture Guidelines

### Backend Structure
```
lib/
├── Controller/     # API endpoints and request handling
├── Service/        # Business logic and calculations
├── Db/            # Database entities and mappers
├── Integration/    # External app integrations
├── BackgroundJob/  # Scheduled maintenance tasks
└── Notification/   # Notification providers
```

### Frontend Structure
```
src/
├── components/     # Vue.js components
├── services/       # API communication layer
├── store/         # Vuex state management
└── assets/        # Static assets and images
```

### Database Design
- Use Nextcloud's query builder
- Add proper indexes for performance
- Follow Nextcloud naming conventions
- Include migration files for schema changes

### Security Considerations
- Validate all user input
- Use prepared statements for database queries
- Implement proper access controls
- Sanitize output to prevent XSS
- Follow Nextcloud security best practices

## 🌍 Internationalization

### Adding Translations
1. Extract translatable strings: `npm run extract-l10n`
2. Update translation files in `l10n/`
3. Test with different languages
4. Submit translations via Crowdin (when available)

### Translation Guidelines
- Use `t('app', 'string')` for PHP translations
- Use `t('nextcloudquest', 'string')` for JavaScript
- Keep strings short and descriptive
- Avoid concatenating translated strings
- Use placeholders for dynamic content

## 🚢 Release Process

### Version Numbering
We follow [Semantic Versioning](https://semver.org/):
- MAJOR.MINOR.PATCH (e.g., 1.2.3)
- Major: Breaking changes
- Minor: New features (backward compatible)
- Patch: Bug fixes

### Release Checklist
- [ ] All tests pass
- [ ] Version numbers updated
- [ ] CHANGELOG.md updated
- [ ] Documentation reviewed
- [ ] Migration scripts tested
- [ ] Performance testing completed
- [ ] Security review passed

## 📞 Getting Help

### Channels
- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: General questions and ideas
- **Nextcloud Forums**: Community support
- **IRC**: #nextcloud-dev on Freenode

### Maintainer Response Times
- Bug reports: Within 48 hours
- Feature requests: Within 1 week
- Pull requests: Within 1 week
- Security issues: Within 24 hours

## 🏆 Recognition

Contributors are recognized in:
- README.md contributors section
- Release notes
- Git commit history
- Optional contributors file

## 📜 Code of Conduct

This project follows the [Nextcloud Code of Conduct](https://nextcloud.com/contribute/code-of-conduct/). By participating, you agree to uphold this code.

### Our Standards
- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards community members

## 📄 License

By contributing to Quest, you agree that your contributions will be licensed under the GNU AGPL v3.0 License.

---

Thank you for helping make Quest better for everyone! 🚀