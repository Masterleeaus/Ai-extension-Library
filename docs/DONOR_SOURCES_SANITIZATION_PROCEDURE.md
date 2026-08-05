# Donor Sources Sanitization Procedure

**Purpose**: Step-by-step guide for importing and sanitizing third-party donor source code.

**Responsibility**: Security and Licensing Team

**Frequency**: Per each donor source import

---

## Pre-Import Checklist

- [ ] Verify archive integrity (checksum match)
- [ ] Confirm licensing agreement allows import
- [ ] Verify vendor contact and provenance
- [ ] Document purchase order and date
- [ ] Prepare isolated workspace for processing
- [ ] Backup original archive in secure location

---

## Step 1: Unpack Archives Without Flattening Structure

### Procedure
```bash
# Create base directory
mkdir -p mobile\ apps/titan-hub
mkdir -p mobile\ apps/mobilekit-reference
mkdir -p integrations/qrpay-web

# Extract QRPay Flutter (preserves directory structure)
unzip -q qrpay-flutter-app-v1.0.0.zip -d mobile\ apps/titan-hub/

# Extract QRPay Laravel (preserves directory structure)
unzip -q qrpay-laravel-web-v1.0.0.zip -d integrations/

# Extract MobileKit (preserves directory structure)
unzip -q mobilekit-reference-assets-v1.0.0.zip -d mobile\ apps/mobilekit-reference/
```

**Important**: Use `-q` (quiet) flag and do NOT flatten directory structure.

---

## Step 2: Remove Secrets and Credentials

### Section 2.1: Environment Files

```bash
# QRPay Flutter
find mobile\ apps/titan-hub -type f -name ".env*" -not -name ".env.example" -delete
find mobile\ apps/titan-hub -type f -name ".env.*.local" -delete
find mobile\ apps/titan-hub -type f -name ".envrc" -delete

# QRPay Laravel
find integrations/qrpay-web -type f -name ".env*" -not -name ".env.example" -delete
find integrations/qrpay-web -type f -name ".env.*.local" -delete
find integrations/qrpay-web -type f -name ".envrc" -delete

# MobileKit
find mobile\ apps/mobilekit-reference -type f -name ".env*" -not -name ".env.example" -delete
```

### Section 2.2: Credentials and Keys

```bash
# Private keys
find . -type f \( -name "*.pem" -o -name "*.key" -o -name "*.p8" -o -name "*.p12" -o -name "*.pfx" \) \
  -path "*/mobile apps/*" -o -path "*/integrations/*" | grep -v node_modules | xargs rm -f

# Firebase credentials
find . -type f -name "google-services.json" -path "*/mobile apps/*" -delete
find . -type f -name "GoogleService-Info.plist" -path "*/mobile apps/*" -delete
find . -type f -name "firebase-config.json" -path "*/mobile apps/*" -delete
find . -type f -name "firebase-*.json" -path "*/integrations/*" -delete

# OAuth and API secrets
find . -type f -name "credentials.json" -path "*/integrations/*" -delete
find . -type f -name "secrets.json" -path "*/integrations/*" -delete
find . -type f -name "oauth*.json" -path "*/integrations/*" -delete
```

### Section 2.3: Android Signing Material

```bash
# Android keystores and signing configs
find mobile\ apps/titan-hub -type f \( -name "*.jks" -o -name "*.keystore" -o -name "*.p12" \) -delete
find mobile\ apps/titan-hub -name "key.properties" -delete
find mobile\ apps/titan-hub -name "gradle.properties" -exec grep -L "^org.gradle" {} \; | xargs -I {} sh -c 'rm "{}" 2>/dev/null || true'
```

### Section 2.4: Build Artifacts and Caches

```bash
# Flutter
find mobile\ apps/titan-hub -type d -name ".dart_tool" -exec rm -rf {} + 2>/dev/null || true
find mobile\ apps/titan-hub -type d -name "build" -exec rm -rf {} + 2>/dev/null || true
find mobile\ apps/titan-hub -type f -name "pubspec.lock" -delete
find mobile\ apps/titan-hub -type d -name ".gradle" -exec rm -rf {} + 2>/dev/null || true

# iOS
find mobile\ apps/titan-hub/ios -type d -name "Pods" -exec rm -rf {} + 2>/dev/null || true
find mobile\ apps/titan-hub/ios -type f -name "Podfile.lock" -delete
find mobile\ apps/titan-hub/ios -type d -name "Flutter/ephemeral" -exec rm -rf {} + 2>/dev/null || true

# Laravel
find integrations/qrpay-web -type d -name "vendor" -exec rm -rf {} + 2>/dev/null || true
find integrations/qrpay-web -type d -name "node_modules" -exec rm -rf {} + 2>/dev/null || true
find integrations/qrpay-web -type d -name ".composer" -exec rm -rf {} + 2>/dev/null || true
find integrations/qrpay-web -type f -name "composer.lock" -delete
find integrations/qrpay-web -type f -name "package-lock.json" -delete
find integrations/qrpay-web -type d -name "bootstrap/cache" -exec rm -rf {} + 2>/dev/null || true
find integrations/qrpay-web -type d -name "storage" -exec rm -rf {} + 2>/dev/null || true
```

