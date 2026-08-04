from __future__ import annotations

import json
import pathlib
import shutil
import tempfile
import unittest

from scripts.package_titanai_hybrid import check, convert


REPO_ROOT = pathlib.Path(__file__).resolve().parents[2]


class PackageTitanAIHybridTest(unittest.TestCase):
    def make_fixture(self) -> pathlib.Path:
        root = pathlib.Path(tempfile.mkdtemp(prefix="titanai-package-test-"))
        self.addCleanup(shutil.rmtree, root, True)
        foundation = root / "foundation" / "TitanAI-Hybrid"
        source = foundation / "app" / "Domains" / "TitanAI"
        (source / "Contracts").mkdir(parents=True)
        (source / "Registries").mkdir(parents=True)
        (foundation / "config").mkdir(parents=True)
        (foundation / "database" / "migrations").mkdir(parents=True)
        (foundation / "docs").mkdir(parents=True)
        (source / "Contracts" / "Registrable.php").write_text(
            "<?php\nnamespace App\\Domains\\TitanAI\\Contracts;\ninterface Registrable {}\n",
            encoding="utf-8",
        )
        (source / "Registries" / "UnifiedRegistry.php").write_text(
            "<?php\nnamespace App\\Domains\\TitanAI\\Registries;\nfinal class UnifiedRegistry {}\n",
            encoding="utf-8",
        )
        (source / "TitanAIServiceProvider.php").write_text(
            "<?php\nnamespace App\\Domains\\TitanAI;\n"
            "final class TitanAIServiceProvider { private function projectPath(string $relative): string { return dirname(__DIR__, 3) . '/' . $relative; } }\n",
            encoding="utf-8",
        )
        (foundation / "config" / "titanai.php").write_text("<?php return [];\n", encoding="utf-8")
        (foundation / "database" / "migrations" / "create_unified_memories.php").write_text("<?php\n", encoding="utf-8")
        (foundation / "docs" / "architecture.md").write_text("App\\Domains\\TitanAI\n", encoding="utf-8")
        for report in ("PASS2-REPORT.md", "PASS3-REPORT.md", "TITANAI-UPGRADE-PLAN.md", "UPGRADE-REPORT.md", "CHECKSUMS.sha256"):
            (foundation / report).write_text(report + "\n", encoding="utf-8")

        for name in ("AIAgent", "AIChatPro", "Chatbot"):
            ext = root / "extensions" / name
            (ext / "System").mkdir(parents=True)
            (ext / "extension.json").write_text(json.dumps({"name": name, "version": "test"}), encoding="utf-8")
            (ext / "System" / f"{name}ServiceProvider.php").write_text(
                "<?php\nnamespace App\\Extensions\\Test;\n"
                "use App\\Domains\\TitanAI\\TitanAIServiceProvider;\n"
                "use App\\Domains\\TitanAI\\Registries\\UnifiedRegistry;\n",
                encoding="utf-8",
            )
        chatbot_owned = root / "extensions" / "Chatbot" / "System" / "TitanAI" / "channels" / "web-chat"
        chatbot_owned.mkdir(parents=True)
        (chatbot_owned / "WebChatChannel.php").write_text(
            "<?php\nnamespace App\\Domains\\TitanAI\\Channels\\WebChat;\n",
            encoding="utf-8",
        )
        return root

    def test_conversion_creates_authoritative_composer_package(self) -> None:
        root = self.make_fixture()
        summary = convert(root)
        package = root / "packages" / "titanai-hybrid-core"
        metadata = json.loads((package / "composer.json").read_text(encoding="utf-8"))
        self.assertEqual("titanai/hybrid-core", metadata["name"])
        self.assertEqual("TitanAI\\Hybrid\\TitanAIServiceProvider", metadata["extra"]["laravel"]["providers"][0])
        self.assertEqual({"TitanAI\\Hybrid\\": "src/"}, metadata["autoload"]["psr-4"])
        self.assertIn("src/Compatibility/LegacyAliases.php", metadata["autoload"]["files"])
        self.assertTrue((package / "src" / "TitanAIServiceProvider.php").is_file())
        self.assertFalse((root / "foundation" / "TitanAI-Hybrid").exists())
        self.assertGreater(summary.package_files, 10)

    def test_conversion_rewrites_only_shared_extension_imports(self) -> None:
        root = self.make_fixture()
        convert(root)
        for name in ("AIAgent", "AIChatPro", "Chatbot"):
            provider = root / "extensions" / name / "System" / f"{name}ServiceProvider.php"
            source = provider.read_text(encoding="utf-8")
            self.assertIn("use TitanAI\\Hybrid\\TitanAIServiceProvider;", source)
            self.assertNotIn("use App\\Domains\\TitanAI\\TitanAIServiceProvider;", source)
        chatbot_owned = root / "extensions" / "Chatbot" / "System" / "TitanAI" / "channels" / "web-chat" / "WebChatChannel.php"
        self.assertIn("namespace App\\Domains\\TitanAI\\Channels\\WebChat;", chatbot_owned.read_text(encoding="utf-8"))

    def test_conversion_writes_dependency_manifests_and_legacy_aliases(self) -> None:
        root = self.make_fixture()
        convert(root)
        for name in ("AIAgent", "AIChatPro", "Chatbot"):
            dependency = json.loads((root / "extensions" / name / "titanai-package.json").read_text(encoding="utf-8"))
            self.assertEqual("titanai/hybrid-core", dependency["package"])
            self.assertEqual("^1.0", dependency["constraint"])
        aliases = (root / "packages" / "titanai-hybrid-core" / "src" / "Compatibility" / "LegacyAliases.php").read_text(encoding="utf-8")
        self.assertIn("App\\\\Domains\\\\TitanAI\\\\Registries\\\\UnifiedRegistry", aliases)
        self.assertIn("TitanAI\\Hybrid\\Registries\\UnifiedRegistry", aliases)

    def test_check_rejects_legacy_reports_inside_installable_package(self) -> None:
        root = self.make_fixture()
        convert(root)
        legacy = root / "packages" / "titanai-hybrid-core" / "docs" / "legacy-pass3"
        legacy.mkdir(parents=True)
        (legacy / "PASS3-REPORT.md").write_text("duplicate\n", encoding="utf-8")
        self.assertIn("legacy Pass 3 reports remain inside installable package", check(root))

    def test_check_reports_clean_converted_state(self) -> None:
        root = self.make_fixture()
        convert(root)
        self.assertEqual([], check(root))

    def test_materialisation_workflow_recreates_all_post_base_packages(self) -> None:
        workflow = (REPO_ROOT / ".github" / "workflows" / "materialize-ai-extensions.yml").read_text(encoding="utf-8")
        restore = "python scripts/restore_extensions.py"
        titanai = "python scripts/package_titanai_hybrid.py --root ."
        interaction = "python scripts/apply_interaction_engine_packages.py --root ."
        self.assertIn(restore, workflow)
        self.assertIn(titanai, workflow)
        self.assertIn("python scripts/package_titanai_hybrid.py --root . --check", workflow)
        self.assertIn(interaction, workflow)
        self.assertIn("python scripts/verify_interaction_engine_architecture.py", workflow)
        self.assertLess(workflow.index(restore), workflow.index(titanai))
        self.assertLess(workflow.index(restore), workflow.index(interaction))
        self.assertIn("packages/titanai-hybrid-core", workflow)
        self.assertIn("extensions/InteractionEngineChatbotBridge", workflow)
        self.assertIn("git add extensions packages", workflow)
        self.assertNotIn("git add extensions foundation", workflow)


if __name__ == "__main__":
    unittest.main()
