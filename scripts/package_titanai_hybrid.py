#!/usr/bin/env python3
"""Convert the verified TitanAI host overlay into a nested Composer package."""
from __future__ import annotations

import argparse
import json
import pathlib
import re
import shutil
from dataclasses import dataclass

PACKAGE_RELATIVE = pathlib.Path("packages/titanai-hybrid-core")
FOUNDATION_RELATIVE = pathlib.Path("foundation/TitanAI-Hybrid")
EXTENSIONS = ("AIAgent", "AIChatPro", "Chatbot")
NEW_NAMESPACE = "TitanAI\\Hybrid"
OLD_NAMESPACE = "App\\Domains\\TitanAI"
ROOT_SHARED_CLASSES = {
    "ActionCompleted", "ActionFailed", "ActionInvoked", "ActionsDiscovered",
    "ConnectorStateChanged", "ConnectorsDiscovered", "Events", "ExtensionBooted",
    "ExtensionShuttingDown", "SkillsDiscovered", "TitanAIServiceProvider",
    "UserMemoryUpdated", "WorkflowMemoryUpdated",
}
SHARED_SUBNAMESPACES = ("Console", "Contracts", "Diagnostics", "Events", "Memory", "Orchestration", "Registries")
TEXT_SUFFIXES = {".php", ".md", ".txt", ".json"}

@dataclass(frozen=True)
class ConversionSummary:
    package_files: int
    extension_files_changed: int
    aliases: int

def _require(path: pathlib.Path, label: str) -> None:
    if not path.exists():
        raise FileNotFoundError(f"Missing {label}: {path}")

def _copy_tree(source: pathlib.Path, destination: pathlib.Path) -> None:
    if source.exists():
        shutil.copytree(source, destination, dirs_exist_ok=True)

def _rewrite_package_text(path: pathlib.Path) -> None:
    if path.suffix.lower() not in TEXT_SUFFIXES:
        return
    try:
        text = path.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        return
    text = text.replace(OLD_NAMESPACE, NEW_NAMESPACE).replace("app/Domains/TitanAI", "src")
    if path.name == "TitanAIServiceProvider.php":
        text = text.replace("$this->projectPath", "$this->packagePath")
        text = text.replace("private function projectPath", "private function packagePath")
        text = text.replace("return dirname(__DIR__, 3)", "return dirname(__DIR__)")
    path.write_text(text, encoding="utf-8")

def _shared_replacements() -> list[tuple[str, str]]:
    replacements = [(f"{OLD_NAMESPACE}\\{name}", f"{NEW_NAMESPACE}\\{name}") for name in sorted(ROOT_SHARED_CLASSES)]
    replacements.extend((f"{OLD_NAMESPACE}\\{sub}\\", f"{NEW_NAMESPACE}\\{sub}\\") for sub in SHARED_SUBNAMESPACES)
    return replacements

def _rewrite_extension_file(path: pathlib.Path) -> bool:
    if path.suffix.lower() != ".php":
        return False
    try:
        original = path.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        return False
    updated = original
    for old, new in _shared_replacements():
        updated = updated.replace(old, new)
    if updated == original:
        return False
    path.write_text(updated, encoding="utf-8")
    return True

def _discover_symbols(src: pathlib.Path) -> list[tuple[str, str]]:
    symbols = []
    namespace_pattern = re.compile(r"^namespace\s+([^;]+);", re.MULTILINE)
    symbol_pattern = re.compile(r"^(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)", re.MULTILINE)
    for path in sorted(src.rglob("*.php")):
        if "Compatibility" in path.parts:
            continue
        text = path.read_text(encoding="utf-8")
        namespace = namespace_pattern.search(text)
        symbol = symbol_pattern.search(text)
        if namespace and symbol:
            current = f"{namespace.group(1)}\\{symbol.group(1)}"
            if current.startswith(NEW_NAMESPACE):
                symbols.append((current, OLD_NAMESPACE + current[len(NEW_NAMESPACE):]))
    return symbols

def _write_legacy_aliases(package: pathlib.Path) -> int:
    symbols = _discover_symbols(package / "src")
    target = package / "src" / "Compatibility" / "LegacyAliases.php"
    target.parent.mkdir(parents=True, exist_ok=True)
    rows = "\n".join(f"    {new}::class => '{old.replace(chr(92), chr(92) * 2)}'," for new, old in symbols)
    target.write_text(
        "<?php\n\ndeclare(strict_types=1);\n\n/** @var array<class-string,string> $aliases */\n$aliases = [\n"
        + rows
        + "\n];\n\nforeach ($aliases as $current => $legacy) {\n"
          "    $legacyExists = class_exists($legacy, false)\n"
          "        || interface_exists($legacy, false)\n"
          "        || trait_exists($legacy, false)\n"
          "        || (function_exists('enum_exists') && enum_exists($legacy, false));\n"
          "    if (! $legacyExists) { class_alias($current, $legacy); }\n}\n",
        encoding="utf-8",
    )
    return len(symbols)

