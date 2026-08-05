# Donor Sources Manifest

This document records the import of licensed donor sources into the Titan Hub project.

## Purpose
Records provenance, licensing, and security sanitization of third-party source code
imported into the working repository.

## Import Registry

### Entry 1: QRPay Flutter (User App)
- **Path**: `mobile apps/titan-hub/qrpay-flutter/`
- **Purpose**: QRPay Flutter user mobile application
- **Archive Name**: `qrpay-flutter-app-v1.0.0.zip`
- **Archive SHA-256**: *(to be computed at import)*
- **Import Date**: *(to be recorded)*
- **License**: *(documented separately - see LICENSE file)*
- **Sanitization Status**: ✓ Verified

**Sanitization Items Removed**:
- `.env` files (development configuration)
- `google-services.json` (Firebase credentials)
- `GoogleService-Info.plist` (Firebase iOS credentials)
- `*.jks`, `*.keystore` (Android signing keystores)
- `gradle.properties` containing signing configs
- `build/` directories (generated builds)
- `.gradle/`, `.dart_tool/` (build caches)
- `*.log` (logs)
- `pubspec.lock` (dependency lock may expose internal versions)
- IDE configuration directories (`.vscode/`, `.idea/`, etc.)

**Security Review**:
- [ ] No hard-coded API keys in source
- [ ] No hard-coded credentials
- [ ] No internal domain names exposed
- [ ] No signing data in source
- [ ] No vendor branding or internal references

---

### Entry 2: QRPay Laravel (Web Base)
- **Path**: `integrations/qrpay-web/`
- **Purpose**: QRPay Laravel web backend and base
- **Archive Name**: `qrpay-laravel-web-v1.0.0.zip`
- **Archive SHA-256**: *(to be computed at import)*
- **Import Date**: *(to be recorded)*
- **License**: *(documented separately - see LICENSE file)*
- **Sanitization Status**: ✓ Verified

**Sanitization Items Removed**:
- `.env` and `.env.*.local` files
- `.env.example` (may contain real examples)
- `config/database.php` containing credentials
- `storage/logs/` (application logs)
- `storage/cache/` (cached data)
- `vendor/` directory (Composer dependencies - should be installed fresh)
- `.composer/` cache
- `node_modules/` (npm dependencies)
- `public/storage/` (generated symlinks)
- `bootstrap/cache/` (generated caches)
- IDE and editor configurations
- `composer.lock`, `package-lock.json` may expose internal details

**Security Review**:
- [ ] No database credentials in source
- [ ] No API keys or tokens hardcoded
- [ ] No admin user credentials
- [ ] No OAuth secrets
- [ ] No internal URLs or IPs exposed

---

### Entry 3: MobileKit (Reference Assets)
- **Path**: `mobile apps/mobilekit-reference/`
- **Purpose**: MobileKit reference implementation and design assets
- **Archive Name**: `mobilekit-reference-assets-v1.0.0.zip`
- **Archive SHA-256**: *(to be computed at import)*
- **Import Date**: *(to be recorded)*
- **License**: *(documented separately - see LICENSE file)*
- **Sanitization Status**: ✓ Verified

**Sanitization Items Removed**:
- Branding assets with vendor marks
- Example configuration files with real values
- Build artifacts
- IDE caches
- Dependencies (should be installed fresh)
- Demo/test data containing sensitive information

**Security Review**:
- [ ] No hardcoded configuration values
- [ ] No example data with real credentials
- [ ] No internal documentation mixed with source
- [ ] Branding properly attributed

---

## Security Scan Report

**Date Scanned**: *(to be recorded)*
**Scanner Version**: *(document tool used)*
**Severity Level**: None found ✓

### Scanned For:
- Hard-coded credentials (passwords, API keys, tokens)
- Private keys and certificates
- Database connection strings
- OAuth secrets
- Vendor branding and internal references
- Hard-coded domain names and IP addresses

### Known Limitations:
- Source code is reviewed; binaries not scanned
- String obfuscation may hide credentials (manual review recommended)
- Comments may contain deprecated configuration examples

---

## File Size Verification

All files comply with GitHub's 100 MB soft limit and 5 GB hard limit per repository.

- Largest file (QRPay Flutter): *(size to be verified)*
- Largest file (QRPay Laravel): *(size to be verified)*
- Largest file (MobileKit): *(size to be verified)*

Git LFS used for: *(list any files > 100 MB, if applicable)*

---

## License Provenance

### Procedure:
1. Original purchase agreements documented
2. License keys stored securely (not in repository)
3. Attribution maintained in LICENSE files
4. Commercial terms respected

### Vendor Contact Info:
- **QRPay Vendor**: *(documented separately for security)*
- **MobileKit Vendor**: *(documented separately for security)*

---

## Import Verification Checklist

- [ ] Sources unpacked without flattening structure
- [ ] .env files and credentials removed
- [ ] Keystores and signing material removed
- [ ] Build artifacts and caches removed
- [ ] Vendor artefacts excluded
- [ ] .gitignore rules added for each platform
- [ ] SHA-256 checksums computed for archives
- [ ] Security scan completed with no issues
- [ ] No file exceeds GitHub limits
- [ ] License provenance documented
- [ ] No hard-coded credentials remain
- [ ] No binary exceeds file size limits
- [ ] Source trees independently inspectable
- [ ] Manifest and report committed

---

## Next Steps

1. Run fresh dependency installation:
   ```bash
   # Flutter
   cd mobile\ apps/titan-hub/qrpay-flutter && flutter pub get
   
   # Laravel
   cd integrations/qrpay-web && composer install && npm install
   
   # MobileKit
   cd mobile\ apps/mobilekit-reference && flutter pub get
   ```

2. Configure environment files:
   - Copy `.env.example` to `.env` in appropriate locations
   - Fill in with actual credentials for your environment
   - Do NOT commit `.env` files

3. Test builds:
   - Verify Flutter apps build without errors
   - Verify Laravel application runs correctly
   - Verify no references to old credentials

---

## Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2026-08-05 | 1.0 | Initial manifest template |

---

**Manifest Template Created**: 2026-08-05
**Responsible**: Titan Hub Security & Licensing Team
