import httpx
import os
from typing import Dict, Any
from utils.exceptions import HetznerAPIException, NetworkException, TimeoutException

class HetznerClient:
    def __init__(self):
        self.api_token = os.getenv("HETZNER_API_TOKEN")
        if not self.api_token:
            raise HetznerAPIException("HETZNER_API_TOKEN not configured", 500, error_code="MISSING_CONFIG")
        self.base_url = "https://api.hetzner-cloud.com/v1"
        
    async def _request(self, method: str, endpoint: str, data: Dict = None) -> Dict[str, Any]:
        headers = {
            "Authorization": f"Bearer {self.api_token}",
            "Content-Type": "application/json"
        }
        
        async with httpx.AsyncClient(timeout=30.0) as client:
            try:
                response = await client.request(
                    method=method,
                    url=f"{self.base_url}{endpoint}",
                    headers=headers,
                    json=data
                )
                
                if response.status_code == 401:
                    raise HetznerAPIException("Invalid Hetzner API token", 401)
                elif response.status_code == 403:
                    raise HetznerAPIException("Insufficient permissions", 403)
                elif response.status_code == 404:
                    raise HetznerAPIException("Resource not found", 404)
                elif response.status_code == 422:
                    error_data = response.json()
                    raise HetznerAPIException.from_hetzner_response(error_data, 422)
                elif response.status_code >= 400:
                    try:
                        error_data = response.json()
                        raise HetznerAPIException.from_hetzner_response(error_data, response.status_code)
                    except Exception:
                        raise HetznerAPIException(f"API error: {response.status_code}", response.status_code)
                    
                return response.json()
                
            except httpx.TimeoutException:
                raise TimeoutException("Hetzner API request timeout", operation="hetzner_api_call")
            except httpx.RequestError as e:
                raise NetworkException(f"Network error: {str(e)}", endpoint=f"{self.base_url}{endpoint}")
    
    # Server operations
    async def get_servers(self) -> Dict[str, Any]:
        return await self._request("GET", "/servers")
    
    async def get_server(self, server_id: int) -> Dict[str, Any]:
        return await self._request("GET", f"/servers/{server_id}")
    
    async def create_server(self, data: Dict[str, Any]) -> Dict[str, Any]:
        return await self._request("POST", "/servers", data)
    
    async def delete_server(self, server_id: int) -> Dict[str, Any]:
        return await self._request("DELETE", f"/servers/{server_id}")
    
    # Power management
    async def server_action(self, server_id: int, action: str) -> Dict[str, Any]:
        valid_actions = ["start", "stop", "restart", "reset", "shutdown"]
        if action not in valid_actions:
            raise HetznerAPIException(f"Invalid action. Must be one of: {', '.join(valid_actions)}", 400)
            
        data = {"type": action}
        return await self._request("POST", f"/servers/{server_id}/actions", data)
    
    # Resource listings
    async def get_server_types(self) -> Dict[str, Any]:
        return await self._request("GET", "/server_types")
    
    async def get_images(self) -> Dict[str, Any]:
        return await self._request("GET", "/images")
    
    async def get_datacenters(self) -> Dict[str, Any]:
        return await self._request("GET", "/datacenters")