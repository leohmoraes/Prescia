# Changelog

All notable changes to the Prescia framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-08-31

### Added
- Added PHPUnit-based PHP 8.3 compatibility tests in `tests/Php83CompatibilityTest.php`.
- Added Composer project metadata and the `composer test` command.
- Added GitHub Actions validation for PHP 8.3, required extensions and Docker build in `.github/workflows/php83.yml`.
- Added the prioritized security remediation plan in `docs/PLANO_ACAO_SEGURANCA.md`.
- Added the conservative migration helper in `tools/migrate_php83.php`, with dry-run, timestamped backups and a security blocker report.

### Changed
- Updated the Docker image from PHP 8.2 to PHP 8.3.
- Added the `mbstring` extension to the Docker image.
- Documented PHP 8.3 requirements and compatibility test commands in `README.md`.
- Updated PHPStan to the 2.x series and raised the initial analysis level to 1.
- Documented the incremental analysis policy: the baseline must not grow automatically and new diagnostics require remediation or an issue.
- Started typing the central component contracts, including `CModule`, authentication, cache, error, header, internationalization and scripted-module properties.
- Fixed PHP 8.3 compatibility-test file discovery by resolving the repository root and filtering excluded directories relative to that root.
- Fixed legacy `<?php/*` headers in framework libraries so PHP 8.3 and PHPStan parse and load them as PHP instead of emitting their source as plain text.
- Initialized legacy database query result variables before calls across core, lazy-loading and plugin flows, and fixed image/template typos involving `$im_dest`, `$minuatura` and `$str_daylabels`.
- Started explicit payload context contracts for `$core` and `$this` in the `bi_adm` and `bi_stats` payloads using PHPDoc annotations that document the framework-injected `CPrescia` and `CModule` contexts.
- Started the undefined-variable remediation batch by initializing prepared-query result handles in `CModule` and correcting the `$code`/`$valor` parameter typos in `CintlControl`.
- Started the legacy-symbol remediation batch by adding precise PHPStan stubs for `dieFreakingThumbs()`, `adodb_daylight_sv()` and the dynamically instantiated `CDBO_0` driver class.
- Aligned the seven PHPStan-reported child properties in `bi_adm`, `bi_bb`, `bi_dev`, `bi_labels` and `bi_undo`; `admFolder` now explicitly models its runtime string/list lifecycle.
- Corrected Lote D method contracts: preserved CKTemplate's legacy fourth constructor argument, removed the obsolete second `runclasses()` argument, aligned `checkPermission()` calls to three parameters, corrected `checkHTML()` usage, and separated duplicate nested action-log helpers.
- Started Lote E by adding generic array contracts to the procedural helpers `arrayToString()`, `extractUri()`, `listFiles()`, `xmlParamsParser()` and `adodb_daylight_sv()`, including their PHPStan stubs.
- Officialized Lote F as the controlled PHPStan baseline-review stage, with regression safeguards, mandatory metrics and a two-cycle stability gate before level 2.
- Expanded Lote F with an operational strategy for the current `1000+` diagnostic report, prioritizing undefined variables, validating object contracts and prohibiting baseline additions for regressions.
- Started Fase F1 by initializing database result handles and row counts in authentication, session, user-loading and integration-test flows without changing query success semantics.
- Completed the Fase F2 third-cycle payload contract corrections: core state in `bi_stats` and `bi_adm` now uses the injected parent/core object, and `bi_auth` no longer references undeclared module properties for the action and translation keys.
- Started Fase F2 by changing CDBO connection state to protected for legitimate driver inheritance and correcting invalid authorization references to module fields and ownerLink.
- Started the second Fase F2 cycle by declaring CPresciaVar::$headerControl, specializing payload PHPDocs to concrete plugin modules and exposing the legitimately consumed bi_adm::$hasStats state as protected.
- Executed the PHPStan analysis for Fase F2 cycle three; the CI formatter exposed 1,000 diagnostics, including 962 `variable.undefined` records and the remaining module-property contract targets documented in the PHPStan plan.
- Started the current Fase F2 property-visibility cycle by replacing direct payload access to `mod_bi_adm::$hasStats` and `$hasUndo` with explicit public read methods, preserving the module's private/protected state. The two affected administrative payloads pass focused PHPStan analysis.
- Started Fase F3 by correcting the `bi_stats` content payload to call `loadAllmodules()` and `loaded()` on the injected `CPrescia` core instead of the `mod_bi_stats` module. The payload now passes focused PHPStan analysis and PHP 8.3 syntax validation.
- Continued Fase F3 in `bi_adm/module.php` by correcting the case of the core call from `loadAllModules()` to the declared `loadAllmodules()` method. No method diagnostics remain in the focused output; unrelated undefined-variable and inner-function diagnostics remain assigned to separate batches.
- Started Fase F4 by isolating the dynamically selected database driver contract in `tools/phpstan-dynamic-classes.php`. The explicit `CDBO`/`CDBO_0` constructor hierarchy is loaded through `scanFiles`, removing the `class.notFound` diagnostics for `CDBO_0` without loading a production connector or expanding the baseline.
- Started Fase F5 by hardening `prescia/lazyload/botprotect.php` against empty crawler regex configuration. Empty blacklist/whitelist patterns now become a non-matching safe pattern before `preg_match()`, eliminating both `regexp.pattern` diagnostics without changing configured pattern behavior.
- Refactored the two recursive local `removeNull()` functions in `bi_cms` into scoped recursive closures. This removes the associated `function.inner` and `function.notFound` diagnostics without introducing global functions; only the preexisting payload-context diagnostic remains in the focused file.
- Documented the injected `CPrescia` contracts for `feedReader.php`, `fullSearch.php` and the `bi_labels` configuration action, removing 18 PHPStan undefined-variable diagnostics without expanding the baseline.
- Added the real `CDBO` and `CDBO_mysqli` driver files to PHPStan discovery and kept `CDBO_0` as the explicit dynamic subclass contract. The focused driver analysis now reports no `property.notFound` diagnostics for inherited connection, logging, timing or query state.
- Started the undefined-variable remediation batch in `bi_bb` by documenting the injected `CPrescia`/`mod_bi_bb` payload context and correcting pagination state from module-local `$templateParams` to `$core->templateParams`. The `index.php` payload now passes focused PHPStan with no errors.
- Continued the undefined-variable remediation in `prescia/lazyload/friendlyurl.php` by documenting its real `CPrescia::friendlyurl(array $param)` include contract. The focused analysis now passes with no errors, without inventing defaults for matching parameters.
- Continued the undefined-variable remediation in `prescia/lazyload/fastclose.php` by documenting the `CPrescia::fastClose($action, $context)` include contract. The focused analysis now passes with no errors.
- Reworked missing-path handling in both entrypoints and the legacy CKEditor loader. Configuration, database connector and CKEditor implementation paths are now calculated, checked with `is_file()` and reported through explicit `RuntimeException`s before inclusion. This removes the PHPStan `require.fileNotFound` and `includeOnce.fileNotFound` diagnostics without adding production placeholder files.
- Initialized the `index.php` output buffer before the `servingFile` branch so `$PAGE` is defined on every path before plugin hooks, error output, honeypot injection, compression and final echo. The focused PHPStan analysis now reports no errors for the entrypoint.
- Started the grouped undefined-variable batch by documenting the injected `CPrescia`/`mod_bi_bb` context in `bi_bb/payload/content/preview.php`. Its 26 context diagnostics are resolved and focused PHPStan now reports no errors.
- Hardened mutable request handling with session-cookie flags, CSRF validation and rotation, production-safe debug handling, logout session invalidation, secure persistent-login cookies and stricter password migration.
- Replaced predictable activation codes and unsafe serialized-object handling, validated uploads by structure, extension and content, and added regression coverage for CSRF, password hashes, serialization and uploads.
- Parameterized the affected statistics and administrative SQL statements, restricted administrative mutations to POST, fixed ownership analysis scope in `bi_auth`, and removed PHP 8.3 `continue`-in-`switch` warnings from administrative payloads.
- Reworked the Docker and CI defaults to keep application code root-owned, isolate development bind mounts, use the committed Composer lockfile and run blocking PHPStan/lint checks. The current working-tree validation reports 219 global PHPStan diagnostics, while the treated files pass focused analysis.
- Completed the `prescia/lazyload/tcaptcha.php` context batch by documenting the `CPrescia::tCaptcha(string $key, bool $checkStage)` include contract. All 12 `variable.undefined` diagnostics were removed; focused PHPStan and PHP 8.3 syntax validation pass.
- Completed the first Issue #47 priority batch: initialized guarded date-conversion state in `datetime.php`, documented the injected `CPrescia` context in `bi_labels/config_labels.php` and `presciatester/_config/config.php`, and removed 35 PHPStan diagnostics without changing the baseline.
- Completed the second Issue #47 priority batch: documented the `CPrescia::rss()` include contract, corrected RSS array-count validation, fixed the `CImporter` class case, corrected enum field indexing, and initialized import fallback state. The batch removed 20 PHPStan diagnostics without changing the baseline.
- Completed the third Issue #47 priority batch by documenting the injected `CPrescia` context in the `bi_labels` content payload and the `bi_fm` permission action. The batch removed 17 context diagnostics without changing the baseline.
- Completed the fourth Issue #47 priority batch by documenting the injected `CPrescia` context in the project-template configuration, the Prescia contact action and the presciatester default content. The batch removed 21 context diagnostics without changing the baseline.
- Completed the fifth Issue #47 priority batch: initialized MIME boundaries in `sendMail.php`, documented the plugin-loading context in `bi_undo`, and initialized query/key fallback state. The batch removed 14 diagnostics without changing the baseline.

