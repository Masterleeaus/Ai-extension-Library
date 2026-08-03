#!/usr/bin/env python3
"""Restore, verify, and optionally overlay the lossless AI extension library."""
from __future__ import annotations

import argparse
import base64
import csv
import hashlib
import json
import pathlib
import shutil
import stat
import tempfile
import urllib.request
import zipfile
from collections import defaultdict
from typing import Any
import zlib


def download(url: str, target: pathlib.Path) -> None:
    request = urllib.request.Request(url, headers={"User-Agent": "ai-extensions-materializer/2.0"})
    with urllib.request.urlopen(request, timeout=300) as response, target.open("wb") as output:
        shutil.copyfileobj(response, output, length=1024 * 1024)


def safe_target(root: pathlib.Path, relative: str) -> pathlib.Path:
    rel = pathlib.PurePosixPath(relative)
    if rel.is_absolute() or ".." in rel.parts or not rel.parts or rel.parts[0] != "extensions":
        raise ValueError(f"Unsafe dataset path: {relative!r}")
    target = (root / pathlib.Path(*rel.parts)).resolve()
    expected = (root / "extensions").resolve()
    if target != expected and expected not in target.parents:
        raise ValueError(f"Path escapes extensions root: {relative!r}")
    return target


def read_manifest(extension_dir: pathlib.Path) -> dict[str, object]:
    manifest_path = extension_dir / "extension.json"
    if not manifest_path.exists():
        return {}
    try:
        value = json.loads(manifest_path.read_text(encoding="utf-8-sig"))
    except (UnicodeDecodeError, json.JSONDecodeError):
        return {}
    return value if isinstance(value, dict) else {}


def scan_extensions(root: pathlib.Path, categories: dict[str, str]) -> dict[str, dict[str, object]]:
    stats: dict[str, dict[str, object]] = {}
    destination = root / "extensions"
    if not destination.exists():
        return stats
    for extension_dir in sorted((p for p in destination.iterdir() if p.is_dir()), key=lambda p: p.name.casefold()):
        files = [path for path in extension_dir.rglob("*") if path.is_file()]
        stats[extension_dir.name] = {
            "category": categories.get(extension_dir.name, "uncategorised"),
            "files": len(files),
            "bytes": sum(path.stat().st_size for path in files),
        }
    return stats


def write_inventory(root: pathlib.Path, stats: dict[str, dict[str, object]]) -> None:
    docs = root / "docs"
    docs.mkdir(parents=True, exist_ok=True)
    inventory: list[dict[str, object]] = []
    for folder in sorted(stats, key=str.casefold):
        extension_dir = root / "extensions" / folder
        manifest = read_manifest(extension_dir)
        inventory.append({
            "folder": folder,
            "category": stats[folder]["category"],
            "name": manifest.get("name", folder),
            "version": manifest.get("version"),
            "description": manifest.get("description", ""),
            "files": stats[folder]["files"],
            "bytes": stats[folder]["bytes"],
        })

    (docs / "extension-inventory.json").write_text(
        json.dumps(inventory, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
    )
    fields = ["folder", "category", "name", "version", "description", "files", "bytes"]
    with (docs / "extension-inventory.csv").open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields)
        writer.writeheader()
        writer.writerows(inventory)


