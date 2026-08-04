from __future__ import annotations

import json
import subprocess
from dataclasses import dataclass
from pathlib import Path
from typing import Any

from app.core.config import settings


class LocalBrainError(RuntimeError):
    pass


@dataclass(frozen=True)
class LocalBrainResult:
    intent: str
    confidence: float
    entities: dict[str, Any]
    persona: dict[str, Any]
    decision: dict[str, Any]
    raw: dict[str, Any]
    cloud_used: bool
    ranked_memories: list[dict[str, Any]]


class LocalBrainClient:
    def __init__(
        self,
        php_binary: str | None = None,
        script_path: Path | None = None,
        timeout_seconds: float | None = None,
    ) -> None:
        self.php_binary = php_binary or settings.LOCALBRAIN_PHP_BINARY
        self.script_path = (script_path or settings.LOCALBRAIN_SCRIPT).resolve()
        self.timeout_seconds = timeout_seconds or settings.LOCALBRAIN_TIMEOUT_SECONDS
        if not self.script_path.is_file():
            raise LocalBrainError(f"LocalBrain CLI script is missing: {self.script_path}")

    def process(
        self,
        message: str,
        *,
        tenant_id: str,
        user_id: str,
        subject_id: str,
        extra_context: dict[str, Any] | None = None,
        memories: list[dict[str, Any]] | None = None,
    ) -> LocalBrainResult:
        context: dict[str, Any] = {
            "tenant_id": tenant_id,
            "user_id": user_id,
            "subject_type": "student",
            "subject_id": subject_id,
            "privacy_class": "school_private",
        }
        if extra_context:
            context.update(extra_context)
        payload = json.dumps({"message": message, "context": context, "memories": memories or []}, separators=(",", ":"))
        try:
            completed = subprocess.run(
                [self.php_binary, str(self.script_path)],
                input=payload,
                text=True,
                capture_output=True,
                timeout=self.timeout_seconds,
                check=False,
                env={"PATH": __import__("os").environ.get("PATH", "")},
            )
        except (OSError, subprocess.TimeoutExpired) as exc:
            raise LocalBrainError(f"LocalBrain execution failed: {exc}") from exc
        try:
            envelope = json.loads(completed.stdout)
        except json.JSONDecodeError as exc:
            raise LocalBrainError(f"LocalBrain returned invalid JSON: {completed.stderr.strip()}") from exc
        if completed.returncode != 0 or not envelope.get("ok"):
            raise LocalBrainError(str(envelope.get("error") or completed.stderr.strip() or "LocalBrain failed"))
        result = envelope["result"]
        perception = result.get("perception", {})
        audit = result.get("audit", {})
        cloud_used = bool(audit.get("cloud_used", False))
        if cloud_used:
            raise LocalBrainError("LocalBrain reported cloud use in an offline-only build")
        return LocalBrainResult(
            intent=str(perception.get("intent", "unknown")),
            confidence=float(perception.get("confidence", 0.0)),
            entities=dict(perception.get("entities", {})),
            persona=dict(result.get("persona", {})),
            decision=dict(result.get("decision", {})),
            raw=result,
            cloud_used=False,
            ranked_memories=list(result.get("ranked_memories", [])),
        )