### Section 2.5: IDE and Editor Configs

```bash
# IDE directories
find mobile\ apps -type d \( -name ".idea" -o -name ".vscode" -o -name ".atom" \) -exec rm -rf {} + 2>/dev/null || true
find integrations -type d \( -name ".idea" -o -name ".vscode" -o -name ".atom" \) -exec rm -rf {} + 2>/dev/null || true

# IDE and editor files
find . -path "*/mobile apps/*" -o -path "*/integrations/*" \( \
  -name "*.sublime-workspace" \
  -o -name "*.sublime-project" \
  -o -name "*.xcworkspace" \
  -o -name ".DS_Store" \
  -o -name "Thumbs.db" \
\) -delete 2>/dev/null || true
```

### Section 2.6: Log Files

```bash
find mobile\ apps -type f -name "*.log" -delete
find integrations -type f -name "*.log" -delete
find mobile\ apps -type d -name "logs" -o -name "log" -exec rm -rf {} + 2>/dev/null || true
find integrations -type d -name "logs" -o -name "log" -exec rm -rf {} + 2>/dev/null || true
```

---

## Step 3: Verify Removal Completeness

```bash
# Search for common credential patterns
echo "=== Searching for hard-coded credentials ==="
grep -r "apiKey\|api_key\|password\|secret\|token\|AWS_\|GOOGLE_\|FIREBASE_" \
  mobile\ apps/titan-hub integrations/qrpay-web mobile\ apps/mobilekit-reference \
  --include="*.dart" --include="*.java" --include="*.swift" --include="*.php" \
  --include="*.js" --include="*.ts" --include="*.json" \
  2>/dev/null | head -20

# Search for hard-coded URLs
echo "=== Searching for hard-coded URLs ==="
grep -r "http://\|https://" \
  mobile\ apps/titan-hub integrations/qrpay-web mobile\ apps/mobilekit-reference \
  --include="*.dart" --include="*.java" --include="*.swift" --include="*.php" \
  --include="*.js" --include="*.ts" \
  2>/dev/null | grep -v "pub.dev\|github.com\|example.com" | head -20
```

**Action**: Review any results and remove/obscure sensitive information.

---

## Step 4: Add .gitignore Rules

```bash
# Copy comprehensive .gitignore rules
cp .gitignore-donor-sources mobile\ apps/titan-hub/.gitignore
cp .gitignore-donor-sources integrations/qrpay-web/.gitignore
cp .gitignore-donor-sources mobile\ apps/mobilekit-reference/.gitignore

# Add directory-level ignores for sensitive patterns
echo ".env*
!.env.example
.env.*.local
.env.*.php
secrets.json
credentials.json
*.jks
*.keystore
*.p12
google-services.json
GoogleService-Info.plist
*.log
build/
.gradle/
vendor/
node_modules/
" >> mobile\ apps/titan-hub/.gitignore
```

---

## Step 5: Compute SHA-256 Checksums

```bash
# Compute checksums for original archives (before extraction)
echo "=== Original Archive Checksums ==="
sha256sum qrpay-flutter-app-v1.0.0.zip
sha256sum qrpay-laravel-web-v1.0.0.zip
sha256sum mobilekit-reference-assets-v1.0.0.zip

# Compute checksums for sanitized source trees
echo "=== Sanitized Source Tree Checksums ==="
find mobile\ apps/titan-hub -type f | sort | xargs sha256sum > titan-hub.sha256
find integrations/qrpay-web -type f | sort | xargs sha256sum > qrpay-web.sha256
find mobile\ apps/mobilekit-reference -type f | sort | xargs sha256sum > mobilekit-reference.sha256

# Verify integrity
sha256sum -c titan-hub.sha256
sha256sum -c qrpay-web.sha256
sha256sum -c mobilekit-reference.sha256
```

**Output**: Record checksums in `docs/DONOR_SOURCES_MANIFEST.md`

---

## Step 6: Security Scan

### 6.1: Manual Code Review

```bash
# Review for secrets
for pattern in "password\|apiKey\|api_key\|secret\|token\|AWS_\|GOOGLE_"; do
  echo "=== Checking for $pattern ==="
  grep -r "$pattern" mobile\ apps/titan-hub integrations/qrpay-web mobile\ apps/mobilekit-reference \
    --include="*.dart" --include="*.java" --include="*.php" --include="*.js" 2>/dev/null | head -5
done
```

### 6.2: Generate Security Scan Report

