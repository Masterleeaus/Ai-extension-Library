#!/usr/bin/env python3
"""Run the Titan Hub donor importer with strict ancestor-directory handling."""
from __future__ import annotations

import importlib.util
import io
import shutil
import tarfile
from pathlib import Path, PurePosixPath

core_path = Path(__file__).with_name("import_titan_hub_donors.py")
spec = importlib.util.spec_from_file_location("titan_hub_donor_import_core", core_path)
if spec is None or spec.loader is None:
    raise SystemExit("Unable to load Titan Hub donor import core")
core = importlib.util.module_from_spec(spec)
spec.loader.exec_module(core)


def extract_with_ancestors(plaintext: bytes) -> None:
    for relative in core.TARGETS:
        target = core.ROOT / relative
        if target.exists():
            shutil.rmtree(target)

    with tarfile.open(fileobj=io.BytesIO(plaintext), mode="r:gz") as archive:
        members: list[tarfile.TarInfo] = []
        for member in archive.getmembers():
            path = PurePosixPath(member.name)
            if not path.parts or str(path) in {".", ""}:
                continue
            if path.is_absolute() or ".." in path.parts:
                core.fail(f"Unsafe archive path: {member.name!r}")
            if member.issym() or member.islnk() or member.isdev():
                core.fail(f"Unsupported archive member: {member.name!r}")

            is_target = any(
                path == prefix
                or prefix in path.parents
                or (member.isdir() and path in prefix.parents)
                for prefix in core.APPROVED_PREFIXES
            )
            if not is_target:
                core.fail(f"Archive path outside approved targets: {member.name!r}")
            members.append(member)
        archive.extractall(core.ROOT, members=members, filter="data")


core.extract = extract_with_ancestors
raise SystemExit(core.main())