### Compatibility checks
- The compatibility suite checks runtime version, required extensions, PHP syntax, removed/deprecated APIs, short tags and Docker base image.
- The suite is intentionally blocking while known legacy constructs remain; passing the suite is required before declaring PHP 8.3 compatibility.

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
- Continued the grouped undefined-variable remediation in `bi_bb/payload/content/forum.php` by documenting the injected `CPrescia`/`mod_bi_bb` context and initializing the query string before the operation-mode switch. Focused PHPStan and PHP 8.3 syntax validation now pass with no errors.
- Completed the grouped undefined-variable remediation in `bi_bb/payload/content/profile.php` by documenting the injected `CPrescia $core` context and initializing the by-reference `$ext` value before `locateFile()`. Focused PHPStan and PHP 8.3 syntax validation pass with no errors.
- Applied the `phpstan-legacy-remediation` skill to `prescia/lazyload/script.php`. Documented the injected `CPrescia $this` context, the `string $scriptname` parameter and the generic script-parameter array. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the global report decreased from 446 to 422 diagnostics.
Applied the `phpstan-legacy-remediation` skill to `prescia/plugins/bi_adm/module.php`, focusing on its 20 `variable.undefined` diagnostics. The module-loading context was documented as `CPrescia`; `$mname`, `$sname` and `$id` now have explicit safe initialization paths. Focused PHPStan leaves only the pre-existing `function.inner` and `class.nameCase` structural diagnostics for a later phase, while PHP 8.3 syntax validation passes.
Applied the `phpstan-legacy-remediation` skill to `prescia/lazyload/udm.php`. Confirmed `CPrescia::udm()` as the dynamic loader and documented the injected `CPrescia $this`, URL definition array and boolean virtual-folder flag. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 447 to 428.
Applied the `phpstan-legacy-remediation` skill to `prescia/plugins/bi_stats/payload/content/stats_ref.php`. Confirmed the dynamic `mod_bi_stats::onShow()` payload context and documented the injected `CPrescia $core`. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 428 to 409.
Applied the `phpstan-legacy-remediation` skill to `prescia/plugins/bi_stats/payload/content/stats_pathajax.php`. Confirmed the dynamic `mod_bi_stats::onShow()` payload context and documented the injected `CPrescia $core`. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 409 to 391.
Applied the `phpstan-legacy-remediation` skill to `pages/presciatester/actions/reset.php`. Confirmed the page action is included by the core action dispatcher and documented the injected `CPrescia $this` context. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 391 to 374.
Applied the `phpstan-legacy-remediation` skill to `prescia/plugins/bi_bb/payload/actions/default.php`. Confirmed `mod_bi_bb::onCheckActions()` as the dynamic loader and documented the injected `mod_bi_bb $this` context. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 374 to 357.
Applied the `phpstan-legacy-remediation` skill to `prescia/plugins/bi_bb/payload/content/default.php`. Confirmed `mod_bi_bb::onRender()` as the dynamic loader and documented the injected `CPrescia $core` and `mod_bi_bb $this` contexts. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 357 to 340.
Applied the `phpstan-legacy-remediation` skill to `prescia/lazyload/prepareMail.php`. Confirmed `CPrescia::prepareMail()` as the dynamic loader, documented the core, template-name and fill-array contracts, and initialized the mail body buffer before both template branches. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 340 to 325.
- Consolidated the PHPStan undefined-variable remediation batch across `bi_bb` payloads, `bi_stats` payloads, `bi_adm/module.php`, page actions and lazy-load files. The corrected files have focused PHPStan and PHP 8.3 syntax validation documented in `docs/RELATORIO_PHPSTAN_PROGRESSO.md`; the batch accounts for 233 resolved `variable.undefined` diagnostics without expanding the baseline.
Applied the `phpstan-legacy-remediation` skill to `prescia/lazyload/readfile.php`. Confirmed `CPrescia::readfile($file, $ext, $exit, $filename, $forceAttach, $cachetime)` as the dynamic loader and documented all injected parameter contracts plus `CPrescia $this`. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 325 to 311.
Applied the `phpstan-legacy-remediation` skill to `pages/prescia/_config/config.php`. Confirmed that `CPrescia` requires the site configuration from `CPrescia::loadDomain()` and documented the injected `CPrescia $this` context. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 311 to 299.
Applied the `phpstan-legacy-remediation` skill to `prescia/lazyload/ajaxQuery.php`. Confirmed that `CPrescia::checkActions()` includes the AJAX query lazy-load and documented the injected `CPrescia $this` context. Focused PHPStan and PHP 8.3 syntax validation pass with no errors; the current global diagnostic count decreased from 299 to 287.
- Recorded the latest PHPStan global milestone in `docs/RELATORIO_PHPSTAN_PROGRESSO.md` and `docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`: the level-1 repository analysis now reports **287 diagnostics**, down from 299 after the `ajaxQuery.php` remediation. Focused validation remains green for the corrected files, while the baseline remains unchanged.
- Analyzed and planned the next structural PHPStan batch for the 46 `return.missing` diagnostics in `tools/phpstan-framework-stubs.php`. The diagnostics come from empty stub bodies with non-void declarations; the planned remediation will add side-effect-free sentinel returns in waves, preserve the public signatures, keep the three `void` stubs unchanged and avoid baseline expansion.
- Added the detailed inventory of the 82 remaining PHPStan diagnostics, separating dynamic `$this`/`$core` contracts, manifest `$sname` flows, local variables and structural symbol diagnostics without expanding the baseline.
- Documented and remediated the six remaining BI module `$this` context diagnostics in `bi_bb`, `bi_cms`, `bi_groups`, `bi_seo` and `bi_stats`, based on the confirmed `CPrescia::addPlugin()` loader contract.
- Completed the BI module context sub-batch and initialized the `bi_bb` template frame, reducing the global PHPStan report from 82 to 75 diagnostics while keeping the baseline unchanged.
- Documented the `CPrescia` context in the `bi_labels` label-test payload and the unique-query AJAX lazyloader, reducing the global PHPStan report from 75 to 65 diagnostics.
- Documented the `$sname` manifest context in the BI Auth, Labels, Permissions and Stats plugins, reducing the global PHPStan report from 65 to 58 diagnostics without baseline changes.
- Documented the BI Stats AJAX core context and the administrative manifest `$sname` context, reducing the global PHPStan report from 58 to 53 diagnostics without baseline changes.
- Corrected `coreFull.php` variable flows and replaced the nested recursive `dieFreakingThumbs()` function with a closure, reducing the global PHPStan report from 53 to 48 diagnostics without baseline changes.
- Documented the `CPrescia` context in the default action/content payloads for `presciatester` and `prescia`, reducing the global PHPStan report from 48 to 38 diagnostics without baseline changes.
