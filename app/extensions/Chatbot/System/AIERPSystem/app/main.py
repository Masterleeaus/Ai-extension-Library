from __future__ import annotations

import json
import logging
import time
from pathlib import Path

from fastapi import FastAPI, HTTPException, Request
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware

from app.api.endpoints import router as api_router

LOG_DIR = Path("logs")
LOG_DIR.mkdir(exist_ok=True)
logger = logging.getLogger("erp_logger")
logger.setLevel(logging.INFO)
if not logger.handlers:
    file_handler = logging.FileHandler(LOG_DIR / "app.log", encoding="utf-8")
    file_handler.setFormatter(logging.Formatter("%(message)s"))
    logger.addHandler(file_handler)

app = FastAPI(
    title="Titan Zero Offline School ERP Assistant",
    version="3.0.0-offline",
    description="Fully offline ERP tool façade powered exclusively by LocalBrain v2.",
)
app.include_router(api_router)


class LoggingMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        start = time.perf_counter()
        response = await call_next(request)
        logger.info(json.dumps({
            "path": request.url.path,
            "query_length": getattr(request.state, "query_length", 0),
            "intent": getattr(request.state, "intent", ""),
            "selected_tool": getattr(request.state, "selected_tool", ""),
            "response_status": getattr(request.state, "response_status", ""),
            "execution_time_ms": round((time.perf_counter() - start) * 1000, 2),
            "cloud_calls": 0,
        }))
        return response


app.add_middleware(LoggingMiddleware)


@app.exception_handler(HTTPException)
async def http_error(_: Request, exc: HTTPException):
    return JSONResponse(status_code=exc.status_code, content={"intent": "Error", "response": str(exc.detail), "status": "Error", "plan": ["Request rejected"], "mode": "offline", "confidence": 0.0, "localbrain_model": "local-brain-v2"})


@app.exception_handler(Exception)
async def unhandled_error(_: Request, exc: Exception):
    logger.exception("Unhandled local ERP error", exc_info=exc)
    return JSONResponse(status_code=500, content={"intent": "Error", "response": "A local processing error occurred.", "status": "Error", "plan": ["Local error boundary"], "mode": "offline", "confidence": 0.0, "localbrain_model": "local-brain-v2"})


@app.get("/", tags=["Health"])
def root() -> dict:
    return {
        "service": "Titan Zero Offline School ERP Assistant",
        "version": "3.0.0-offline",
        "mode": "offline",
        "brain": "local-brain-v2",
        "cloud_enabled": False,
        "endpoints": {"chat": "POST /chat", "history": "GET /chat/history", "health": "GET /health"},
    }
