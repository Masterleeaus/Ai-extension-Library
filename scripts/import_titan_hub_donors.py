#!/usr/bin/env python3
"""Verify, decrypt, extract, and scan the sanitised Titan Hub donor bundle."""
from __future__ import annotations

import base64
import hashlib
import io
import json
import os
import re
import shutil
import tarfile
import tempfile
import urllib.request
from pathlib import Path, PurePosixPath

import pyarrow.parquet as pq
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

ROOT = Path(".").resolve()
TARGETS = (
    Path("mobile apps/titan-hub"),
    Path("integrations/qrpay-web"),
    Path("mobile apps/mobilekit-reference"),
)
APPROVED_PREFIXES = (
    PurePosixPath("mobile apps/titan-hub"),
    PurePosixPath("mobile apps/mobilekit-reference"),
    PurePosixPath("integrations/qrpay-web"),
    PurePosixPath("docs/titan-hub"),
)


def fail(message: str) -> None:
    raise SystemExit(message)


def download_dataset(url: str, target: Path) -> None:
    request = urllib.request.Request(url, headers={"User-Agent": "titan-hub-donor-import/1.1"})
    with urllib.request.urlopen(request, timeout=300) as response, target.open("wb") as output:
        shutil.copyfileobj(response, output, length=1024 * 1024)


def read_ciphertext(dataset: Path) -> bytes:
    columns = [
        "Sequence",
        "Payload_Base64",
        "Total_Chunks",
        "Ciphertext_Sha256",
        "Ciphertext_Bytes",
        "Plaintext_Sha256",
        "Plaintext_Bytes",
        "Nonce_Base64",
        "Algorithm",
        "AAD",
    ]
    data = pq.read_table(dataset, columns=columns).to_pydict()
    rows = sorted(zip(*(data[column] for column in columns)), key=lambda row: int(row[0]))
    expected_chunks = int(os.environ["EXPECTED_CHUNKS"])
    if [int(row[0]) for row in rows] != list(range(expected_chunks)):
        fail("Transport chunks are not contiguous")

    metadata = [
        ({int(row[index]) for row in rows} if index in {2, 4, 6} else {str(row[index]) for row in rows})
        for index in range(2, 10)
    ]
    if any(len(values) != 1 for values in metadata):
        fail("Transport metadata differs between chunks")
    if metadata[0] != {expected_chunks}:
        fail("Transport chunk count mismatch")
    if metadata[6] != {"AES-256-GCM"} or metadata[7] != {"titan-hub-issue-254"}:
        fail("Unexpected transport encryption metadata")

    ciphertext = b"".join(base64.b64decode(str(row[1]), validate=True) for row in rows)
    if len(ciphertext) != int(os.environ["EXPECTED_CIPHERTEXT_BYTES"]):
        fail("Ciphertext byte count mismatch")
    if hashlib.sha256(ciphertext).hexdigest() != os.environ["EXPECTED_CIPHERTEXT_SHA256"]:
        fail("Ciphertext SHA-256 mismatch")
    return ciphertext


def decrypt(ciphertext: bytes) -> bytes:
    key = Path("/tmp/titan-hub-import-aes.key").read_bytes()
    if len(key) != 32:
        fail("Unwrapped AES key must be 32 bytes")
    nonce = base64.b64decode(os.environ["EXPECTED_NONCE_B64"], validate=True)
    plaintext = AESGCM(key).decrypt(nonce, ciphertext, b"titan-hub-issue-254")
    if len(plaintext) != int(os.environ["EXPECTED_PLAINTEXT_BYTES"]):
        fail("Plaintext byte count mismatch")
    if hashlib.sha256(plaintext).hexdigest() != os.environ["EXPECTED_PLAINTEXT_SHA256"]:
        fail("Plaintext SHA-256 mismatch")
    return plaintext


def extract(plaintext: bytes) -> None:
    for relative in TARGETS:
        target = ROOT / relative
        if target.exists():
            shutil.rmtree(target)

    with tarfile.open(fileobj=io.BytesIO(plaintext), mode="r:gz") as archive:
        members: list[tarfile.TarInfo] = []
        for member in archive.getmembers():
            path = PurePosixPath(member.name)
            if not path.parts or str(path) in {".", ""}:
                continue
            if path.is_absolute() or ".." in path.parts:
                fail(f"Unsafe archive path: {member.name!r}")
            if member.issym() or member.islnk() or member.isdev():
                fail(f"Unsupported archive member: {member.name!r}")
            if not any(path == prefix or prefix in path.parents for prefix in APPROVED_PREFIXES):
                fail(f"Archive path outside approved targets: {member.name!r}")
            members.append(member)
        archive.extractall(ROOT, members=members, filter="data")


