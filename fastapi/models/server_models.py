from pydantic import BaseModel, Field, validator
from typing import List, Dict, Optional, Any
from datetime import datetime
from enum import Enum

class ServerStatus(str, Enum):
    INITIALIZING = "initializing"
    STARTING = "starting"
    RUNNING = "running"
    STOPPING = "stopping"
    STOPPED = "stopped"
    REBOOTING = "rebooting"
    REBUILDING = "rebuilding"
    MIGRATING = "migrating"
    UNKNOWN = "unknown"

class PowerAction(str, Enum):
    START = "start"
    STOP = "stop"
    REBOOT = "reboot"
    RESET = "reset"
    SHUTDOWN = "shutdown"

class ServerCreateRequest(BaseModel):
    """Request model for creating a new server"""
    name: str = Field(..., min_length=1, max_length=63, description="Server name")
    server_type: str = Field(..., description="Server type (e.g., cx11, cx21)")
    location: str = Field(..., description="Location name (e.g., fsn1, nbg1)")
    image: str = Field(default="ubuntu-22.04", description="OS image")
    ssh_keys: Optional[List[str]] = Field(default=None, description="SSH key names or IDs")
    user_data: Optional[str] = Field(default=None, description="Cloud-init user data")
    labels: Optional[Dict[str, str]] = Field(default_factory=dict, description="Labels for the server")
    enable_backups: bool = Field(default=False, description="Enable automatic backups")
    enable_protection: bool = Field(default=False, description="Enable delete protection")
    networks: Optional[List[int]] = Field(default=None, description="Network IDs to attach")
    volumes: Optional[List[int]] = Field(default=None, description="Volume IDs to attach")
    firewalls: Optional[List[int]] = Field(default=None, description="Firewall IDs to apply")

    @validator('name')
    def validate_name(cls, v):
        if not v.replace('-', '').replace('_', '').isalnum():
            raise ValueError('Name must contain only alphanumeric characters, hyphens, and underscores')
        return v

    @validator('labels')
    def validate_labels(cls, v):
        if v and len(v) > 64:
            raise ValueError('Maximum 64 labels allowed')
        for key, value in v.items():
            if len(key) > 63 or len(value) > 255:
                raise ValueError('Label key max 63 chars, value max 255 chars')
        return v

class ServerResponse(BaseModel):
    """Response model for server data"""
    id: int
    hetzner_id: int
    name: str
    status: ServerStatus
    server_type: str
    datacenter: str
    ipv4_address: Optional[str] = None
    ipv6_address: Optional[str] = None
    labels: Dict[str, str] = Field(default_factory=dict)
    created_at: datetime
    monthly_cost: Optional[float] = None

    class Config:
        orm_mode = True

class ServerListResponse(BaseModel):
    """Response model for server list"""
    servers: List[ServerResponse]
    count: int

class PowerActionRequest(BaseModel):
    """Request model for power actions"""
    action: PowerAction = Field(..., description="Power action to execute")
    force: bool = Field(default=False, description="Force action (for stop/reset)")

class PowerActionResponse(BaseModel):
    """Response model for power actions"""
    server_id: int
    action: PowerAction
    status: str
    message: str
    action_id: Optional[int] = None

class ServerMetricsResponse(BaseModel):
    """Response model for server metrics"""
    server_id: int
    cpu_usage: Optional[float] = None
    memory_usage: Optional[float] = None
    disk_usage: Optional[float] = None
    network_in: Optional[float] = None
    network_out: Optional[float] = None
    timestamp: Optional[datetime] = None

class ServerTypeResponse(BaseModel):
    """Response model for server types"""
    id: int
    name: str
    description: str
    cores: int
    memory: float
    disk: int
    prices: List[Dict[str, Any]]
    storage_type: str
    cpu_type: str
    architecture: str

class LocationResponse(BaseModel):
    """Response model for locations"""
    id: int
    name: str
    description: str
    country: str
    city: str
    latitude: float
    longitude: float
    network_zone: str

class ImageResponse(BaseModel):
    """Response model for images"""
    id: int
    name: str
    description: str
    type: str
    status: str
    architecture: str
    os_flavor: str
    os_version: str
    created: datetime
    created_from: Optional[Dict[str, Any]] = None
    bound_to: Optional[int] = None
    disk_size: float
    labels: Dict[str, str] = Field(default_factory=dict)

