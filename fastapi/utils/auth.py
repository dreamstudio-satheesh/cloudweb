import jwt
import os
from datetime import datetime, timedelta
from typing import Optional, Dict, Any
from fastapi import HTTPException, status
import hashlib
import secrets
import logging

logger = logging.getLogger(__name__)

class AuthenticationError(Exception):
    """Raised when authentication fails"""
    pass

class JWTManager:
    """JWT token management for internal service authentication"""
    
    def __init__(self, secret_key: Optional[str] = None):
        self.secret_key = secret_key or os.getenv("INTERNAL_API_KEY", "fallback-secret-key")
        self.algorithm = "HS256"
        self.token_expire_minutes = 60
    
    def create_token(self, user_id: int, email: str, role: str = "client") -> str:
        """Create JWT token for internal service communication"""
        payload = {
            "user_id": user_id,
            "email": email,
            "role": role,
            "exp": datetime.utcnow() + timedelta(minutes=self.token_expire_minutes),
            "iat": datetime.utcnow(),
            "iss": "laravel-service"
        }
        
        return jwt.encode(payload, self.secret_key, algorithm=self.algorithm)
    
    def verify_token(self, token: str) -> Dict[str, Any]:
        """Verify and decode JWT token"""
        try:
            payload = jwt.decode(token, self.secret_key, algorithms=[self.algorithm])
            return payload
        except jwt.ExpiredSignatureError:
            logger.warning("Token expired")
            raise AuthenticationError("Token has expired")
        except jwt.InvalidTokenError as e:
            logger.warning(f"Invalid token: {str(e)}")
            raise AuthenticationError("Invalid token")

# Global JWT manager instance
jwt_manager = JWTManager()

def verify_internal_token(token: str) -> Dict[str, Any]:
    """
    Verify internal service token
    Used for Laravel -> FastAPI communication
    """
    try:
        return jwt_manager.verify_token(token)
    except AuthenticationError as e:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(e)
        )

def verify_api_key(api_key: str) -> Dict[str, Any]:
    """
    Verify API key authentication
    For direct API access (future implementation)
    """
    # This would check against database API keys
    # For now, just verify it's the internal key
    internal_key = os.getenv("INTERNAL_API_KEY")
    if not internal_key or api_key != internal_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid API key"
        )
    
    return {
        "user_id": None,
        "type": "internal",
        "permissions": ["*"]
    }

def hash_api_key(api_key: str) -> str:
    """Hash API key for storage"""
    salt = os.getenv("API_KEY_SALT", "default-salt")
    return hashlib.sha256(f"{api_key}{salt}".encode()).hexdigest()

def generate_api_key() -> str:
    """Generate secure API key"""
    return secrets.token_urlsafe(32)

def verify_password(plain_password: str, hashed_password: str) -> bool:
    """
    Verify password against hash
    For future direct authentication
    """
    import bcrypt
    return bcrypt.checkpw(plain_password.encode('utf-8'), hashed_password.encode('utf-8'))

def hash_password(password: str) -> str:
    """
    Hash password for storage
    For future direct authentication
    """
    import bcrypt
    salt = bcrypt.gensalt()
    return bcrypt.hashpw(password.encode('utf-8'), salt).decode('utf-8')

def create_access_token(user_id: int, email: str, role: str = "client") -> str:
    """Create access token for user"""
    return jwt_manager.create_token(user_id, email, role)

def verify_token_permissions(token_payload: Dict[str, Any], required_permission: str) -> bool:
    """
    Verify token has required permissions
    For future role-based access control
    """
    user_role = token_payload.get("role", "client")
    
    # Admin has all permissions
    if user_role == "admin":
        return True
    
    # Define permission mappings
    permission_mappings = {
        "client": [
            "server.read",
            "server.create",
            "server.update",
            "server.delete",
            "volume.read",
            "volume.create",
            "volume.update",
            "volume.delete",
            "ssh_key.read",
            "ssh_key.create",
            "ssh_key.update",
            "ssh_key.delete",
            "backup.read",
            "backup.create",
            "metrics.read"
        ],
        "readonly": [
            "server.read",
            "volume.read",
            "ssh_key.read",
            "backup.read",
            "metrics.read"
        ]
    }
    
    allowed_permissions = permission_mappings.get(user_role, [])
    return required_permission in allowed_permissions

