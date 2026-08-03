#!/usr/bin/env python3
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SERVER = ROOT / "packages/titan-interaction-engine"
OFFLINE = ROOT / "packages/titan-interaction-engine-offline"
CONTRACTS = ROOT / "packages/titan-interaction-contracts"
BRIDGE = ROOT / "extensions/InteractionEngineChatbotBridge"


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


def load(path: Path) -> dict:
    return json.loads(path.read_text(encoding="utf-8"))


def main() -> None:
    for path in (SERVER, OFFLINE, CONTRACTS, BRIDGE):
        require(path.is_dir(), f"missing {path.relative_to(ROOT)}")

    composer = load(SERVER / "composer.json")
    require(composer["name"] == "titanzero/interaction-engine", "wrong Composer name")
    require(composer["type"] == "library", "server package must be a Composer library")
    require("extra" in composer and "laravel" in composer["extra"], "Laravel discovery missing")
    for forbidden in ("extension.json", "module.json", "package.json", "src/Extensions", "resources/ts"):
        require(not (SERVER / forbidden).exists(), f"mixed packaging remains in server package: {forbidden}")

    offline = load(OFFLINE / "package.json")
    require(offline["name"] == "@titanzero/interaction-engine-offline", "wrong offline package name")
    require((OFFLINE / "src/index.ts").is_file(), "offline entry source missing")
    require((OFFLINE / "dist/index.js").is_file(), "offline compiled distribution missing")

    contracts_npm = load(CONTRACTS / "package.json")
    contracts_composer = load(CONTRACTS / "composer.json")
    require(contracts_npm["name"] == "@titanzero/interaction-contracts", "wrong contracts NPM name")
    require(contracts_composer["name"] == "titanzero/interaction-contracts", "wrong contracts Composer name")
    for schema in (
        "command-envelope.schema.json",
        "cognitive-event.schema.json",
        "interaction-definition.schema.json",
        "wizard-definition.schema.json",
    ):
        load(CONTRACTS / "schemas" / schema)

    manifest = load(BRIDGE / "extension.json")
    require(manifest["id"] == "interaction-engine-chatbot-bridge", "wrong bridge id")
    bridge_composer = load(BRIDGE / "composer.json")
    bridge_npm = load(BRIDGE / "package.json")
    require(bridge_composer["require"]["titanzero/interaction-engine"] == "^1.0", "bridge server dependency missing")
    require("@titanzero/interaction-engine-offline" in bridge_npm["dependencies"], "bridge offline dependency missing")
    require(len(list(BRIDGE.rglob("*.php"))) <= 2, "bridge contains duplicated PHP implementation")

    print("PASS Interaction Engine Composer, offline, contracts and Chatbot bridge architecture")


if __name__ == "__main__":
    main()
