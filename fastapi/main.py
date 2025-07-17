from fastapi import FastAPI, HTTPException, Depends, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import uvicorn
import os
from typing import List, Optional
import logging

from services.hetzner_client import HetznerClient
from services.database import get_db
from models.server_models import (
    ServerCreateRequest, ServerResponse, ServerListResponse,
    PowerActionRequest, PowerActionResponse, ServerMetricsResponse
)
from models.database_models import Server, User
from utils.auth import verify_internal_token
from utils.exceptions import HetznerAPIException, DatabaseException
from utils.logging_config import setup_logging

# Setup logging
setup_logging()
logger = logging.getLogger(__name__)

# Initialize Hetzner client
hetzner_client = HetznerClient(
    api_token=os.getenv("HETZNER_API_TOKEN"),
    rate_limit_per_second=10
)

security = HTTPBearer()

@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.info("FastAPI application startup")
    yield
    logger.info("FastAPI application shutdown")

app = FastAPI(
    title="Cloud Hosting API",
    description="FastAPI backend for Hetzner Cloud integration",
    version="1.0.0",
    lifespan=lifespan
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost", "http://laravel:8000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health check endpoint
@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "fastapi"}

# Authentication dependency
async def get_current_user(
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db = Depends(get_db)
):
    try:
        payload = verify_internal_token(credentials.credentials)
        user_id = payload.get("user_id")
        if not user_id:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Invalid authentication token"
            )
        
        user = db.query(User).filter(User.id == user_id).first()
        if not user:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="User not found"
            )
        return user
    except Exception as e:
        logger.error(f"Authentication error: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid authentication token"
        )

# Server endpoints
@app.get("/servers", response_model=ServerListResponse)
async def list_servers(
    current_user: User = Depends(get_current_user),
    db = Depends(get_db)
):
    """List all servers for the current user"""
    try:
        # Get servers from database
        db_servers = db.query(Server).filter(Server.user_id == current_user.id).all()
        
        # Sync with Hetzner Cloud
        hetzner_servers = await hetzner_client.list_servers()
        
        # Update database with latest status
        for hetzner_server in hetzner_servers:
            db_server = next((s for s in db_servers if s.hetzner_id == hetzner_server['id']), None)
            if db_server:
                db_server.status = hetzner_server['status']
                db_server.ipv4_address = hetzner_server['public_net']['ipv4']['ip'] if hetzner_server['public_net']['ipv4'] else None
                db_server.ipv6_address = hetzner_server['public_net']['ipv6']['ip'] if hetzner_server['public_net']['ipv6'] else None
        
        db.commit()
        
        # Convert to response format
        servers = []
        for server in db_servers:
            servers.append(ServerResponse(
                id=server.id,
                hetzner_id=server.hetzner_id,
                name=server.name,
                status=server.status,
                server_type=server.server_type,
                datacenter=server.datacenter,
                ipv4_address=server.ipv4_address,
                ipv6_address=server.ipv6_address,
                labels=server.labels or {},
                created_at=server.created_at,
                monthly_cost=server.monthly_cost
            ))
        
        return ServerListResponse(servers=servers, count=len(servers))
        
    except Exception as e:
        logger.error(f"Error listing servers: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve servers"
        )

@app.post("/servers", response_model=ServerResponse)
async def create_server(
    request: ServerCreateRequest,
    current_user: User = Depends(get_current_user),
    db = Depends(get_db)
):
    """Create a new server"""
    try:
        # Validate server type and location
        server_types = await hetzner_client.get_server_types()
        locations = await hetzner_client.get_locations()
        
        if request.server_type not in [st['name'] for st in server_types]:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"Invalid server type: {request.server_type}"
            )
        
        if request.location not in [loc['name'] for loc in locations]:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"Invalid location: {request.location}"
            )
        
        # Check user quota (implement quota logic)
        user_server_count = db.query(Server).filter(Server.user_id == current_user.id).count()
        if user_server_count >= 10:  # Example limit
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Server limit reached"
            )
        
        # Create server via Hetzner API
        hetzner_response = await hetzner_client.create_server(
            name=request.name,
            server_type=request.server_type,
            location=request.location,
            ssh_keys=request.ssh_keys,
            user_data=request.user_data,
            labels=request.labels,
            enable_backups=request.enable_backups,
            enable_protection=request.enable_protection
        )
        
        # Save to database
        server_type_data = next(st for st in server_types if st['name'] == request.server_type)
        
        db_server = Server(
            user_id=current_user.id,
            hetzner_id=hetzner_response['server']['id'],
            name=request.name,
            status=hetzner_response['server']['status'],
            server_type=request.server_type,
            datacenter=request.location,
            ipv4_address=hetzner_response['server']['public_net']['ipv4']['ip'] if hetzner_response['server']['public_net']['ipv4'] else None,
            ipv6_address=hetzner_response['server']['public_net']['ipv6']['ip'] if hetzner_response['server']['public_net']['ipv6'] else None,
            labels=request.labels,
            monthly_cost=server_type_data['prices'][0]['price_monthly']['gross']
        )
        
        db.add(db_server)
        db.commit()
        db.refresh(db_server)
        
        logger.info(f"Server created: {request.name} for user {current_user.id}")
        
        return ServerResponse(
            id=db_server.id,
            hetzner_id=db_server.hetzner_id,
            name=db_server.name,
            status=db_server.status,
            server_type=db_server.server_type,
            datacenter=db_server.datacenter,
            ipv4_address=db_server.ipv4_address,
            ipv6_address=db_server.ipv6_address,
            labels=db_server.labels or {},
            created_at=db_server.created_at,
            monthly_cost=db_server.monthly_cost
        )
        
    except HetznerAPIException as e:
        logger.error(f"Hetzner API error: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Hetzner API error: {str(e)}"
        )
    except Exception as e:
        logger.error(f"Error creating server: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to create server"
        )

