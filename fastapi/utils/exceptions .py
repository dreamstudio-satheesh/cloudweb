"""
Custom exception classes for the FastAPI application
Provides specific error types for different components and operations
"""

import logging
from typing import Dict, Any, Optional
from datetime import datetime

logger = logging.getLogger(__name__)

class BaseAPIException(Exception):
    """Base exception class for all API-related errors"""
    
    def __init__(
        self, 
        message: str, 
        status_code: int = 500, 
        details: Optional[Dict[str, Any]] = None,
        error_code: Optional[str] = None
    ):
        super().__init__(message)
        self.message = message
        self.status_code = status_code
        self.details = details or {}
        self.error_code = error_code
        self.timestamp = datetime.utcnow()
        
        # Log the exception
        logger.error(
            f"{self.__class__.__name__}: {message}",
            extra={
                "status_code": status_code,
                "error_code": error_code,
                "details": details,
                "timestamp": self.timestamp.isoformat()
            }
        )
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert exception to dictionary for API responses"""
        return {
            "error": self.__class__.__name__,
            "message": self.message,
            "status_code": self.status_code,
            "error_code": self.error_code,
            "details": self.details,
            "timestamp": self.timestamp.isoformat()
        }
    
    def __str__(self) -> str:
        return f"{self.__class__.__name__}: {self.message}"

class HetznerAPIException(BaseAPIException):
    """Exception for Hetzner Cloud API related errors"""
    
    def __init__(
        self, 
        message: str, 
        status_code: int = 500, 
        hetzner_error_code: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None,
        error_code: Optional[str] = None
    ):
        super().__init__(
            message=message,
            status_code=status_code,
            details=details,
            error_code=error_code or hetzner_error_code
        )
        self.hetzner_error_code = hetzner_error_code
    
    @classmethod
    def from_hetzner_response(cls, response_data: Dict[str, Any], status_code: int):
        """Create exception from Hetzner API error response"""
        error_info = response_data.get("error", {})
        
        return cls(
            message=error_info.get("message", "Unknown Hetzner API error"),
            status_code=status_code,
            hetzner_error_code=error_info.get("code"),
            details={
                "hetzner_response": response_data,
                "error_details": error_info.get("details", [])
            }
        )

class NetworkException(BaseAPIException):
    """Exception for network related errors"""
    
    def __init__(
        self, 
        message: str, 
        operation: Optional[str] = None,
        endpoint: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if operation:
            details["operation"] = operation
        if endpoint:
            details["endpoint"] = endpoint
            
        super().__init__(
            message=message,
            status_code=502,
            details=details,
            error_code="NETWORK_ERROR"
        )

class TimeoutException(BaseAPIException):
    """Exception for timeout errors"""
    
    def __init__(
        self, 
        message: str = "Operation timed out", 
        timeout_duration: Optional[float] = None,
        operation: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if timeout_duration:
            details["timeout_duration"] = timeout_duration
        if operation:
            details["operation"] = operation
            
        super().__init__(
            message=message,
            status_code=504,
            details=details,
            error_code="TIMEOUT_ERROR"
        )

class ValidationException(BaseAPIException):
    """Exception for input validation errors"""
    
    def __init__(
        self, 
        message: str, 
        field: Optional[str] = None, 
        value: Optional[str] = None,
        validation_errors: Optional[Dict[str, Any]] = None
    ):
        details = validation_errors or {}
        if field:
            details["field"] = field
        if value:
            details["value"] = value
            
        super().__init__(
            message=message,
            status_code=422,
            details=details,
            error_code="VALIDATION_ERROR"
        )