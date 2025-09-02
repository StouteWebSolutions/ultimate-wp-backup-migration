# Ultimate WordPress Backup Migration

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v3%2B-green.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

The ultimate WordPress migration and backup solution with unlimited file sizes, multiple protocols, and complete local control.

## 🚀 Features

### Core Features
- **Unlimited File Sizes** - No artificial restrictions on backup sizes
- **Multiple Storage Protocols** - FTP, SFTP, and Local storage built-in
- **Complete Local Control** - No external dependencies or cloud requirements
- **Single Plugin Solution** - No extensions needed, everything included

### Advanced Features
- **Real-time Monitoring** - Automatic change detection and backup triggers
- **Incremental Backups** - Only backup what has changed since last backup
- **Advanced Scheduling** - Flexible automated backup schedules with cron
- **Professional Interface** - Modern, responsive admin interface
- **WP-CLI Support** - Full command-line integration for automation

### Enterprise Features
- **Setup Wizard** - Easy step-by-step configuration for new users
- **Email Notifications** - Get notified of backup success/failure
- **Activity Logging** - Comprehensive logs for debugging and monitoring
- **Performance Optimized** - Handles large sites efficiently
- **Security Focused** - All data stays on your servers

## 🎯 Why Choose This Plugin?

### vs All-in-One WP Migration
- ✅ **Single Plugin** - No extensions needed
- ✅ **No File Limits** - Remove artificial 512MB restriction
- ✅ **Built-in Protocols** - FTP/SFTP included by default
- ✅ **Real-time Monitoring** - Advanced features they don't have

### vs BlogVault
- ✅ **No Subscriptions** - One-time install, lifetime use
- ✅ **Complete Privacy** - No external data storage
- ✅ **Local Control** - All processing on your server
- ✅ **Open Source** - Transparent, auditable code

### vs Migrate Guru
- ✅ **Full Backup System** - Not just migration, complete backup solution
- ✅ **Advanced Scheduling** - Automated backups with monitoring
- ✅ **Local Processing** - No cloud dependency
- ✅ **Professional Features** - Enterprise-grade capabilities

## 📦 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the latest release
2. Go to `Plugins > Add New > Upload Plugin`
3. Select the downloaded ZIP file
4. Click "Install Now" and then "Activate"

### Method 2: Manual Installation
1. Download and extract the plugin
2. Upload the `ultimate-wp-backup-migration` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin

### Method 3: Git Clone (Development)
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/StouteWebSolutions/ultimate-wp-backup-migration.git
```

## 🛠️ Quick Start

### 1. Setup Wizard
After activation, use the Setup Wizard:
- Navigate to the WordPress dashboard
- Follow the 4-step configuration wizard
- Choose your storage method
- Configure scheduling (optional)

### 2. Create Your First Backup
- Go to `Backup Migration > Export`
- Select what to include (Database, Media, Plugins, Themes)
- Choose storage destination
- Click "Start Backup"

### 3. Monitor Progress
- Real-time progress tracking
- Email notifications
- Activity logs

## 📋 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **Disk Space:** At least 2x your site size for temporary files

### Optional Extensions
- **php-ftp** - For FTP protocol support
- **ssh2** - For SFTP protocol support
- **zip** - For archive creation (usually included)

## 🔧 Configuration

### Storage Protocols

#### Local Storage
- Default option, works out of the box
- Stores backups in `/wp-content/uploads/uwpbm-backups/`
- No additional configuration needed

#### FTP Storage
- Configure in `Settings > FTP Settings`
- Supports both active and passive modes
- SSL/TLS encryption supported

#### SFTP Storage
- Configure in `Settings > SFTP Settings`
- Password or private key authentication
- Secure SSH connection

### Advanced Settings
- **Memory Limit:** Adjust for large sites
- **Execution Time:** Increase for large backups
- **Chunk Size:** Optimize for your server
- **Retention Policy:** Automatic cleanup of old backups

## 🖥️ WP-CLI Usage

```bash
# Check plugin status
wp uwpbm status

# Create a backup
wp uwpbm backup --protocol=local --name="my-backup"

# List all backups
wp uwpbm list

# Restore a backup
wp uwpbm restore 123

# Test connection
wp uwpbm test-connection ftp
```

## 🔍 Troubleshooting

### Common Issues

**Memory Errors:**
- Increase PHP memory limit in wp-config.php or plugin settings
- Use incremental backups for large sites

**Timeout Errors:**
- Increase max execution time
- Break large backups into smaller chunks

**FTP Connection Issues:**
- Verify credentials and server settings
- Test passive vs active mode
- Check firewall settings

**SFTP Connection Issues:**
- Ensure SSH2 extension is installed
- Verify key format and permissions
- Test authentication method

### Debug Mode
Enable WordPress debug logging:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
1. Clone the repository
2. Install development dependencies
3. Follow WordPress coding standards
4. Submit pull requests for review

## 📄 License

This plugin is licensed under the GPL v3 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.
```

## 🆘 Support

- **Documentation:** [Installation Guide](INSTALLATION_GUIDE.md)
- **Issues:** [GitHub Issues](https://github.com/StouteWebSolutions/ultimate-wp-backup-migration/issues)
- **WordPress Support:** [Plugin Support Forum](https://wordpress.org/support/plugin/ultimate-wp-backup-migration/)

## 🏆 Acknowledgments

Built with ❤️ for the WordPress community. Special thanks to all contributors and testers who help make this plugin better.

---

**Made with WordPress standards and best practices in mind.**