def verify_required_paths() -> None:
    required = (
        Path("mobile apps/titan-hub/pubspec.yaml"),
        Path("mobile apps/titan-hub/lib/main.dart"),
        Path("integrations/qrpay-web/composer.json"),
        Path("integrations/qrpay-web/artisan"),
        Path("integrations/qrpay-web/routes/web.php"),
        Path("integrations/qrpay-web/database/migrations"),
        Path("mobile apps/mobilekit-reference/HTML/index.html"),
        Path("docs/titan-hub/DONOR_MANIFEST.md"),
        Path("docs/titan-hub/DONOR_SECURITY_SCAN.md"),
        Path("docs/titan-hub/DONOR_SECURITY_SCAN.json"),
    )
    missing = [str(path) for path in required if not path.exists()]
    if missing:
        fail("Missing imported donor paths: " + ", ".join(missing))


def verify_forbidden_files() -> None:
    forbidden_names = {
        ".env",
        "key.properties",
        "key.jks",
        "google-services.json",
        "GoogleService-Info.plist",
        "oauth-private.key",
        "oauth-public.key",
    }
    forbidden_suffixes = {
        ".jks",
        ".keystore",
        ".p12",
        ".pfx",
        ".p8",
        ".pem",
        ".mobileprovision",
        ".provisionprofile",
    }
    forbidden_dirs = {
        "__MACOSX",
        ".dart_tool",
        ".gradle",
        "node_modules",
        "vendor",
        ".idea",
        ".vscode",
        "DerivedData",
    }
    findings: list[str] = []
    for source_root in TARGETS:
        for path in source_root.rglob("*"):
            if path.is_dir() and path.name in forbidden_dirs:
                findings.append(str(path))
            if path.is_file() and (
                path.name in forbidden_names
                or path.suffix.lower() in forbidden_suffixes
                or (path.name.startswith(".env") and path.name != ".env.example")
            ):
                findings.append(str(path))
    if findings:
        fail("Forbidden imported files/directories: " + ", ".join(findings[:30]))


def verify_credentials() -> None:
    patterns = {
        "private_key": re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
        "google_api_key": re.compile(r"AIza[0-9A-Za-z_-]{30,}"),
        "aws_access_key": re.compile(r"AKIA[0-9A-Z]{16}"),
        "gateway_secret": re.compile(r"\b(?:sk_live|sk_test|sk-proj)-?[A-Za-z0-9_-]{16,}"),
        "jwt": re.compile(r"\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}"),
        "flutterwave_key": re.compile(r"FLW(?:SEC|PUB)K[_A-Za-z0-9-]{12,}"),
    }
    binary_suffixes = {
        ".png",
        ".jpg",
        ".jpeg",
        ".gif",
        ".webp",
        ".ico",
        ".pdf",
        ".zip",
        ".gz",
        ".xz",
        ".ttf",
        ".woff",
        ".woff2",
        ".eot",
        ".mp4",
        ".mov",
        ".sketch",
        ".lock",
    }
    findings: list[str] = []
    for source_root in TARGETS:
        for path in source_root.rglob("*"):
            if not path.is_file() or path.suffix.lower() in binary_suffixes or path.stat().st_size > 3_000_000:
                continue
            text = path.read_text(encoding="utf-8", errors="ignore")
            for name, pattern in patterns.items():
                match = pattern.search(text)
                if match:
                    line = text.count("\n", 0, match.start()) + 1
                    findings.append(f"{name}: {path}:{line}")
    if findings:
        fail("Credential-pattern findings: " + ", ".join(findings[:30]))


def verify_inventory() -> None:
    files = [path for source_root in TARGETS for path in source_root.rglob("*") if path.is_file()]
    oversized = [str(path) for path in files if path.stat().st_size > 95 * 1024 * 1024]
    if oversized:
        fail("Imported file exceeds 95 MiB: " + ", ".join(oversized))
    if len(files) < 2700:
        fail(f"Imported donor tree is unexpectedly small: {len(files)} files")
    report = json.loads(Path("docs/titan-hub/DONOR_SECURITY_SCAN.json").read_text(encoding="utf-8"))
    if report["scan"]["credential_findings"]:
        fail("Committed security report contains unresolved credential findings")
    print(f"Verified {len(files)} imported donor files")


def main() -> int:
    with tempfile.TemporaryDirectory(prefix="titan-hub-donor-") as temp_name:
        dataset = Path(temp_name) / "transport.geoparquet"
        download_dataset(os.environ["DATASET_URL"], dataset)
        plaintext = decrypt(read_ciphertext(dataset))
        extract(plaintext)
    verify_required_paths()
    verify_forbidden_files()
    verify_credentials()
    verify_inventory()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
