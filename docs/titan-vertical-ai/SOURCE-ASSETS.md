# Titan Vertical AI Source Assets

The uploaded donor extensions, onboarding catalogues, scan packs and deep-scan reports are preserved as unlisted MiniUp project assets. The branch records site URLs and relative asset paths rather than committing large binary archives to Git.

## Common source bundle and architecture documents

- MiniUp project: `https://crooked-juice.miniup.app`
- Source ZIP: `downloads/Titan-Vertical-AI-Source-Inputs.zip`
- Source ZIP SHA-256: `5ebe36bef29d939a7f41a7d37320df2a60fe6e2055d6770f743eeb48ebd9eda8`
- Architecture source: `docs/titan-vertical-ai/FOUNDATION.md`
- Implementation-plan source: `docs/superpowers/plans/2026-08-05-titan-vertical-ai-context-engine.md`

The bundle contains the vertical-engine donor extensions, OnboardingPro, wizard question catalogues, scan packs, deep-scan reports and the original integrated-archive checksum. The two Markdown files are the verified sources used for issue #302.

## Integrated MagicAI and WorkCore onboarding archive

Original archive:

- Name: `MagicAI-v10.91-WorkCore-CLEANING-ONBOARDING-INTEGRATED(1).zip`
- Bytes: `31,878,490`
- SHA-256: `19f467c118870f9a436ed087da79e34d06e959e23ecc62a0c340025fe992cb4c`

MiniUp project-file limits require the source to be carried in eight ordered payload parts. Each downloadable ZIP wrapper contains one payload part and a local README.

| Payload parts | MiniUp project | Project files |
|---|---|---|
| 01–02 | `https://abiding-accountant.miniup.app` | `downloads/MagicAI-Onboarding-Integrated-part01.zip`, `downloads/MagicAI-Onboarding-Integrated-part02.zip` |
| 03–04 | `https://acoustic-london.miniup.app` | `downloads/MagicAI-Onboarding-Integrated-part03.zip`, `downloads/MagicAI-Onboarding-Integrated-part04.zip` |
| 05–06 | `https://busy-toothbrush.miniup.app` | `downloads/MagicAI-Onboarding-Integrated-part05.zip`, `downloads/MagicAI-Onboarding-Integrated-part06.zip` |
| 07–08 | `https://billions-sweden.miniup.app` | `downloads/MagicAI-Onboarding-Integrated-part07.zip`, `downloads/MagicAI-Onboarding-Integrated-part08.zip` |

### Reassemble on Linux or macOS

Extract every wrapper, then run:

```bash
cat \
  MagicAI-Onboarding-Integrated.part01 \
  MagicAI-Onboarding-Integrated.part02 \
  MagicAI-Onboarding-Integrated.part03 \
  MagicAI-Onboarding-Integrated.part04 \
  MagicAI-Onboarding-Integrated.part05 \
  MagicAI-Onboarding-Integrated.part06 \
  MagicAI-Onboarding-Integrated.part07 \
  MagicAI-Onboarding-Integrated.part08 \
  > MagicAI-v10.91-WorkCore-CLEANING-ONBOARDING-INTEGRATED.zip

sha256sum MagicAI-v10.91-WorkCore-CLEANING-ONBOARDING-INTEGRATED.zip
```

### Reassemble in PowerShell

Extract every wrapper, then run:

```powershell
$out = [System.IO.File]::Create('MagicAI-v10.91-WorkCore-CLEANING-ONBOARDING-INTEGRATED.zip')
1..8 | ForEach-Object {
    $path = 'MagicAI-Onboarding-Integrated.part{0:D2}' -f $_
    $bytes = [System.IO.File]::ReadAllBytes($path)
    $out.Write($bytes, 0, $bytes.Length)
}
$out.Close()

Get-FileHash .\MagicAI-v10.91-WorkCore-CLEANING-ONBOARDING-INTEGRATED.zip -Algorithm SHA256
```

The resulting hash must be:

```text
19f467c118870f9a436ed087da79e34d06e959e23ecc62a0c340025fe992cb4c
```

## Machine-readable manifest

See [`source-assets.json`](source-assets.json) for site IDs, relative paths, byte lengths and per-part SHA-256 values.
