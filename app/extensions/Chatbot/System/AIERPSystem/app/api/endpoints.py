from __future__ import annotations

from fastapi import APIRouter, HTTPException, Query, Request

from app.agents.erp_agent import ERPAgent
from app.core.config import settings
from app.memory.conversation_memory import ConversationMemoryStore, MemoryScope
from app.models.schemas import ChatRequest, ERPResponse, HealthResponse, HistoryItem
from mock_data.init_db import initialise_database

router = APIRouter()

if not settings.DATABASE_URL.exists():
    initialise_database(settings.DATABASE_URL)
_memory = ConversationMemoryStore()
_agent = ERPAgent(memory=_memory)


def _scope(tenant_id: str, user_id: str, student_id: str) -> MemoryScope:
    return MemoryScope(tenant_id=tenant_id, user_id=user_id, subject_id=student_id)


@router.post("/chat", response_model=ERPResponse, tags=["Offline ERP"])
@router.post("/api/v1/chat", response_model=ERPResponse, include_in_schema=False)
def chat(payload: ChatRequest, request: Request) -> ERPResponse:
    response = _agent.execute(payload.message, _scope(payload.tenant_id, payload.user_id, payload.student_id))
    request.state.query_length = len(payload.message)
    request.state.intent = response.intent
    request.state.selected_tool = response.tool or "none"
    request.state.response_status = response.status
    return response


@router.get("/chat/history", response_model=list[HistoryItem], tags=["Offline ERP"])
@router.get("/api/v1/chat/history", response_model=list[HistoryItem], include_in_schema=False)
def history(
    tenant_id: str = Query(default="local-school"),
    user_id: str = Query(default="local-user"),
    student_id: str = Query(default="STU101"),
    limit: int = Query(default=20, ge=1, le=100),
) -> list[dict]:
    return _memory.get_messages(_scope(tenant_id, user_id, student_id), limit=limit)


@router.delete("/chat/history", tags=["Offline ERP"])
def clear_history(
    tenant_id: str = Query(default="local-school"),
    user_id: str = Query(default="local-user"),
    student_id: str = Query(default="STU101"),
) -> dict[str, int]:
    return {"deleted": _memory.clear(_scope(tenant_id, user_id, student_id))}


@router.get("/health", response_model=HealthResponse, tags=["Health"])
def health() -> HealthResponse:
    return HealthResponse()
