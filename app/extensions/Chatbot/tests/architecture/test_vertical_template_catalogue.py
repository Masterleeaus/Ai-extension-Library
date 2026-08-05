from __future__ import annotations

import json
from pathlib import Path

CHATBOT_ROOT = Path(__file__).resolve().parents[2]
CATALOGUES = CHATBOT_ROOT / "resources" / "titan-apps" / "TemplateCatalogues"

EXPECTED_VERTICALS = {
    "vertical-field-home-services",
    "vertical-accommodation-hospitality",
    "vertical-real-estate",
    "vertical-salons-personal-care",
    "vertical-fitness-membership",
    "vertical-automotive-services",
    "vertical-ecommerce-retail",
    "vertical-hire-rental",
    "vertical-booking-reservations",
}

EXPECTED_WORKSPACES = {
    "workspace-crm",
    "workspace-jobs-projects",
    "workspace-finance",
    "workspace-crew-team",
}


def load_templates(category: str) -> dict[str, dict]:
    templates: dict[str, dict] = {}

    for path in sorted(CATALOGUES.glob("*.json")):
        payload = json.loads(path.read_text(encoding="utf-8"))
        if payload.get("category") != category:
            continue

        for template in payload.get("templates", []):
            slug = template["slug"]
            assert slug not in templates, f"duplicate template slug: {slug}"
            templates[slug] = template

    return templates


def test_exact_vertical_families_are_registered() -> None:
    verticals = load_templates("vertical")
    assert set(verticals) == EXPECTED_VERTICALS
    assert "vertical-facilities" not in verticals


def test_verticals_are_shared_shell_overlays() -> None:
    for slug, template in load_templates("vertical").items():
        assert template["type"] == "vertical-template"
        assert template["platform_app"] == "titan-zero"
        assert set(template["platform_apps"]) == {
            "titan-zero",
            "titan-go",
            "titan-desk",
            "titan-hub",
        }
        assert set(template["workspaces"]) == EXPECTED_WORKSPACES
        assert len(template["segments"]) >= 20, slug
        assert template["workcore"]["domains"], slug
        assert template["offline"]["conflict_rules"]["server_authoritative"] is True
        assert template["chatbot"]["system_prompt"], slug


def test_workcore_workspace_templates_are_registered() -> None:
    workspaces = load_templates("workspace")
    assert set(workspaces) == EXPECTED_WORKSPACES

    for template in workspaces.values():
        assert template["type"] == "workspace-template"
        assert template["workcore"]["domains"]
        assert template["navigation"]["primary"]


def test_titan_shell_files_have_no_merge_markers() -> None:
    paths = [
        CHATBOT_ROOT / "System" / "Http" / "Controllers" / "Api" / "TitanController.php",
        CHATBOT_ROOT / "System" / "Titan" / "TitanRegistry.php",
        CHATBOT_ROOT / "System" / "TitanShell" / "TemplateSchema.php",
        CHATBOT_ROOT / "resources" / "titan-apps" / "TemplateSchemas" / "index.json",
    ]

    for path in paths:
        content = path.read_text(encoding="utf-8")
        assert "<<<<<<<" not in content, path
        assert "=======" not in content, path
        assert ">>>>>>>" not in content, path
