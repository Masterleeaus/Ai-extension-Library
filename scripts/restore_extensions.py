#!/usr/bin/env python3
"""Restore and verify the lossless AI extension dataset."""
from __future__ import annotations

import argparse
import base64
import csv
import hashlib
import json
import pathlib
import shutil
import tempfile
import urllib.request
import zlib
from collections import defaultdict


def download(url: str, target: pathlib.Path) -> None:
    request = urllib.request.Request(
        url,
        headers={"User-Agent": "ai-extensions-materializer/1.0"},
    )
    with urllib.request.urlopen(request, timeout=180) as response, target.open("wb") as output:
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


def write_inventory(root: pathlib.Path, stats: dict[str, dict[str, object]]) -> None:
    docs = root / "docs"
    docs.mkdir(parents=True, exist_ok=True)
    inventory: list[dict[str, object]] = []
    for folder in sorted(stats, key=str.casefold):
        extension_dir = root / "extensions" / folder
        manifest = read_manifest(extension_dir)
        item = {
            "folder": folder,
            "category": stats[folder]["category"],
            "name": manifest.get("name", folder),
            "version": manifest.get("version"),
            "description": manifest.get("description", ""),
            "files": stats[folder]["files"],
            "bytes": stats[folder]["bytes"],
        }
        inventory.append(item)

    json_path = docs / "extension-inventory.json"
    json_path.write_text(json.dumps(inventory, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    csv_path = docs / "extension-inventory.csv"
    fields = ["folder", "category", "name", "version", "description", "files", "bytes"]
    with csv_path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields)
        writer.writeheader()
        writer.writerows(inventory)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", required=True)
    parser.add_argument("--root", default=".")
    parser.add_argument("--clean", action="store_true")
    args = parser.parse_args()

    root = pathlib.Path(args.root).resolve()
    destination = root / "extensions"
    if args.clean and destination.exists():
        shutil.rmtree(destination)
    destination.mkdir(parents=True, exist_ok=True)

    try:
        import pyarrow.parquet as pq
    except ImportError as exc:
        raise SystemExit("pyarrow is required: python -m pip install pyarrow") from exc

    stats: dict[str, dict[str, object]] = defaultdict(lambda: {"category": "uncategorised", "files": 0, "bytes": 0})
    restored = 0
    raw_bytes = 0

    with tempfile.TemporaryDirectory(prefix="ai-ext-") as temp_dir:
        parquet = pathlib.Path(temp_dir) / "archive.parquet"
        download(args.url, parquet)
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

                target = safe_target(root, path)
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(payload)

                entry = stats[str(extension)]
                entry["category"] = str(category)
                entry["files"] = int(entry["files"]) + 1
                entry["bytes"] = int(entry["bytes"]) + len(payload)
                restored += 1
                raw_bytes += len(payload)

    write_inventory(root, stats)
    print(f"Restored {restored} files ({raw_bytes} bytes) into {destination}")
    print(f"Generated inventories for {len(stats)} extensions")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