def _composer_metadata() -> dict[str, object]:
    illuminate = "^10.0 || ^11.0 || ^12.0 || ^13.0"
    return {
        "name": "titanai/hybrid-core",
        "description": "Shared Laravel foundation for TitanAI Chatbot, AIChatPro, and AIAgent.",
        "type": "library", "license": "proprietary", "version": "1.0.0",
        "require": {
            "php": "^8.1", "illuminate/console": illuminate, "illuminate/database": illuminate,
            "illuminate/events": illuminate, "illuminate/support": illuminate,
        },
        "autoload": {"psr-4": {"TitanAI\\Hybrid\\": "src/"}, "files": ["src/Compatibility/LegacyAliases.php"]},
        "extra": {"laravel": {"providers": ["TitanAI\\Hybrid\\TitanAIServiceProvider"]}},
        "config": {"sort-packages": True}, "minimum-stability": "stable", "prefer-stable": True,
    }

def _write_readme(package: pathlib.Path) -> None:
    (package / "README.md").write_text(
        "# TitanAI Hybrid Core\n\nPrivate Laravel Composer package shared by Chatbot, AIChatPro, and AIAgent.\n\n"
        "## Responsibilities\n\n- unified registry and contracts;\n- failure-isolated event delivery;\n"
        "- shared contextual memory;\n- diagnostics and cross-extension orchestration;\n"
        "- scheduled expired-memory cleanup.\n\nIt does not own conversations, workflows, connectors, or WorkCore operational records.\n\n"
        "## Monorepo installation\n\nRegister `packages/titanai-hybrid-core` as a Composer path repository in the WorkCore root and require `titanai/hybrid-core:^1.0`.\n",
        encoding="utf-8",
    )

def _write_smoke_test(package: pathlib.Path) -> None:
    target = package / "tests" / "standalone" / "core-smoke.php"
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace Illuminate\\Support {\n"
        "    final class Collection implements \\Countable {\n"
        "        public function __construct(private array $items = []) {}\n"
        "        public function put(string $key, mixed $value): self { $this->items[$key] = $value; return $this; }\n"
        "        public function get(string $key): mixed { return $this->items[$key] ?? null; }\n"
        "        public function has(string $key): bool { return array_key_exists($key, $this->items); }\n"
        "        public function keys(): self { return new self(array_keys($this->items)); }\n"
        "        public function values(): self { return new self(array_values($this->items)); }\n"
        "        public function all(): array { return $this->items; }\n"
        "        public function count(): int { return count($this->items); }\n"
        "        public function mapWithKeys(callable $callback): self { $out=[]; foreach ($this->items as $k=>$v) $out += $callback($v,$k); return new self($out); }\n"
        "    }\n}\n\nnamespace {\n"
        "    function collect(array $items = []): \\Illuminate\\Support\\Collection { return new \\Illuminate\\Support\\Collection($items); }\n"
        "    $root = dirname(__DIR__, 2);\n"
        "    foreach (['Registrable','SkillDefinition','ActionDefinition','ConnectorDefinition','ToolDefinition'] as $file) require_once $root . '/src/Contracts/' . $file . '.php';\n"
        "    require_once $root . '/src/Registries/UnifiedRegistry.php';\n"
        "    $skill = new class implements \\TitanAI\\Hybrid\\Contracts\\SkillDefinition {\n"
        "        public function key(): string { return 'smoke'; } public function name(): string { return 'Smoke'; }\n"
        "        public function description(): string { return 'Smoke test'; } public function metadata(): array { return []; }\n"
        "        public function canHandle(string $intent): bool { return true; } public function handle(string $intent, array $context = []): string { return 'ok'; } public function trainingExamples(): array { return []; }\n"
        "    };\n"
        "    $registry = new \\TitanAI\\Hybrid\\Registries\\UnifiedRegistry();\n"
        "    $registry->registerSkill('smoke', $skill);\n"
        "    if ($registry->counts()['total'] !== 1) throw new \\RuntimeException('Registry smoke failed.');\n"
        "    echo \"TitanAI Composer core smoke PASSED\\n\";\n}\n",
        encoding="utf-8",
    )

def _write_verifier(package: pathlib.Path) -> None:
    target = package / "bin" / "verify-package.php"
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text("""#!/usr/bin/env php
<?php

declare(strict_types=1);
$root = dirname(__DIR__); $failures = []; $checks = 0;
$assert = static function (bool $ok, string $message) use (&$failures, &$checks): void { $checks++; if (! $ok) $failures[] = $message; };
$composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$assert(($composer['name'] ?? null) === 'titanai/hybrid-core', 'Invalid package name.');
$assert(($composer['extra']['laravel']['providers'][0] ?? null) === 'TitanAI\\Hybrid\\TitanAIServiceProvider', 'Missing Laravel provider discovery.');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));
foreach ($iterator as $file) { if (! $file->isFile() || $file->getExtension() !== 'php') continue; $checks++; $output=[]; exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $code); if ($code !== 0) $failures[] = implode("\n", $output); }
$checks++; $output=[]; exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/standalone/core-smoke.php') . ' 2>&1', $output, $code); if ($code !== 0) $failures[] = implode("\n", $output);
if ($failures) { fwrite(STDERR, 'TitanAI package verification FAILED (' . count($failures) . ' failures across ' . $checks . " checks)\n"); foreach ($failures as $failure) fwrite(STDERR, ' - ' . $failure . "\n"); exit(1); }
echo 'TitanAI package verification PASSED (' . $checks . " checks)\n";
""", encoding="utf-8")
    target.chmod(0o755)

