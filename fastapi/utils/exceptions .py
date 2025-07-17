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
            details["limit_type"] = limit_type
            
        super().__init__(
            message=message,
            status_code=429,
            details=details,
            error_code="RATE_LIMIT_EXCEEDED"
        )

class ExternalServiceException(BaseAPIException):
    """Exception for external service errors"""
    
    def __init__(
        self, 
        service_name: str, 
        message: str, 
        status_code: int = 502,
        service_error_code: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        details["service_name"] = service_name
        if service_error_code:
            details["service_error_code"] = service_error_code
            
        super().__init__(
            message=f"{service_name}: {message}",
            status_code=status_code,
            details=details,
            error_code="EXTERNAL_SERVICE_ERROR"
        )

class ConfigurationException(BaseAPIException):
    """Exception for configuration errors"""
    
    def __init__(
        self, 
        message: str, 
        config_key: Optional[str] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if config_key:
            details["config_key"] = config_key
            
        super().__init__(
            message=message,
            status_code=500,
            details=details,
            error_code="CONFIGURATION_ERROR"
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

class MaintenanceException(BaseAPIException):
    """Exception for maintenance mode errors"""
    
    def __init__(
        self, 
        message: str = "Service temporarily unavailable for maintenance",
        maintenance_end: Optional[datetime] = None,
        details: Optional[Dict[str, Any]] = None
    ):
        details = details or {}
        if maintenance_end:
            details["maintenance_end"] = maintenance_end.isoformat()
            
        super().__init__(
            message=message,
            status_code=503,
            details=details,
            error_code="MAINTENANCE_MODE"
        )

# Exception mapping for common HTTP status codes
EXCEPTION_STATUS_MAP = {
    400: ValidationException,
    401: AuthenticationException,
    403: AuthorizationException,
    404: ResourceNotFoundException,
    409: ConflictException,
    422: ValidationException,
    429: RateLimitException,
    500: BaseAPIException,
    502: ExternalServiceException,
    503: MaintenanceException,
    504: TimeoutException
}

def create_exception_from_status(
    status_code: int, 
    message: str, 
    details: Optional[Dict[str, Any]] = None
) -> BaseAPIException:
    """Create appropriate exception based on HTTP status code"""
    exception_class = EXCEPTION_STATUS_MAP.get(status_code, BaseAPIException)
    return exception_class(message=message, details=details)

def handle_sqlalchemy_error(error: Exception) -> DatabaseException:
    """Convert SQLAlchemy errors to DatabaseException"""
    error_str = str(error)
    
    # Determine operation type from error message
    operation = None
    if "SELECT" in error_str.upper():
        operation = "select"
    elif "INSERT" in error_str.upper():
        operation = "insert"
    elif "UPDATE" in error_str.upper():
        operation = "update"
    elif "DELETE" in error_str.upper():
        operation = "delete"
    
    # Check for specific error types
    if "duplicate" in error_str.lower() or "unique" in error_str.lower():
        return DatabaseException(
            message="Duplicate entry violation",
            operation=operation,
            details={"original_error": error_str}
        )
    elif "foreign key" in error_str.lower():
        return DatabaseException(
            message="Foreign key constraint violation",
            operation=operation,
            details={"original_error": error_str}
        )
    elif "connection" in error_str.lower():
        return DatabaseException(
            message="Database connection error",
            operation=operation,
            details={"original_error": error_str}
        )
    else:
        return DatabaseException(
            message=f"Database operation failed: {error_str}",
            operation=operation,
            details={"original_error": error_str}
        )

def handle_httpx_error(error: Exception, endpoint: str = None) -> NetworkException:
    """Convert httpx errors to NetworkException"""
    error_str = str(error)
    
    if "timeout" in error_str.lower():
        return TimeoutException(
            message=f"Request timeout: {error_str}",
            endpoint=endpoint,
            details={"original_error": error_str}
        )
    elif "connection" in error_str.lower():
        return NetworkException(
            message=f"Connection error: {error_str}",
            endpoint=endpoint,
            details={"original_error": error_str}
        )
    else:
        return NetworkException(
            message=f"Network error: {error_str}",
            endpoint=endpoint,
            details={"original_error": error_str}
        )

# Error severity levels
class ErrorSeverity:
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    CRITICAL = "critical"

def get_error_severity(exception: BaseAPIException) -> str:
    """Determine error severity based on exception type and status code"""
    if isinstance(exception, (DatabaseException, ConfigurationException)):
        return ErrorSeverity.HIGH
    elif isinstance(exception, (AuthenticationException, AuthorizationException)):
        return ErrorSeverity.MEDIUM
    elif isinstance(exception, HetznerAPIException) and exception.status_code >= 500:
        return ErrorSeverity.HIGH
    elif exception.status_code >= 500:
        return ErrorSeverity.HIGH
    elif exception.status_code >= 400:
        return ErrorSeverity.MEDIUM
    else:
        return ErrorSeverity.LOW

# Exception context manager for error tracking
class ErrorContext:
    """Context manager for tracking errors in operations"""
    
    def __init__(self, operation: str, user_id: Optional[int] = None):
        self.operation = operation
        self.user_id = user_id
        self.start_time = datetime.utcnow()
        self.exception = None
    
    def __enter__(self):
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        end_time = datetime.utcnow()
        duration = (end_time - self.start_time).total_seconds()
        
        if exc_type:
            self.exception = exc_val
            severity = get_error_severity(exc_val) if isinstance(exc_val, BaseAPIException) else ErrorSeverity.HIGH
            
            logger.error(
                f"Operation failed: {self.operation}",
                extra={
                    "operation": self.operation,
                    "user_id": self.user_id,
                    "duration": duration,
                    "exception_type": exc_type.__name__,
                    "exception_message": str(exc_val),
                    "severity": severity
                }
            )
        else:
            logger.info(
                f"Operation completed: {self.operation}",
                extra={
                    "operation": self.operation,
                    "user_id": self.user_id,
                    "duration": duration
                }
            )
    
    def add_context(self, **kwargs):
        """Add additional context to error logging"""
        for key, value in kwargs.items():
            setattr(self, key, value)