@app.get("/servers/{server_id}", response_model=ServerResponse)
async def get_server(
    server_id: int,
    current_user: User = Depends(get_current_user),
    db = Depends(get_db)
):
    """Get a specific server"""
    try:
        server = db.query(Server).filter(
            Server.id == server_id,
            Server.user_id == current_user.id
        ).first()
        
        if not server:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Server not found"
            )
        
        # Get latest info from Hetzner
        hetzner_server = await hetzner_client.get_server(server.hetzner_id)
        
        # Update database
        server.status = hetzner_server['status']
        server.ipv4_address = hetzner_server['public_net']['ipv4']['ip'] if hetzner_server['public_net']['ipv4'] else None
        server.ipv6_address = hetzner_server['public_net']['ipv6']['ip'] if hetzner_server['public_net']['ipv6'] else None
        db.commit()
        
        return ServerResponse(
            id=server.id,
            hetzner_id=server.hetzner_id,
            name=server.name,
            status=server.status,
            server_type=server.server_type,
            datacenter=server.datacenter,
            ipv4_address=server.ipv4_address,
            ipv6_address=server.ipv6_address,
            labels=server.labels or {},
            created_at=server.created_at,
            monthly_cost=server.monthly_cost
        )
        
    except Exception as e:
        logger.error(f"Error getting server: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve server"
        )

@app.post("/servers/{server_id}/power", response_model=PowerActionResponse)
async def power_action(
    server_id: int,
    request: PowerActionRequest,
    current_user: User = Depends(get_current_user),
    db = Depends(get_db)
):
    """Execute power action on server"""
    try:
        server = db.query(Server).filter(
            Server.id == server_id,
            Server.user_id == current_user.id
        ).first()
        
        if not server:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Server not found"
            )
        
        # Execute power action via Hetzner API
        if request.action == "start":
            result = await hetzner_client.power_on_server(server.hetzner_id)
        elif request.action == "stop":
            result = await hetzner_client.power_off_server(server.hetzner_id)
        elif request.action == "reboot":
            result = await hetzner_client.reboot_server(server.hetzner_id)
        elif request.action == "reset":
            result = await hetzner_client.reset_server(server.hetzner_id)
        else:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"Invalid power action: {request.action}"
            )
        
        # Update server status
        server.status = "changing"  # Temporary status during action
        db.commit()
        
        logger.info(f"Power action {request.action} executed for server {server_id}")
        
        return PowerActionResponse(
            server_id=server_id,
            action=request.action,
            status="success",
            message=f"Server {request.action} initiated successfully"
        )
        
    except HetznerAPIException as e:
        logger.error(f"Hetzner API error during power action: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Power action failed: {str(e)}"
        )
    except Exception as e:
        logger.error(f"Error executing power action: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to execute power action"
        )

@app.get("/servers/{server_id}/metrics", response_model=ServerMetricsResponse)
async def get_server_metrics(
    server_id: int,
    current_user: User = Depends(get_current_user),
    db = Depends(get_db)
):
    """Get server metrics"""
    try:
        server = db.query(Server).filter(
            Server.id == server_id,
            Server.user_id == current_user.id
        ).first()
        
        if not server:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Server not found"
            )
        
        # Get metrics from Hetzner API
        metrics = await hetzner_client.get_server_metrics(server.hetzner_id)
        
        return ServerMetricsResponse(
            server_id=server_id,
            cpu_usage=metrics.get('cpu', 0),
            memory_usage=metrics.get('memory', 0),
            disk_usage=metrics.get('disk', 0),
            network_in=metrics.get('network_in', 0),
            network_out=metrics.get('network_out', 0),
            timestamp=metrics.get('timestamp')
        )
        
    except Exception as e:
        logger.error(f"Error getting server metrics: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve server metrics"
        )

@app.delete("/servers/{server_id}")
async def delete_server(
    server_id: int,
    current_user: User = Depends(get_current_user),
    db = Depends(get_db)
):
    """Delete a server"""
    try:
        server = db.query(Server).filter(
            Server.id == server_id,
            Server.user_id == current_user.id
        ).first()
        
        if not server:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Server not found"
            )
        
        # Delete from Hetzner Cloud
        await hetzner_client.delete_server(server.hetzner_id)
        
        # Delete from database
        db.delete(server)
        db.commit()
        
        logger.info(f"Server {server_id} deleted for user {current_user.id}")
        
        return {"message": "Server deleted successfully"}
        
    except HetznerAPIException as e:
        logger.error(f"Hetzner API error during server deletion: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Server deletion failed: {str(e)}"
        )
    except Exception as e:
        logger.error(f"Error deleting server: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to delete server"
        )

# Server types and locations endpoints
@app.get("/server-types")
async def get_server_types():
    """Get available server types"""
    try:
        return await hetzner_client.get_server_types()
    except Exception as e:
        logger.error(f"Error getting server types: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve server types"
        )

@app.get("/locations")
async def get_locations():
    """Get available locations"""
    try:
        return await hetzner_client.get_locations()
    except Exception as e:
        logger.error(f"Error getting locations: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve locations"
        )

@app.get("/ssh-keys")
async def get_ssh_keys(current_user: User = Depends(get_current_user)):
    """Get user's SSH keys"""
    try:
        return await hetzner_client.get_ssh_keys()
    except Exception as e:
        logger.error(f"Error getting SSH keys: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve SSH keys"
        )

if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8000,
        reload=True,
        log_level="info"
    )