# main.py
from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, Dict, Any
import os

from routers.hetzner import router as hetzner_router

app = FastAPI(title="Cloud Platform API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

async def verify_internal_key(x_internal_key: Optional[str] = Header(None)):
    if x_internal_key != os.getenv("INTERNAL_API_KEY"):
        raise HTTPException(status_code=401, detail="Invalid internal API key")
    return True

app.include_router(hetzner_router, prefix="/api/v1", dependencies=[Depends(verify_internal_key)])

@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "fastapi"}