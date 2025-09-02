# Contributing to Ultimate WordPress Backup Migration

Thank you for your interest in contributing to Ultimate WordPress Backup Migration! This document provides guidelines for contributing to the project.

## 🤝 How to Contribute

### Reporting Issues
- Use the [GitHub Issues](https://github.com/StouteWebSolutions/ultimate-wp-backup-migration/issues) page
- Search existing issues before creating a new one
- Provide detailed information including WordPress version, PHP version, and error messages
- Include steps to reproduce the issue

### Suggesting Features
- Open a GitHub issue with the "enhancement" label
- Describe the feature and its benefits
- Explain how it fits with the plugin's goals

### Code Contributions
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following our coding standards
4. Test your changes thoroughly
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## 📝 Coding Standards

### WordPress Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use WordPress functions instead of PHP equivalents where available
- Prefix all functions, classes, and hooks with `uwpbm_` or `UWPBM_`

### PHP Standards
- Use PHP 7.4+ features appropriately
- Follow PSR-4 autoloading standards
- Add PHPDoc comments for all functions and classes
- Use type hints where appropriate

### Security
- Sanitize all inputs using WordPress functions
- Escape all outputs using WordPress functions
- Use nonces for all form submissions
- Check user capabilities before performing actions

### Example Code Style
```php
<?php
/**
 * Example class following our standards
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example class description
 */
class UWPBM_Example {
    
    /**
     * Example method
     *
     * @param string $input User input to process
     * @return string Processed output
     */
    public function process_input($input) {
        // Sanitize input
        $clean_input = sanitize_text_field($input);
        
        // Process and return
        return esc_html($clean_input);
    }
}
```

## 🧪 Testing

### Before Submitting
- Test on a fresh WordPress installation
- Test with different PHP versions (7.4, 8.0, 8.1, 8.2)
- Test with different WordPress versions (5.0+)
- Verify multisite compatibility
- Check for PHP errors and warnings

### Test Cases
- Basic backup and restore functionality
- All storage protocols (Local, FTP, SFTP)
- Scheduling and monitoring features
- WP-CLI commands
- Large site handling

## 📚 Documentation

### Code Documentation
- Add PHPDoc comments for all public methods
- Include parameter types and return types
- Explain complex logic with inline comments

### User Documentation
- Update README.md for new features
- Update INSTALLATION_GUIDE.md if installation changes
- Add examples for new functionality

## 🔄 Pull Request Process

### Before Submitting
1. Ensure your code follows WordPress coding standards
2. Test thoroughly on multiple environments
3. Update documentation as needed
4. Add or update unit tests if applicable

### Pull Request Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tested on WordPress 5.0+
- [ ] Tested on PHP 7.4+
- [ ] Tested multisite compatibility
- [ ] No PHP errors or warnings

## Checklist
- [ ] Code follows WordPress standards
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Tests added/updated
```

## 🏷️ Versioning

We use [Semantic Versioning](https://semver.org/):
- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality
- **PATCH** version for backwards-compatible bug fixes

## 📄 License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.

## 🆘 Getting Help

- Check existing [GitHub Issues](https://github.com/StouteWebSolutions/ultimate-wp-backup-migration/issues)
- Review the [Installation Guide](INSTALLATION_GUIDE.md)
- Ask questions in GitHub Discussions

## 🙏 Recognition

Contributors will be recognized in:
- Plugin credits
- GitHub contributors list
- Release notes for significant contributions

Thank you for helping make Ultimate WordPress Backup Migration better for everyone!
