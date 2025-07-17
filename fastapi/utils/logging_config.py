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
        details: Optional[Dict[str, Any]] = None
    ):
        super().__init__(
            message=message,
            status_code=status_code,
            details=details,
            error_code=hetzner_error_code
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

class DatabaseException(BaseAPIException):
    """Exception for database operation errors"""
    
    def __init__(
        self, 
        message: str, 
        operation: Optional[str] = None, 
        table: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        super().__init__(
            message=message,
            status_code=500,
            details=details,
            error_code="DATABASE_ERROR"
        )
        self.operation = operation
        self.table = table
        
        if operation:
            self.details["operation"] = operation
        if table:
            self.details["table"] = table

class AuthenticationException(BaseAPIException):
    """Exception for authentication related errors"""
    
    def __init__(
        self, 
        message: str = "Authentication failed", 
        details: Optional[Dict[str, Any]] = None
    ):
        super().__init__(
            message=message,
            status_code=401,
            details=details,
            error_code="AUTH_FAILED"
        )

class AuthorizationException(BaseAPIException):
    """Exception for authorization/permission related errors"""
    
    def __init__(
        self, 
        message: str = "Insufficient permissions", 
        required_permission: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if required_permission:
            details["required_permission"] = required_permission
            
        super().__init__(
            message=message,
            status_code=403,
            details=details,
            error_code="INSUFFICIENT_PERMISSIONS"
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

class QuotaExceededException(BaseAPIException):
    """Exception for resource quota exceeded errors"""
    
    def __init__(
        self, 
        resource_type: str, 
        current_usage: int, 
        quota_limit: int,
        user_id: Optional[int] = None
    ):
        message = f"Quota exceeded for {resource_type}: {current_usage}/{quota_limit}"
        details = {
            "resource_type": resource_type,
            "current_usage": current_usage,
            "quota_limit": quota_limit
        }
        
        if user_id:
            details["user_id"] = user_id
            
        super().__init__(
            message=message,
            status_code=429,
            details=details,
            error_code="QUOTA_EXCEEDED"
        )

class ResourceNotFoundException(BaseAPIException):
    """Exception for resource not found errors"""
    
    def __init__(
        self, 
        resource_type: str, 
        resource_id: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        message = f"{resource_type} not found"
        if resource_id:
            message += f" (ID: {resource_id})"
            
        details = details or {}
        details["resource_type"] = resource_type
        if resource_id:
            details["resource_id"] = resource_id
            
        super().__init__(
            message=message,
            status_code=404,
            details=details,
            error_code="RESOURCE_NOT_FOUND"
        )

class ConflictException(BaseAPIException):
    """Exception for resource conflict errors"""
    
    def __init__(
        self, 
        message: str, 
        resource_type: Optional[str] = None,
        conflict_reason: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if resource_type:
            details["resource_type"] = resource_type
        if conflict_reason:
            details["conflict_reason"] = conflict_reason
            
        super().__init__(
            message=message,
            status_code=409,
            details=details,
            error_code="RESOURCE_CONFLICT"
        )

class RateLimitException(BaseAPIException):
    """Exception for rate limiting errors"""
    
    def __init__(
        self, 
        message: str = "Rate limit exceeded", 
        retry_after: Optional[int] = None,
        limit_type: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if retry_after:
            details["retry_after"] = retry_after
        if limit_type:
            details["limit_type