#!/usr/bin/env python3
"""Restore and verify the lossless AI extension datasets."""
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
from collections.abc import Iterable


DATASET_COLUMNS = ["Extension", "Category", "Path", "Bytes", "Sha256", "Codec", "Content"]


def download(url: str, target: pathlib.Path) -> None:
    request = urllib.request.Request(
        url,
        headers={"User-Agent": "ai-extensions-materializer/1.1"},
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


def safe_extension_dir(root: pathlib.Path, extension: str) -> pathlib.Path:
    if not extension or extension in {".", ".."} or "/" in extension or "\\" in extension:
        raise ValueError(f"Unsafe extension name: {extension!r}")
    target = (root / "extensions" / extension).resolve()
    expected = (root / "extensions").resolve()
    if expected not in target.parents:
        raise ValueError(f"Extension directory escapes root: {extension!r}")
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


def write_inventory(root: pathlib.Path, categories: dict[str, str]) -> None:
    docs = root / "docs"
    docs.mkdir(parents=True, exist_ok=True)
    inventory: list[dict[str, object]] = []
    extensions_root = root / "extensions"

    for extension_dir in sorted(
        (path for path in extensions_root.iterdir() if path.is_dir()),
        key=lambda path: path.name.casefold(),
    ):
        files = [path for path in extension_dir.rglob("*") if path.is_file()]
        manifest = read_manifest(extension_dir)
        item = {
            "folder": extension_dir.name,
            "category": categories.get(extension_dir.name, "uncategorised"),
            "name": manifest.get("name", extension_dir.name),
            "version": manifest.get("version"),
            "description": manifest.get("description", ""),
            "files": len(files),
            "bytes": sum(path.stat().st_size for path in files),
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


def dataset_extensions(parquet_file: object) -> set[str]:
    extensions: set[str] = set()
    for batch in parquet_file.iter_batches(columns=["Extension"], batch_size=512):
        for extension in batch.to_pydict()["Extension"]:
            extensions.add(str(extension))
    return extensions


def restore_dataset(
    *,
    url: str,
    root: pathlib.Path,
    categories: dict[str, str],
    replace_extensions: bool,
    label: str,
) -> tuple[int, int]:
    try:
        import pyarrow.parquet as pq
    except ImportError as exc:
        raise SystemExit("pyarrow is required: python -m pip install pyarrow") from exc

    restored = 0
    raw_bytes = 0

    with tempfile.TemporaryDirectory(prefix="ai-ext-") as temp_dir:
        parquet = pathlib.Path(temp_dir) / "archive.parquet"
        download(url, parquet)
        parquet_file = pq.ParquetFile(parquet)

        if replace_extensions:
            for extension in sorted(dataset_extensions(parquet_file), key=str.casefold):
                target = safe_extension_dir(root, extension)
                if target.exists():
                    shutil.rmtree(target)

        for batch in parquet_file.iter_batches(columns=DATASET_COLUMNS, batch_size=128):
            data = batch.to_pydict()
            rows: Iterable[tuple[object, ...]] = zip(*(data[column] for column in DATASET_COLUMNS))
            for extension, category, path, expected_bytes, expected_sha, codec, content in rows:
                if codec != "zlib+base64":
                    raise ValueError(f"Unsupported codec {codec!r} for {path}")
                payload = zlib.decompress(base64.b64decode(content, validate=True))
                if len(payload) != int(expected_bytes):
                    raise ValueError(f"Size mismatch for {path}: {len(payload)} != {expected_bytes}")
                actual_sha = hashlib.sha256(payload).hexdigest()
                if actual_sha != expected_sha:
                    raise ValueError(f"SHA-256 mismatch for {path}: {actual_sha} != {expected_sha}")

                extension_name = str(extension)
                categories[extension_name] = str(category)
                target = safe_target(root, str(path))
                expected_dir = safe_extension_dir(root, extension_name)
                if target != expected_dir and expected_dir not in target.parents:
                    raise ValueError(
                        f"Dataset extension/path mismatch for {path!r}: expected {extension_name!r}"
                    )
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(payload)
                restored += 1
                raw_bytes += len(payload)

    print(f"Restored {restored} files ({raw_bytes} bytes) from {label}")
    return restored, raw_bytes


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", required=True, help="Base lossless Parquet dataset URL")
    parser.add_argument(
        "--overlay-url",
        action="append",
        default=[],
        help="Lossless Parquet overlay URL; each overlay replaces the extension folders it contains",
    )
    parser.add_argument("--root", default=".")
    parser.add_argument("--clean", action="store_true")
    args = parser.parse_args()

    root = pathlib.Path(args.root).resolve()
    destination = root / "extensions"
    if args.clean and destination.exists():
        shutil.rmtree(destination)
    destination.mkdir(parents=True, exist_ok=True)

    categories: dict[str, str] = {}
    restored, raw_bytes = restore_dataset(
        url=args.url,
        root=root,
        categories=categories,
        replace_extensions=False,
        label="base dataset",
    )

    for index, overlay_url in enumerate(args.overlay_url, start=1):
        overlay_files, overlay_bytes = restore_dataset(
            url=overlay_url,
            root=root,
            categories=categories,
            replace_extensions=True,
            label=f"overlay {index}",
        )
        restored += overlay_files
        raw_bytes += overlay_bytes

    write_inventory(root, categories)
    print(f"Materialised {restored} dataset rows ({raw_bytes} bytes processed) into {destination}")
    print(f"Generated inventories for {len(categories)} extensions")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
