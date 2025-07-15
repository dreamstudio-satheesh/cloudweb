from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
import os
from typing import Optional

app = FastAPI(title="Cloud Platform API", version="1.0.0")

# CORS Configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Internal API Key validation
async def verify_internal_key(x_internal_key: Optional[str] = Header(None)):
    if x_internal_key != os.getenv("INTERNAL_API_KEY"):
        raise HTTPException(status_code=401, detail="Invalid internal API key")
    return True

@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "fastapi"}

@app.get("/api/internal/test", dependencies=[Depends(verify_internal_key)])
async def internal_test():
    return {"message": "Internal API communication successful"}