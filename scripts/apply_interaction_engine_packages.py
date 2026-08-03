#!/usr/bin/env python3
from __future__ import annotations

import argparse
import base64
import hashlib
import json
import shutil
import tarfile
import tempfile
import urllib.request
from pathlib import Path, PurePosixPath


def fail(message: str) -> None:
    raise SystemExit(message)


def within(path: PurePosixPath, root: PurePosixPath) -> bool:
    return path == root or root in path.parents


def download(url: str, target: Path) -> None:
    if not url.startswith("https://titan-builder-ob009-artifact.miniup.app/"):
        fail("dataset URL is not the approved MiniUp origin")
    request = urllib.request.Request(url, headers={"User-Agent": "interaction-engine-materializer/1.0"})
    with urllib.request.urlopen(request, timeout=300) as response, target.open("wb") as output:
        shutil.copyfileobj(response, output, length=1024 * 1024)


def reconstruct_archive(dataset: Path, archive_path: Path, expected_rows: int) -> tuple[str, int]:
    try:
        import pyarrow.parquet as pq
    except ImportError as error:
        fail(f"pyarrow is required to materialize the package dataset: {error}")

    table = pq.read_table(
        dataset,
        columns=["Sequence", "Payload_Base64", "Archive_Sha256", "Archive_Bytes"],
    )
    rows = sorted(
        zip(
            table.column("Sequence").to_pylist(),
            table.column("Payload_Base64").to_pylist(),
            table.column("Archive_Sha256").to_pylist(),
            table.column("Archive_Bytes").to_pylist(),
        ),
        key=lambda row: int(row[0]),
    )
    if len(rows) != expected_rows:
        fail(f"dataset row mismatch: expected {expected_rows}, got {len(rows)}")
    sequences = [int(row[0]) for row in rows]
    if sequences != list(range(len(rows))):
        fail(f"dataset chunks are not contiguous: {sequences}")
    archive_hashes = {str(row[2]) for row in rows}
    archive_sizes = {int(row[3]) for row in rows}
    if len(archive_hashes) != 1 or len(archive_sizes) != 1:
        fail("dataset archive metadata differs between chunks")

    with archive_path.open("wb") as output:
        for _, encoded, _, _ in rows:
            try:
                output.write(base64.b64decode(str(encoded), validate=True))
            except Exception as error:
                fail(f"archive chunk decode failed: {error}")

    digest = hashlib.sha256(archive_path.read_bytes()).hexdigest()
    size = archive_path.stat().st_size
    if digest != next(iter(archive_hashes)):
        fail("dataset chunk metadata SHA-256 does not match reconstructed archive")
    if size != next(iter(archive_sizes)):
        fail("dataset chunk metadata byte count does not match reconstructed archive")
    return digest, size


def main() -> None:
    parser = argparse.ArgumentParser(description="Materialize verified Titan Interaction Engine packages")
    parser.add_argument("--root", default=".")
    parser.add_argument("--manifest", default="transport/interaction-engine-packages-manifest.json")
    args = parser.parse_args()

    root = Path(args.root).resolve()
    manifest_path = (root / args.manifest).resolve()
    if manifest_path != root and root not in manifest_path.parents:
        fail("manifest path escapes repository root")
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))

    targets = [PurePosixPath(value) for value in manifest["targets"]]
    with tempfile.TemporaryDirectory(prefix="interaction-engine-") as temp_name:
        temp = Path(temp_name)
        dataset_path = temp / "packages.parquet"
        archive_path = temp / "packages.tar.gz"
        download(str(manifest["dataset_url"]), dataset_path)
        digest, archive_size = reconstruct_archive(
            dataset_path,
            archive_path,
            int(manifest["dataset_rows"]),
        )
        if archive_size != int(manifest["archive_bytes"]):
            fail(f"archive byte mismatch: expected {manifest['archive_bytes']}, got {archive_size}")
        if digest != manifest["archive_sha256"]:
            fail(f"archive SHA-256 mismatch: expected {manifest['archive_sha256']}, got {digest}")

        extract_root = temp / "extract"
        extract_root.mkdir()
        with tarfile.open(archive_path, "r:gz") as tar:
            members = tar.getmembers()
            for member in members:
                path = PurePosixPath(member.name)
                if path.is_absolute() or ".." in path.parts:
                    fail(f"unsafe archive path: {member.name}")
                if member.issym() or member.islnk() or member.isdev():
                    fail(f"unsupported archive member: {member.name}")
                if not any(within(path, target) for target in targets):
                    fail(f"archive member outside approved targets: {member.name}")
            tar.extractall(extract_root, filter="data")

        files = [path for path in extract_root.rglob("*") if path.is_file()]
        size = sum(path.stat().st_size for path in files)
        if len(files) != int(manifest["materialized_files"]):
            fail(f"materialized file mismatch: expected {manifest['materialized_files']}, got {len(files)}")
        if size != int(manifest["materialized_bytes"]):
            fail(f"materialized byte mismatch: expected {manifest['materialized_bytes']}, got {size}")

        for target in targets:
            source = extract_root.joinpath(*target.parts)
            destination = root.joinpath(*target.parts)
            if not source.is_dir():
                fail(f"missing materialized target: {target}")
            if destination.exists():
                if destination.is_dir():
                    shutil.rmtree(destination)
                else:
                    destination.unlink()
            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copytree(source, destination)

    print(
        f"Materialized Interaction Engine architecture: {manifest['materialized_files']} files, "
        f"{manifest['materialized_bytes']} bytes, SHA-256 {manifest['archive_sha256']}"
    )


if __name__ == "__main__":
    main()