def _write_dependency_manifests(root: pathlib.Path) -> None:
    data = {"package": "titanai/hybrid-core", "constraint": "^1.0", "provider": "TitanAI\\Hybrid\\TitanAIServiceProvider"}
    for extension in EXTENSIONS:
        (root / "extensions" / extension / "titanai-package.json").write_text(json.dumps(data, indent=2) + "\n", encoding="utf-8")

def convert(root: pathlib.Path) -> ConversionSummary:
    root = root.resolve(); foundation = root / FOUNDATION_RELATIVE; source_root = foundation / "app" / "Domains" / "TitanAI"
    _require(source_root, "TitanAI shared source")
    for extension in EXTENSIONS:
        _require(root / "extensions" / extension / "extension.json", f"{extension} manifest")
    package = root / PACKAGE_RELATIVE
    if package.exists(): shutil.rmtree(package)
    package.mkdir(parents=True)
    _copy_tree(source_root, package / "src"); _copy_tree(foundation / "config", package / "config")
    _copy_tree(foundation / "database", package / "database"); _copy_tree(foundation / "docs", package / "docs")
    archive = root / "docs" / "archive" / "titanai-pass3"
    if archive.exists(): shutil.rmtree(archive)
    archive.mkdir(parents=True, exist_ok=True)
    for name in ("PASS2-REPORT.md", "PASS3-REPORT.md", "TITANAI-UPGRADE-PLAN.md", "UPGRADE-REPORT.md", "CHECKSUMS.sha256"):
        source = foundation / name
        if source.is_file(): shutil.copy2(source, archive / name)
    for path in package.rglob("*"):
        if path.is_file(): _rewrite_package_text(path)
    (package / "composer.json").write_text(json.dumps(_composer_metadata(), indent=2) + "\n", encoding="utf-8")
    _write_readme(package); aliases = _write_legacy_aliases(package); _write_smoke_test(package); _write_verifier(package)
    changed = 0
    for extension in EXTENSIONS:
        for path in (root / "extensions" / extension).rglob("*.php"):
            changed += int(_rewrite_extension_file(path))
    _write_dependency_manifests(root)
    shutil.rmtree(foundation)
    if foundation.parent.exists() and not any(foundation.parent.iterdir()): foundation.parent.rmdir()
    errors = check(root)
    if errors: raise RuntimeError("Converted package failed validation:\n - " + "\n - ".join(errors))
    return ConversionSummary(sum(1 for path in package.rglob("*") if path.is_file()), changed, aliases)

def check(root: pathlib.Path) -> list[str]:
    root = root.resolve(); package = root / PACKAGE_RELATIVE; errors = []
    if (root / FOUNDATION_RELATIVE).exists(): errors.append("legacy foundation directory still exists")
    if (package / "docs" / "legacy-pass3").exists(): errors.append("legacy Pass 3 reports remain inside installable package")
    composer = package / "composer.json"
    if not composer.is_file(): return [*errors, "package composer.json is missing"]
    try: metadata = json.loads(composer.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc: return [*errors, f"invalid composer.json: {exc}"]
    if metadata.get("name") != "titanai/hybrid-core": errors.append("unexpected Composer package name")
    provider = package / "src" / "TitanAIServiceProvider.php"
    if not provider.is_file() or "namespace TitanAI\\Hybrid;" not in provider.read_text(encoding="utf-8"): errors.append("package service provider namespace is incorrect")
    for path in (package / "src").rglob("*.php"):
        if "Compatibility" not in path.parts and OLD_NAMESPACE in path.read_text(encoding="utf-8"):
            errors.append(f"legacy namespace remains in package source: {path.relative_to(root)}")
    for extension in EXTENSIONS:
        manifest = root / "extensions" / extension / "titanai-package.json"
        if not manifest.is_file(): errors.append(f"missing dependency manifest for {extension}"); continue
        data = json.loads(manifest.read_text(encoding="utf-8"))
        if data.get("package") != "titanai/hybrid-core" or data.get("constraint") != "^1.0": errors.append(f"invalid dependency manifest for {extension}")
    return errors

def main() -> int:
    parser = argparse.ArgumentParser(); parser.add_argument("--root", default="."); parser.add_argument("--check", action="store_true")
    args = parser.parse_args(); root = pathlib.Path(args.root)
    if args.check:
        errors = check(root)
        if errors:
            print("TitanAI package check FAILED")
            for error in errors: print(f" - {error}")
            return 1
        print("TitanAI package check PASSED"); return 0
    summary = convert(root)
    print(f"Converted TitanAI Hybrid to Composer package: {summary.package_files} package files, {summary.extension_files_changed} extension files updated, {summary.aliases} legacy aliases")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
