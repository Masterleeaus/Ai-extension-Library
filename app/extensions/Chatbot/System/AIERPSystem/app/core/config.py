from __future__ import annotations

from pathlib import Path

from pydantic import field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

ERP_ROOT = Path(__file__).resolve().parents[2]
ARCHIVE_ROOT = ERP_ROOT.parent


class Settings(BaseSettings):
    LOCALBRAIN_PHP_BINARY: str = "php"
    LOCALBRAIN_SCRIPT: Path = ARCHIVE_ROOT / "interaction-engine" / "bin" / "localbrain.php"
    LOCALBRAIN_TIMEOUT_SECONDS: float = 3.0
    LOCALBRAIN_MIN_CONFIDENCE: float = 0.65
    DATABASE_URL: Path = ERP_ROOT / "mock_data" / "school_erp.db"
    MEMORY_DATABASE: Path = ERP_ROOT / "data" / "conversation_memory.sqlite3"
    MEMORY_HISTORY_LIMIT: int = 50
    NETWORK_AI_ENABLED: bool = False

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        case_sensitive=True,
        extra="forbid",
    )

    @field_validator("NETWORK_AI_ENABLED")
    @classmethod
    def network_ai_must_remain_disabled(cls, value: bool) -> bool:
        if value:
            raise ValueError("Network AI is prohibited in this offline build")
        return False

    @field_validator("LOCALBRAIN_SCRIPT")
    @classmethod
    def localbrain_script_must_be_local(cls, value: Path) -> Path:
        resolved = value.expanduser().resolve()
        allowed_root = ARCHIVE_ROOT.resolve()
        if resolved != allowed_root and allowed_root not in resolved.parents:
            raise ValueError("LOCALBRAIN_SCRIPT must remain inside the extracted Titan Zero package")
        return resolved


settings = Settings()
