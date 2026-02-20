# Changelog

All notable changes to the Prescia framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-02-20

### Breaking Changes
- **PHP 8.0+ Required**: Minimum PHP version requirement updated from PHP 5.4+ to PHP 8.0+
- **MySQL Extension Removed**: The deprecated `mysql` extension driver has been removed. Only `mysqli` is now supported.

### Changed
- Updated PHP version requirement from PHP 5.4+ to PHP 8.0+ in `/index.php`
- Updated database connector configuration to only support `mysqli` in `/config/settings.php.original`
- Replaced deprecated `each()` function with `foreach` in `/prescia/lib/_mqgpc.inc.php`
- Replaced deprecated `ereg()` function with `preg_match()` in `/prescia/plugins/bi_adm/payload/actions/import.php`

### Removed
- **Deprecated MySQL Driver**: Removed `/prescia/lib/dbo/mysql.php` as it used the deprecated `mysql_*` functions that were removed in PHP 7.0

### Security
- The framework continues to use the following security measures:
  - **Input Sanitization**: `cleanString()`, `cleanHTML()`, and `addslashes_EX()` functions for SQL injection and XSS prevention
  - **Bot Protection**: Honeypot links, crawler whitelist/blacklist, rate limiting, and IP-based temporary banning
  - **Authentication**: Master override password system, session-based access control
  - **HTML Filtering**: Regex-based dangerous tag removal (script, form, iframe, etc.)

### Notes
- All deprecated PHP 5.x and PHP 7.x functions have been updated for PHP 8+ compatibility
- The framework now requires `mysqli` extension for database connectivity
- Short tags (`<?`) are still required for this version
- UTF-8 encoding remains mandatory

### Migration Guide

#### For Existing Installations

1. **Update PHP Version**: Ensure your server is running PHP 8.0 or higher
   ```bash
   php -v
   ```

2. **Update Configuration**:
   - Edit your `config/settings.php` file
   - Ensure `CONS_AFF_DATABASECONNECTOR` is set to `"mysqli"`
   - Remove any references to `"mysql"` connector

3. **Enable MySQLi Extension**: Verify mysqli is enabled
   ```bash
   php -m | grep mysqli
   ```

4. **Test Your Installation**:
   - Access your Prescia installation
   - Check error logs in `_temp/_logs/` for any compatibility issues
   - Test database connectivity through admin panel

#### Breaking Changes Impact

**MySQL Extension Removal**:
- If you were using `CONS_AFF_DATABASECONNECTOR = "mysql"`, you must change it to `"mysqli"`
- The mysqli driver has been supported since the beginning and offers better performance
- No database schema changes are required

**PHP 8.0+ Requirement**:
- Deprecated functions (`each()`, `ereg()`) have been replaced
- Code is now compatible with PHP 8.0, 8.1, 8.2, and 8.3
- Improved type safety and error handling

### Recommendations

1. **Database**: Continue using MySQLi driver with prepared statements for enhanced security
2. **Error Reporting**: Keep `CONS_AFF_ERRORHANDLER` enabled in production
3. **Caching**: Utilize the built-in cache system for optimal performance
4. **Security**: Review and update `CONS_MASTERPASS` and `CONS_MASTERMAIL` in settings
5. **Monitoring**: Check performance logs regularly in `_temp/_logs/pm.log`

### Known Issues

- Short tags (`<?`) are still required and must be enabled in php.ini
- Some legacy code patterns remain from the original 2004 codebase
- Framework is optimized for Apache; IIS requires additional configuration

### Future Considerations

- Consider migrating to full `<?php` tags instead of short tags for better compatibility
- Evaluate moving from regex-based HTML filtering to a modern sanitization library
- Consider implementing PDO as an alternative to mysqli for database abstraction
- Add comprehensive unit testing infrastructure

---

## Previous Versions

This is the first formal changelog entry. Previous versions (2004-2025) were maintained without a formal changelog.

### Historical Notes

- **2004**: Initial framework conception
- **2006**: Second iteration based on earlier work
- **2011+**: Prescia framework released under BSD-new license
- **500+ Sites**: Served over 500 production sites worldwide before open source release
- **2025**: PHP 8+ migration and modernization effort initiated
