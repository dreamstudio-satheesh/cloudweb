import httpx
import asyncio
from typing import List, Dict, Optional, Any
import logging
from datetime import datetime, timedelta
import json
import time

from utils.exceptions import HetznerAPIException
from utils.rate_limiter import RateLimiter

logger = logging.getLogger(__name__)

class HetznerClient:
    """
    Async client for Hetzner Cloud API
    Handles authentication, rate limiting, and error handling
    """
    
    def __init__(self, api_token: str, rate_limit_per_second: int = 10):
        self.api_token = api_token
        self.base_url = "https://api.hetzner.cloud/v1"
        self.rate_limiter = RateLimiter(rate_limit_per_second)
        
        # HTTP client with proper timeout and retry configuration
        self.client = httpx.AsyncClient(
            timeout=httpx.Timeout(30.0, connect=10.0),
            headers={
                "Authorization": f"Bearer {api_token}",
                "Content-Type": "application/json"
            }
        )
    
    async def __aenter__(self):
        return self
    
    async def __aexit__(self, exc_type, exc_val, exc_tb):
        await self.client.aclose()
    
    async def _make_request(
        self, 
        method: str, 
        endpoint: str, 
        data: Optional[Dict] = None,
        params: Optional[Dict] = None
    ) -> Dict[str, Any]:
        """Make authenticated request to Hetzner API with rate limiting"""
        
        # Apply rate limiting
        await self.rate_limiter.acquire()
        
        url = f"{self.base_url}/{endpoint}"
        
        try:
            response = await self.client.request(
                method=method,
                url=url,
                json=data,
                params=params
            )
            
            # Handle rate limiting
            if response.status_code == 429:
                retry_after = int(response.headers.get("Retry-After", 1))
                logger.warning(f"Rate limited, waiting {retry_after} seconds")
                await asyncio.sleep(retry_after)
                return await self._make_request(method, endpoint, data, params)
            
            # Handle errors
            if response.status_code >= 400:
                error_data = response.json() if response.content else {}
                error_msg = error_data.get("error", {}).get("message", "Unknown error")
                logger.error(f"API Error {response.status_code}: {error_msg}")
                raise HetznerAPIException(f"API Error: {error_msg}", response.status_code)
            
            return response.json()
            
        except httpx.TimeoutException:
            logger.error("Request timeout")
            raise HetznerAPIException("Request timeout")
        except httpx.RequestError as e:
            logger.error(f"Request error: {str(e)}")
            raise HetznerAPIException(f"Request failed: {str(e)}")
    
    async def list_servers(self, label_selector: Optional[str] = None) -> List[Dict]:
        """List all servers"""
        params = {}
        if label_selector:
            params["label_selector"] = label_selector
        
        response = await self._make_request("GET", "servers", params=params)
        return response.get("servers", [])
    
    async def get_server(self, server_id: int) -> Dict:
        """Get specific server by ID"""
        response = await self._make_request("GET", f"servers/{server_id}")
        return response.get("server", {})
    
    async def create_server(
        self,
        name: str,
        server_type: str,
        location: str,
        image: str = "ubuntu-22.04",
        ssh_keys: Optional[List[str]] = None,
        user_data: Optional[str] = None,
        labels: Optional[Dict[str, str]] = None,
        enable_backups: bool = False,
        enable_protection: bool = False,
        networks: Optional[List[int]] = None,
        volumes: Optional[List[int]] = None,
        firewalls: Optional[List[int]] = None
    ) -> Dict:
        """Create a new server"""
        
        data = {
            "name": name,
            "server_type": server_type,
            "location": location,
            "image": image,
            "start_after_create": True,
            "public_net": {
                "enable_ipv4": True,
                "enable_ipv6": True
            }
        }
        
        if ssh_keys:
            data["ssh_keys"] = ssh_keys
        
        if user_data:
            data["user_data"] = user_data
        
        if labels:
            data["labels"] = labels
        
        if enable_backups:
            data["automount"] = True
        
        if enable_protection:
            data["protection"] = {
                "delete": True,
                "rebuild": True
            }
        
        if networks:
            data["networks"] = networks
        
        if volumes:
            data["volumes"] = volumes
        
        if firewalls:
            data["firewalls"] = [{"firewall": fw_id} for fw_id in firewalls]
        
        logger.info(f"Creating server: {name}")
        response = await self._make_request("POST", "servers", data=data)
        
        return response
    
    async def delete_server(self, server_id: int) -> Dict:
        """Delete a server"""
        logger.info(f"Deleting server: {server_id}")
        response = await self._make_request("DELETE", f"servers/{server_id}")
        return response
    
    async def power_on_server(self, server_id: int) -> Dict:
        """Power on a server"""
        data = {"type": "poweron"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def power_off_server(self, server_id: int) -> Dict:
        """Power off a server"""
        data = {"type": "poweroff"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def reboot_server(self, server_id: int) -> Dict:
        """Reboot a server"""
        data = {"type": "reboot"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def reset_server(self, server_id: int) -> Dict:
        """Reset a server"""
        data = {"type": "reset"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def shutdown_server(self, server_id: int) -> Dict:
        """Gracefully shutdown a server"""
        data = {"type": "shutdown"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def get_server_actions(self, server_id: int) -> List[Dict]:
        """Get all actions for a server"""
        response = await self._make_request("GET", f"servers/{server_id}/actions")
        return response.get("actions", [])
    
    async def get_server_metrics(
        self, 
        server_id: int, 
        type_: str = "cpu,disk,network", 
        start: Optional[datetime] = None,
        end: Optional[datetime] = None
    ) -> Dict:
        """Get server metrics"""
        params = {"type": type_}
        
        if start:
            params["start"] = start.isoformat()
        else:
            params["start"] = (datetime.utcnow() - timedelta(hours=1)).isoformat()
        
        if end:
            params["end"] = end.isoformat()
        else:
            params["end"] = datetime.utcnow().isoformat()
        
        response = await self._make_request("GET", f"servers/{server_id}/metrics", params=params)
        
        # Process metrics to get latest values
        metrics = response.get("metrics", {})
        processed_metrics = {}
        
        for metric_type, metric_data in metrics.items():
            if metric_data.get("values"):
                # Get the latest value
                latest_value = metric_data["values"][-1]
                processed_metrics[metric_type] = {
                    "value": latest_value[1],
                    "timestamp": latest_value[0]
                }
        
        return processed_metrics
    
    async def get_server_types(self) -> List[Dict]:
        """Get available server types"""
        response = await self._make_request("GET", "server_types")
        return response.get("server_types", [])
    
    async def get_locations(self) -> List[Dict]:
        """Get available locations"""
        response = await self._make_request("GET", "locations")
        return response.get("locations", [])
    
    async def get_datacenters(self) -> List[Dict]:
        """Get available datacenters"""
        response = await self._make_request("GET", "datacenters")
        return response.get("datacenters", [])
    
    async def get_images(self, type_: str = "system") -> List[Dict]:
        """Get available images"""
        params = {"type": type_}
        response = await self._make_request("GET", "images", params=params)
        return response.get("images", [])
    
    async def get_ssh_keys(self) -> List[Dict]:
        """Get SSH keys"""
        response = await self._make_request("GET", "ssh_keys")
        return response.get("ssh_keys", [])
    
    async def create_ssh_key(self, name: str, public_key: str, labels: Optional[Dict] = None) -> Dict:
        """Create SSH key"""
        data = {
            "name": name,
            "public_key": public_key
        }
        if labels:
            data["labels"] = labels
        
        response = await self._make_request("POST", "ssh_keys", data=data)
        return response
    
    async def delete_ssh_key(self, key_id: int) -> Dict:
        """Delete SSH key"""
        response = await self._make_request("DELETE", f"ssh_keys/{key_id}")
        return response
    
    async def get_networks(self) -> List[Dict]:
        """Get networks"""
        response = await self._make_request("GET", "networks")
        return response.get("networks", [])
    
    async def get_volumes(self) -> List[Dict]:
        """Get volumes"""
        response = await self._make_request("GET", "volumes")
        return response.get("volumes", [])
    
    async def create_volume(
        self,
        name: str,
        size: int,
        location: str,
        labels: Optional[Dict] = None,
        linux_device: Optional[str] = None
    ) -> Dict:
        """Create a volume"""
        data = {
            "name": name,
            "size": size,
            "location": location
        }
        
        if labels:
            data["labels"] = labels
        
        if linux_device:
            data["linux_device"] = linux_device
        
        response = await self._make_request("POST", "volumes", data=data)
        return response
    
    async def attach_volume(self, volume_id: int, server_id: int, automount: bool = False) -> Dict:
        """Attach volume to server"""
        data = {
            "type": "attach_volume",
            "volume": volume_id,
            "automount": automount
        }
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def detach_volume(self, volume_id: int, server_id: int) -> Dict:
        """Detach volume from server"""
        data = {
            "type": "detach_volume",
            "volume": volume_id
        }
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def get_floating_ips(self) -> List[Dict]:
        """Get floating IPs"""
        response = await self._make_request("GET", "floating_ips")
        return response.get("floating_ips", [])
    
    async def assign_floating_ip(self, ip_id: int, server_id: int) -> Dict:
        """Assign floating IP to server"""
        data = {
            "type": "assign_floating_ip",
            "floating_ip": ip_id
        }
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def create_image(
        self,
        server_id: int,
        name: str,
        description: Optional[str] = None,
        labels: Optional[Dict] = None
    ) -> Dict:
        """Create image from server"""
        data = {
            "type": "create_image",
            "description": description or f"Backup of {name}",
            "labels": labels or {}
        }
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def enable_backup(self, server_id: int) -> Dict:
        """Enable backup for server"""
        data = {"type": "enable_backup"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def disable_backup(self, server_id: int) -> Dict:
        """Disable backup for server"""
        data = {"type": "disable_backup"}
        response = await self._make_request("POST", f"servers/{server_id}/actions", data=data)
        return response
    
    async def get_firewalls(self) -> List[Dict]:
        """Get firewalls"""
        response = await self._make_request("GET", "firewalls")
        return response.get("firewalls", [])
    
    async def get_pricing(self) -> Dict:
        """Get pricing information"""
        response = await self._make_request("GET", "pricing")
        return response.get("pricing", {})
    
    async def health_check(self) -> bool:
        """Check if Hetzner API is accessible"""
        try:
            await self._make_request("GET", "server_types")
            return True
        except Exception as e:
            logger.error(f"Health check failed: {str(e)}")
            return False