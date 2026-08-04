# Titan Hub Donor Manifest

Generated from the supplied licensed archives while beginning issue #254.

| Donor | Target | Archive size | SHA-256 |
|---|---|---:|---|
| QRPay Flutter User App v5.1.0 | `mobile apps/titan-hub/` | ~2.8 MB | `77d00027b7771e219ac1a74fbea4b7e1029e905d36afe3fcb97d1b2f557f6ec8` |
| QRPay Laravel Web v5.1.0 | `integrations/qrpay-web/` | ~54 MB | `412765575258c3c17f9da652d225306b5cbab3cd69f4953b65e02a4980693037` |
| MobileKit Bootstrap 4 UI Kit | `mobile apps/mobilekit-reference/` | ~3.9 MB | `a2f9bda06a35a2d82217692bdff736a8b901cef795422fc98f5816f667c6a736` |
| Cryptomus extension | future Titan Pay crypto adapter | archive supplied | `5ad8aa0794438070f1c550cbaf8f2bba0935950bda3126d64488b14475e17bb4` |

## Confirmed sensitive/generated paths requiring exclusion or replacement

### QRPay Flutter

- `android/key.properties`
- `android/app/key.jks`
- `android/app/google-services.json`
- `ios/Runner/GoogleService-Info.plist`
- `__MACOSX/` metadata

These files must not be imported as production Titan credentials. New Titan-owned Android/iOS signing and Firebase configuration must be generated outside Git.

### QRPay Laravel

- `.env`
- `storage/oauth-private.key`
- `storage/oauth-public.key`

`.env.example` may be retained only after confirming it contains placeholders rather than live values. Published framework view overrides under `resources/views/vendor/` are source files and are not equivalent to Composer's root `vendor/` directory.

## Import policy

1. Verify each archive hash before extraction.
2. Extract into a temporary directory.
3. Remove credentials, signing material, generated output, caches, logs, macOS metadata, and root dependency vendor directories.
4. Scan source for hard-coded secrets, domains, tokens, API keys, and logging of sensitive request/response bodies.
5. Copy only sanitised source into the requested repository folders.
6. Commit the manifest, scan report, and source separately where practical.
7. Rotate every credential contained in an archive before any deployment, regardless of whether it appears to be demo data.

## Transport note

MiniUp publishes static web projects and datasets; it is not a Git repository transport. The connected GitHub API can create branches, issues, and text commits, but cannot stream the 54 MB donor archive from this session. Binary/source import therefore requires either a local authenticated Git push, a Git LFS workflow, or a future connector action capable of uploading repository files from mounted paths.