class PermissionChecker:
    """Dependency for checking permissions"""
    
    def __init__(self, required_permission: str):
        self.required_permission = required_permission
    
    def __call__(self, token_payload: Dict[str, Any]) -> bool:
        if not verify_token_permissions(token_payload, self.required_permission):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Insufficient permissions"
            )
        return True

def require_permission(permission: str):
    """Decorator factory for permission checking"""
    return PermissionChecker(permission)

# Rate limiting for authentication attempts
class AuthRateLimiter:
    """Rate limiter for authentication attempts"""
    
    def __init__(self, max_attempts: int = 5, window_minutes: int = 15):
        self.max_attempts = max_attempts
        self.window_minutes = window_minutes
        self.attempts = {}  # In production, use Redis
    
    def is_allowed(self, identifier: str) -> bool:
        """Check if authentication attempt is allowed"""
        now = datetime.utcnow()
        window_start = now - timedelta(minutes=self.window_minutes)
        
        # Clean old attempts
        self.attempts = {
            key: attempts for key, attempts in self.attempts.items()
            if any(attempt > window_start for attempt in attempts)
        }
        
        # Check current attempts
        user_attempts = self.attempts.get(identifier, [])
        recent_attempts = [attempt for attempt in user_attempts if attempt > window_start]
        
        return len(recent_attempts) < self.max_attempts
    
    def record_attempt(self, identifier: str):
        """Record authentication attempt"""
        if identifier not in self.attempts:
            self.attempts[identifier] = []
        self.attempts[identifier].append(datetime.utcnow())

# Global rate limiter instance
auth_rate_limiter = AuthRateLimiter()

def check_auth_rate_limit(identifier: str):
    """Check authentication rate limit"""
    if not auth_rate_limiter.is_allowed(identifier):
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail="Too many authentication attempts. Please try again later."
        )
    
    auth_rate_limiter.record_attempt(identifier)

def extract_user_info(token_payload: Dict[str, Any]) -> Dict[str, Any]:
    """Extract user information from token payload"""
    return {
        "user_id": token_payload.get("user_id"),
        "email": token_payload.get("email"),
        "role": token_payload.get("role", "client"),
        "issued_at": token_payload.get("iat"),
        "expires_at": token_payload.get("exp")
    }

def validate_email(email: str) -> bool:
    """Validate email format"""
    import re
    pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
    return re.match(pattern, email) is not None

def sanitize_user_input(input_str: str) -> str:
    """Sanitize user input to prevent injection"""
    import html
    return html.escape(input_str.strip())

def log_security_event(event_type: str, user_id: Optional[int], details: Dict[str, Any]):
    """Log security-related events"""
    logger.warning(f"Security event: {event_type}", extra={
        "user_id": user_id,
        "event_type": event_type,
        "details": details,
        "timestamp": datetime.utcnow().isoformat()
    })

# Context manager for user authentication
class UserContext:
    """Context manager for user authentication state"""
    
    def __init__(self, user_id: int, email: str, role: str):
        self.user_id = user_id
        self.email = email
        self.role = role
        self.authenticated_at = datetime.utcnow()
    
    def has_permission(self, permission: str) -> bool:
        """Check if user has specific permission"""
        return verify_token_permissions({
            "user_id": self.user_id,
            "email": self.email,
            "role": self.role
        }, permission)
    
    def is_admin(self) -> bool:
        """Check if user is admin"""
        return self.role == "admin"
    
    def is_owner(self, resource_user_id: int) -> bool:
        """Check if user owns the resource"""
        return self.user_id == resource_user_id or self.is_admin()
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary"""
        return {
            "user_id": self.user_id,
            "email": self.email,
            "role": self.role,
            "authenticated_at": self.authenticated_at.isoformat()
        }