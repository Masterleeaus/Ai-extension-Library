# Mobile Apps

This directory is the root workspace for Titan mobile applications.

## Planned structure

- `titan-hub/` — customer-facing Flutter application derived from QRPay User App v5.1.0.
- `mobilekit-reference/` — design/reference extraction from the licensed MobileKit Bootstrap 4 UI kit. HTML/CSS assets are reference material and must be recreated as native Flutter widgets rather than embedded into the Flutter runtime.

## Licensed donor archives

The source archives are retained outside Git history until they are imported through an approved binary/source workflow:

- QRPay Flutter User App v5.1.0
- MobileKit Bootstrap 4 UI Kit

Do not commit embedded credentials, signing keys, `.env` files, Firebase service files, OAuth private keys, or vendor build artefacts.
