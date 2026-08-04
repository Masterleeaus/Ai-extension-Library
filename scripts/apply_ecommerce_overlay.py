#!/usr/bin/env python3
"""Apply the verified ChatbotEcommerce overlay without disturbing other overlays."""
from __future__ import annotations

import argparse
import json
import pathlib
import shutil
import tempfile
import zipfile

from restore_extensions import (
    download,
    load_json,
    reconstruct_overlay_archive,
    scan_extensions,
    validate_zip_member,
    write_inventory,
)


def load_categories(root: pathlib.Path) -> dict[str, str]:
    inventory_path = root / "docs" / "extension-inventory.json"
    if not inventory_path.exists():
        return {}
    value = json.loads(inventory_path.read_text(encoding="utf-8"))
    if not isinstance(value, list):
        return {}
    return {
        str(item.get("folder")): str(item.get("category", "uncategorised"))
        for item in value
        if isinstance(item, dict) and item.get("folder")
    }


def apply_ecommerce_overlay(root: pathlib.Path, manifest_path: pathlib.Path) -> dict[str, object]:
    manifest = load_json(manifest_path)
    replace_extensions = [str(name) for name in manifest.get("replace_extensions", [])]
    if replace_extensions != ["ChatbotEcommerce"]:
        raise ValueError("Ecommerce overlay may replace only ChatbotEcommerce")

    with tempfile.TemporaryDirectory(prefix="chatbot-ecommerce-overlay-") as temp_dir:
        temp = pathlib.Path(temp_dir)
        parquet = temp / "overlay.parquet"
        archive = temp / "overlay.zip"
        download(str(manifest["url"]), parquet)
        actual_sha, actual_size, chunk_count = reconstruct_overlay_archive(parquet, archive)

        if actual_sha != str(manifest["archive_sha256"]):
            raise ValueError("Ecommerce overlay SHA-256 does not match the repository manifest")
        if actual_size != int(manifest["archive_bytes"]):
            raise ValueError("Ecommerce overlay byte count does not match the repository manifest")

        target_extension = root / "extensions" / "ChatbotEcommerce"
        if target_extension.exists():
            shutil.rmtree(target_extension)

        extracted_files = 0
        with zipfile.ZipFile(archive) as bundle:
            for member in bundle.infolist():
                relative = validate_zip_member(member)
                if relative.parts[:2] != ("extensions", "ChatbotEcommerce"):
                    raise ValueError(f"Unexpected ecommerce overlay path: {member.filename!r}")
                target = root.joinpath(*relative.parts)
                if member.is_dir():
                    target.mkdir(parents=True, exist_ok=True)
                    continue
                target.parent.mkdir(parents=True, exist_ok=True)
                with bundle.open(member) as source, target.open("wb") as output:
                    shutil.copyfileobj(source, output)
                extracted_files += 1

    expected_files = int(manifest["extracted_files"])
    if extracted_files != expected_files:
        raise ValueError(f"Ecommerce overlay file count mismatch: {extracted_files} != {expected_files}")

    extension_manifest = root / "extensions" / "ChatbotEcommerce" / "extension.json"
    extension_data = json.loads(extension_manifest.read_text(encoding="utf-8-sig"))
    expected_version = str(manifest["extension_version"])
    if str(extension_data.get("version")) != expected_version:
        raise ValueError(
            f"ChatbotEcommerce version mismatch: {extension_data.get('version')} != {expected_version}"
        )

    return {
        "sha256": actual_sha,
        "bytes": actual_size,
        "chunks": chunk_count,
        "files": extracted_files,
        "version": expected_version,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--root", default=".")
    args = parser.parse_args()

    root = pathlib.Path(args.root).resolve()
    categories = load_categories(root)
    result = apply_ecommerce_overlay(root, pathlib.Path(args.manifest).resolve())
    categories.setdefault("ChatbotEcommerce", "chatbot-suite")
    stats = scan_extensions(root, categories)
    write_inventory(root, stats)

    print(
        "Applied verified ChatbotEcommerce overlay: "
        f"v{result['version']}, {result['files']} files, {result['bytes']} archive bytes, "
        f"SHA-256 {result['sha256']}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