**Template** (save as `SECURITY_SCAN_REPORT.md`):
```markdown
# Security Scan Report

**Date**: [YYYY-MM-DD]
**Scanner**: Manual code review + grep pattern matching
**Status**: PASSED ✓

## Items Checked
- [ ] No hard-coded credentials (passwords, API keys, tokens)
- [ ] No private keys or certificates
- [ ] No database connection strings
- [ ] No OAuth secrets
- [ ] No vendor branding in source
- [ ] No hard-coded domain names
- [ ] No hard-coded IP addresses
- [ ] No .env files
- [ ] No keystores or signing material
- [ ] No build artifacts
- [ ] No IDE configurations
- [ ] No credential files (google-services.json, etc.)

## Findings
None found ✓

## Exclusions
- Binary files not scanned (present: [list or "none"])
- Dependencies excluded (vendor/, node_modules/)
- Generated files excluded

## Sign-off
- Scanned by: [Name]
- Date: [Date]
- Approved by: [Name]
```

---

## Step 7: Document License Provenance

Create `LICENSE-DONOR-SOURCES.txt`:
```
DONOR SOURCES LICENSING INFORMATION

This repository contains licensed third-party source code:

1. QRPay Flutter (mobile apps/titan-hub/qrpay-flutter/)
   License: [Specify license type]
   Vendor: QRPay Inc.
   Purchase Date: [Date]
   License Valid Until: [Date or "Perpetual"]
   Contact: [Vendor contact info for license questions]

2. QRPay Laravel (integrations/qrpay-web/)
   License: [Specify license type]
   Vendor: QRPay Inc.
   Purchase Date: [Date]
   License Valid Until: [Date or "Perpetual"]
   Contact: [Vendor contact info]

3. MobileKit Reference (mobile apps/mobilekit-reference/)
   License: [Specify license type]
   Vendor: [MobileKit vendor]
   Purchase Date: [Date]
   License Valid Until: [Date or "Perpetual"]
   Contact: [Vendor contact info]

IMPORTANT: Do NOT publish license keys, vendor credentials, or commercial terms.
License agreements available to authorized personnel upon request from [Security Team].
```

---

## Step 8: Verify File Sizes

```bash
# Check for files > 100 MB (GitHub soft limit)
echo "=== Files > 100 MB ==="
find mobile\ apps integrations -type f -size +100M

# Check for files > 5 GB (GitHub hard limit)
echo "=== Files > 5 GB ==="
find mobile\ apps integrations -type f -size +5G

# Check total size
du -sh mobile\ apps/titan-hub
du -sh integrations/qrpay-web
du -sh mobile\ apps/mobilekit-reference
```

**Action**: If files > 100 MB exist and aren't already in LFS, add to `.gitattributes`:
```
*.so filter=lfs diff=lfs merge=lfs -text
*.dylib filter=lfs diff=lfs merge=lfs -text
*.dll filter=lfs diff=lfs merge=lfs -text
```

---

## Step 9: Final Verification Checklist

- [ ] Sources unpacked without flattening
- [ ] All .env files removed (except .env.example)
- [ ] All private keys removed
- [ ] All keystores/signing material removed
- [ ] All build artifacts cleaned
- [ ] All IDE configs removed
- [ ] All logs removed
- [ ] .gitignore rules added to directories
- [ ] SHA-256 checksums computed
- [ ] Security scan completed with no findings
- [ ] License provenance documented
- [ ] No file exceeds GitHub limits
- [ ] Manifest updated with import details
- [ ] Source trees independently inspectable
- [ ] Ready for commit

---

## Step 10: Commit to Git

```bash
git add mobile\ apps/titan-hub
git add integrations/qrpay-web
git add mobile\ apps/mobilekit-reference
git add docs/DONOR_SOURCES_MANIFEST.md
git add docs/SECURITY_SCAN_REPORT.md
git add LICENSE-DONOR-SOURCES.txt

git commit -m "Import and sanitize donor sources (QRPay Flutter, Laravel, MobileKit)

- Sanitized QRPay Flutter user app
- Sanitized QRPay Laravel web backend
- Sanitized MobileKit reference assets
- Removed all credentials, keys, and build artifacts
- Added comprehensive .gitignore rules
- Documented license provenance
- Security scan completed with no findings

Manifest: docs/DONOR_SOURCES_MANIFEST.md
Scan Report: docs/SECURITY_SCAN_REPORT.md
License Info: LICENSE-DONOR-SOURCES.txt"
```

---

## Troubleshooting

### Issue: Large files in archive
**Solution**: Pre-identify large files and setup Git LFS before import

### Issue: Intermixed credentials in source code
**Solution**: Manual code review and sanitization; document findings

### Issue: Deeply nested build artifacts
**Solution**: Use `find` with `-exec rm -rf` to recursively remove

### Issue: Cannot remove files due to permissions
**Solution**: Use `chmod 755` before attempting removal

---

## References

- [GitHub File Size Limits](https://docs.github.com/en/repositories/working-with-files/managing-large-files)
- [Git LFS Documentation](https://git-lfs.github.com/)
- [Flutter Project Structure](https://flutter.dev/docs/development/best-practices/layout-conv)
- [Laravel Project Structure](https://laravel.com/docs/structure)
- [OWASP: Secrets Scanning](https://owasp.org/www-community/attacks/Sensitive_Data_Exposure)

---

**Document Version**: 1.0  
**Last Updated**: 2026-08-05  
**Maintained By**: Titan Hub Security Team