class SSHKeyResponse(BaseModel):
    """Response model for SSH keys"""
    id: int
    name: str
    fingerprint: str
    public_key: str
    labels: Dict[str, str] = Field(default_factory=dict)

class SSHKeyCreateRequest(BaseModel):
    """Request model for creating SSH keys"""
    name: str = Field(..., min_length=1, max_length=63)
    public_key: str = Field(..., min_length=1)
    labels: Optional[Dict[str, str]] = Field(default_factory=dict)

class VolumeResponse(BaseModel):
    """Response model for volumes"""
    id: int
    name: str
    size: int
    location: str
    status: str
    created: datetime
    server: Optional[int] = None
    labels: Dict[str, str] = Field(default_factory=dict)
    linux_device: Optional[str] = None

class VolumeCreateRequest(BaseModel):
    """Request model for creating volumes"""
    name: str = Field(..., min_length=1, max_length=63)
    size: int = Field(..., ge=10, le=10000, description="Size in GB")
    location: str = Field(..., description="Location name")
    labels: Optional[Dict[str, str]] = Field(default_factory=dict)
    linux_device: Optional[str] = Field(default=None, description="Linux device path")

class VolumeAttachRequest(BaseModel):
    """Request model for attaching volumes"""
    volume_id: int
    automount: bool = Field(default=False)

class NetworkResponse(BaseModel):
    """Response model for networks"""
    id: int
    name: str
    ip_range: str
    subnets: List[Dict[str, Any]]
    routes: List[Dict[str, Any]]
    servers: List[int]
    location: str
    labels: Dict[str, str] = Field(default_factory=dict)

class FloatingIPResponse(BaseModel):
    """Response model for floating IPs"""
    id: int
    ip: str
    type: str
    server: Optional[int] = None
    home_location: str
    blocked: bool
    dns_ptr: List[Dict[str, str]]
    labels: Dict[str, str] = Field(default_factory=dict)

class FirewallResponse(BaseModel):
    """Response model for firewalls"""
    id: int
    name: str
    rules: List[Dict[str, Any]]
    applied_to: List[Dict[str, Any]]
    labels: Dict[str, str] = Field(default_factory=dict)

class BackupResponse(BaseModel):
    """Response model for backups"""
    id: int
    name: str
    description: str
    created: datetime
    size: Optional[float] = None
    labels: Dict[str, str] = Field(default_factory=dict)
    created_from: Dict[str, Any]

class ActionResponse(BaseModel):
    """Response model for actions"""
    id: int
    command: str
    status: str
    progress: int
    started: datetime
    finished: Optional[datetime] = None
    error: Optional[Dict[str, Any]] = None
    resources: List[Dict[str, Any]]

class ServerUpdateRequest(BaseModel):
    """Request model for updating server settings"""
    name: Optional[str] = Field(default=None, min_length=1, max_length=63)
    labels: Optional[Dict[str, str]] = Field(default=None)

class ServerRebuildRequest(BaseModel):
    """Request model for rebuilding servers"""
    image: str = Field(..., description="Image name or ID")
    return_response: bool = Field(default=False)

class ServerResizeRequest(BaseModel):
    """Request model for resizing servers"""
    server_type: str = Field(..., description="New server type")
    upgrade_disk: bool = Field(default=False, description="Upgrade disk size")

class ServerRescueRequest(BaseModel):
    """Request model for rescue mode"""
    type: str = Field(default="linux64", description="Rescue type")
    ssh_keys: Optional[List[str]] = Field(default=None, description="SSH keys for rescue")

class ConsoleResponse(BaseModel):
    """Response model for console access"""
    wss_url: str
    password: str

class PricingResponse(BaseModel):
    """Response model for pricing"""
    currency: str
    vat_rate: str
    image: Dict[str, Any]
    floating_ip: Dict[str, Any]
    traffic: Dict[str, Any]
    server_backup: Dict[str, Any]
    server_types: Dict[str, Any]
    load_balancer_types: Dict[str, Any]
    volume: Dict[str, Any]

class ErrorResponse(BaseModel):
    """Response model for errors"""
    error: str
    message: str
    details: Optional[Dict[str, Any]] = None

class HealthCheckResponse(BaseModel):
    """Response model for health check"""
    status: str
    timestamp: datetime
    version: str
    hetzner_api_accessible: bool