def restore_base_dataset(root: pathlib.Path, url: str, clean: bool) -> tuple[int, int, dict[str, str]]:
    destination = root / "extensions"
    if clean and destination.exists():
        shutil.rmtree(destination)
    destination.mkdir(parents=True, exist_ok=True)

    try:
        import pyarrow.parquet as pq
    except ImportError as exc:
        raise SystemExit("pyarrow is required: python -m pip install pyarrow") from exc

    category_stats: dict[str, dict[str, object]] = defaultdict(
        lambda: {"category": "uncategorised", "files": 0, "bytes": 0}
    )
    restored = 0
    raw_bytes = 0
    with tempfile.TemporaryDirectory(prefix="ai-ext-base-") as temp_dir:
        parquet = pathlib.Path(temp_dir) / "archive.parquet"
        download(url, parquet)
        parquet_file = pq.ParquetFile(parquet)
        columns = ["Extension", "Category", "Path", "Bytes", "Sha256", "Codec", "Content"]
        for batch in parquet_file.iter_batches(columns=columns, batch_size=128):
            data = batch.to_pydict()
            rows = zip(*(data[column] for column in columns))
            for extension, category, path, expected_bytes, expected_sha, codec, content in rows:
                if codec != "zlib+base64":
                    raise ValueError(f"Unsupported codec {codec!r} for {path}")
                payload = zlib.decompress(base64.b64decode(content, validate=True))
                if len(payload) != int(expected_bytes):
                    raise ValueError(f"Size mismatch for {path}: {len(payload)} != {expected_bytes}")
                actual_sha = hashlib.sha256(payload).hexdigest()
                if actual_sha != expected_sha:
                    raise ValueError(f"SHA-256 mismatch for {path}: {actual_sha} != {expected_sha}")
                target = safe_target(root, str(path))
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(payload)
                entry = category_stats[str(extension)]
                entry["category"] = str(category)
                entry["files"] = int(entry["files"]) + 1
                entry["bytes"] = int(entry["bytes"]) + len(payload)
                restored += 1
                raw_bytes += len(payload)

    categories = {name: str(item["category"]) for name, item in category_stats.items()}
    return restored, raw_bytes, categories


def load_json(path: pathlib.Path) -> dict[str, Any]:
    value = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(value, dict):
        raise ValueError(f"Expected JSON object in {path}")
    return value


def reconstruct_overlay_archive(parquet_path: pathlib.Path, archive_path: pathlib.Path) -> tuple[str, int, int]:
    try:
        import pyarrow.parquet as pq
    except ImportError as exc:
        raise SystemExit("pyarrow is required: python -m pip install pyarrow") from exc

    table = pq.read_table(parquet_path, columns=["Sequence", "Payload_Base64", "Archive_Sha256", "Archive_Bytes"])
    rows = sorted(zip(
        table.column("Sequence").to_pylist(),
        table.column("Payload_Base64").to_pylist(),
        table.column("Archive_Sha256").to_pylist(),
        table.column("Archive_Bytes").to_pylist(),
    ), key=lambda row: int(row[0]))
    if not rows:
        raise ValueError("Overlay dataset is empty")
    expected_sequences = list(range(len(rows)))
    actual_sequences = [int(row[0]) for row in rows]
    if actual_sequences != expected_sequences:
        raise ValueError(f"Overlay chunks are not contiguous: {actual_sequences}")
    expected_shas = {str(row[2]) for row in rows}
    expected_sizes = {int(row[3]) for row in rows}
    if len(expected_shas) != 1 or len(expected_sizes) != 1:
        raise ValueError("Overlay metadata differs between chunks")
    with archive_path.open("wb") as output:
        for _, payload_b64, _, _ in rows:
            output.write(base64.b64decode(str(payload_b64), validate=True))
    actual_size = archive_path.stat().st_size
    actual_sha = hashlib.sha256(archive_path.read_bytes()).hexdigest()
    expected_sha = next(iter(expected_shas))
    expected_size = next(iter(expected_sizes))
    if actual_size != expected_size:
        raise ValueError(f"Overlay archive size mismatch: {actual_size} != {expected_size}")
    if actual_sha != expected_sha:
        raise ValueError(f"Overlay archive SHA-256 mismatch: {actual_sha} != {expected_sha}")
    return actual_sha, actual_size, len(rows)


