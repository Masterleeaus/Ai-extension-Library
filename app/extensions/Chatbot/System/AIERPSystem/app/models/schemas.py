from __future__ import annotations

from datetime import datetime
from typing import Any, Literal

from pydantic import BaseModel, Field


class ChatRequest(BaseModel):
    message: str = Field(min_length=1, max_length=5000)
    student_id: str = Field(default="STU101", min_length=1, max_length=64)
    tenant_id: str = Field(default="local-school", min_length=1, max_length=128)
    user_id: str = Field(default="local-user", min_length=1, max_length=128)


class ERPResponse(BaseModel):
    intent: str
    response: str
    status: str
    plan: list[str]
    mode: Literal["offline"] = "offline"
    confidence: float = Field(default=0.0, ge=0.0, le=1.0)
    localbrain_model: str = "local-brain-v2"
    tool: str | None = None
    data: Any = None


class HistoryItem(BaseModel):
    id: int
    tenant_id: str
    user_id: str
    subject_id: str
    role: Literal["user", "assistant"]
    content: str
    created_at: datetime


class HealthResponse(BaseModel):
    status: Literal["healthy"] = "healthy"
    mode: Literal["offline"] = "offline"
    brain: Literal["local-brain-v2"] = "local-brain-v2"
    cloud_enabled: Literal[False] = False
    network_ai_calls: Literal[0] = 0
