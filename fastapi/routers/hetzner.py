from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import List, Optional, Dict, Any
from services.hetzner_client import HetznerClient
from utils.exceptions import HetznerAPIException, NetworkException, TimeoutException, ValidationException

router = APIRouter(prefix="/hetzner", tags=["hetzner"])

class ServerCreateRequest(BaseModel):
    name: str
    server_type: str
    image: str
    datacenter: Optional[str] = None
    ssh_keys: Optional[List[str]] = []
    user_data: Optional[str] = None

class ServerActionRequest(BaseModel):
    action: str  # "start", "stop", "restart", "reset"

@router.get("/servers")
async def list_servers():
    try:
        client = HetznerClient()
        response = await client.get_servers()
        return {
            "success": True,
            "data": response.get("servers", []),
            "meta": response.get("meta", {})
        }
    except (HetznerAPIException, NetworkException, TimeoutException, ValidationException) as e:
        raise HTTPException(status_code=e.status_code, detail=e.to_dict())

@router.get("/servers/{server_id}")
async def get_server(server_id: int):
    try:
        client = HetznerClient()
        response = await client.get_server(server_id)
        return {
            "success": True,
            "data": response.get("server")
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)

@router.post("/servers")
async def create_server(request: ServerCreateRequest):
    try:
        client = HetznerClient()
        data = {
            "name": request.name,
            "server_type": request.server_type,
            "image": request.image
        }
        
        if request.datacenter:
            data["datacenter"] = request.datacenter
        if request.ssh_keys:
            data["ssh_keys"] = request.ssh_keys
        if request.user_data:
            data["user_data"] = request.user_data
            
        response = await client.create_server(data)
        return {
            "success": True,
            "data": response
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)

@router.post("/servers/{server_id}/actions")
async def server_action(server_id: int, request: ServerActionRequest):
    try:
        client = HetznerClient()
        response = await client.server_action(server_id, request.action)
        return {
            "success": True,
            "data": response
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)

@router.delete("/servers/{server_id}")
async def delete_server(server_id: int):
    try:
        client = HetznerClient()
        response = await client.delete_server(server_id)
        return {
            "success": True,
            "data": response
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)

@router.get("/server-types")
async def get_server_types():
    try:
        client = HetznerClient()
        response = await client.get_server_types()
        return {
            "success": True,
            "data": response.get("server_types", [])
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)

@router.get("/images")
async def get_images():
    try:
        client = HetznerClient()
        response = await client.get_images()
        return {
            "success": True,
            "data": response.get("images", [])
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)

@router.get("/datacenters")
async def get_datacenters():
    try:
        client = HetznerClient()
        response = await client.get_datacenters()
        return {
            "success": True,
            "data": response.get("datacenters", [])
        }
    except HetznerAPIException as e:
        raise HTTPException(status_code=e.status_code, detail=e.message)