def validate_zip_member(member: zipfile.ZipInfo) -> pathlib.PurePosixPath:
    path = pathlib.PurePosixPath(member.filename)
    if path.is_absolute() or not path.parts or ".." in path.parts:
        raise ValueError(f"Unsafe overlay archive path: {member.filename!r}")
    allowed = (
        path.parts[0] == "extensions"
        or path.parts[:2] == ("foundation", "TitanAI-Hybrid")
    )
    if not allowed:
        raise ValueError(f"Overlay archive contains disallowed path: {member.filename!r}")
    unix_mode = member.external_attr >> 16
    if stat.S_ISLNK(unix_mode):
        raise ValueError(f"Overlay archive contains a symbolic link: {member.filename!r}")
    return path


def apply_overlay(root: pathlib.Path, manifest_path: pathlib.Path) -> dict[str, Any]:
    manifest = load_json(manifest_path)
    url = str(manifest["url"])
    replace_extensions = manifest.get("replace_extensions", [])
    if not isinstance(replace_extensions, list) or not replace_extensions:
        raise ValueError("Overlay manifest requires replace_extensions")
    replace_extensions = [str(name) for name in replace_extensions]

    with tempfile.TemporaryDirectory(prefix="ai-ext-overlay-") as temp_dir:
        temp = pathlib.Path(temp_dir)
        parquet = temp / "overlay.parquet"
        archive = temp / "overlay.zip"
        download(url, parquet)
        actual_sha, actual_size, chunk_count = reconstruct_overlay_archive(parquet, archive)
        expected_sha = str(manifest["archive_sha256"])
        expected_size = int(manifest["archive_bytes"])
        if actual_sha != expected_sha or actual_size != expected_size:
            raise ValueError("Overlay dataset metadata does not match the repository manifest")

        for name in replace_extensions:
            target = root / "extensions" / name
            if target.exists():
                shutil.rmtree(target)
        foundation = root / "foundation" / "TitanAI-Hybrid"
        if foundation.exists():
            shutil.rmtree(foundation)

        extracted_files = 0
        with zipfile.ZipFile(archive) as bundle:
            for member in bundle.infolist():
                relative = validate_zip_member(member)
                target = root.joinpath(*relative.parts)
                if member.is_dir():
                    target.mkdir(parents=True, exist_ok=True)
                    continue
                target.parent.mkdir(parents=True, exist_ok=True)
                with bundle.open(member) as source, target.open("wb") as output:
                    shutil.copyfileobj(source, output)
                extracted_files += 1

    for name in replace_extensions:
        manifest_file = root / "extensions" / name / "extension.json"
        if not manifest_file.is_file():
            raise ValueError(f"Overlay did not restore required manifest: {manifest_file}")
        json.loads(manifest_file.read_text(encoding="utf-8-sig"))
    expected_files = int(manifest.get("extracted_files", extracted_files))
    if extracted_files != expected_files:
        raise ValueError(f"Overlay extracted file count mismatch: {extracted_files} != {expected_files}")
    return {
        "sha256": actual_sha,
        "bytes": actual_size,
        "chunks": chunk_count,
        "files": extracted_files,
        "replace_extensions": replace_extensions,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", required=True)
    parser.add_argument("--root", default=".")
    parser.add_argument("--clean", action="store_true")
    parser.add_argument("--overlay-manifest")
    args = parser.parse_args()

    root = pathlib.Path(args.root).resolve()
    restored, raw_bytes, categories = restore_base_dataset(root, args.url, args.clean)
    overlay_result: dict[str, Any] | None = None
    if args.overlay_manifest:
        overlay_result = apply_overlay(root, pathlib.Path(args.overlay_manifest).resolve())

    stats = scan_extensions(root, categories)
    write_inventory(root, stats)
    final_files = sum(int(item["files"]) for item in stats.values())
    final_bytes = sum(int(item["bytes"]) for item in stats.values())
    print(f"Restored base dataset: {restored} files ({raw_bytes} bytes)")
    if overlay_result is not None:
        print(
            "Applied verified overlay: "
            f"{overlay_result['files']} files, {overlay_result['bytes']} archive bytes, "
            f"SHA-256 {overlay_result['sha256']}"
        )
    print(f"Final extensions tree: {final_files} files ({final_bytes} bytes) across {len(stats)} extensions")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
