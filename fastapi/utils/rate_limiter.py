# utils/rate_limiter.py
import asyncio
import time
from typing import Dict, List
from datetime import datetime, timedelta

class RateLimiter:
    """Token bucket rate limiter for API requests"""
    
    def __init__(self, rate_per_second: float, burst_size: int = None):
        self.rate_per_second = rate_per_second
        self.burst_size = burst_size or int(rate_per_second * 2)
        self.tokens = self.burst_size
        self.last_refill = time.time()
        self.lock = asyncio.Lock()
    
    async def acquire(self, tokens: int = 1) -> bool:
        """Acquire tokens from the bucket"""
        async with self.lock:
            now = time.time()
            
            # Refill tokens based on elapsed time
            elapsed = now - self.last_refill
            new_tokens = elapsed * self.rate_per_second
            self.tokens = min(self.burst_size, self.tokens + new_tokens)
            self.last_refill = now
            
            if self.tokens >= tokens:
                self.tokens -= tokens
                return True
            else:
                # Calculate wait time
                wait_time = (tokens - self.tokens) / self.rate_per_second
                await asyncio.sleep(wait_time)
                self.tokens = max(0, self.tokens - tokens)
                return True

# utils/exceptions.py
class HetznerAPIException(Exception):
    """Exception for Hetzner API errors"""
    
    def __init__(self, message: str, status_code: int = None, details: dict = None):
        super().__init__(message)
        self.message = message
        self.status_code = status_code
        self.details = details or {}
    
    def __str__(self):
        return f"HetznerAPIException: {self.message}"

class DatabaseException(Exception):
    """Exception for database operations"""
    
    def __init__(self, message: str, operation: str = None, details: dict = None):
        super().__init__(message)
        self.message = message
        self.operation = operation
        self.details = details or {}
    
    def __str__(self):
        return f"DatabaseException: {self.message}"

class QuotaExceededException(Exception):
    """Exception for quota limits"""
    
    def __init__(self, resource_type: str, current: int, limit: int):
        self.resource_type = resource_type
        self.current = current
        self.limit = limit
        super().__init__(f"Quota exceeded for {resource_type}: {current}/{limit}")

class ValidationException(Exception):
    """Exception for validation errors"""
    
    def __init__(self, message: str, field: str = None, value: str = None):
        super().__init__(message)
        self.message = message
        self.field = field
        self.value = value

# utils/logging_config.py
import logging
import sys
import os
from datetime import datetime
import json

class JSONFormatter(logging.Formatter):
    """JSON formatter for structured logging"""
    
    def format(self, record):
        log_entry = {
            "timestamp": datetime.utcnow().isoformat(),
            "level": record.levelname,
            "logger": record.name,
            "message": record.getMessage(),
            "module": record.module,
            "function": record.funcName,
            "line": record.lineno
        }
        
        if hasattr(record, 'user_id'):
            log_entry["user_id"] = record.user_id
        
        if hasattr(record, 'request_id'):
            log_entry["request_id"] = record.request_id
        
        if record.exc_info:
            log_entry["exception"] = self.formatException(record.exc_info)
        
        return json.dumps(log_entry)

def setup_logging():
    """Setup logging configuration"""
    log_level = os.getenv("LOG_LEVEL", "INFO").upper()
    log_format = os.getenv("LOG_FORMAT", "json")  # json or text
    
    # Configure root logger
    logger = logging.getLogger()
    logger.setLevel(getattr(logging, log_level))
    
    # Remove existing handlers
    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
    
    # Create console handler
    console_handler = logging.StreamHandler(sys.stdout)
    
    if log_format == "json":
        console_handler.setFormatter(JSONFormatter())
    else:
        console_handler.setFormatter(logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        ))
    
    logger.addHandler(console_handler)
    
    # Set specific logger levels
    logging.getLogger("uvicorn").setLevel(logging.INFO)
    logging.getLogger("httpx").setLevel(logging.WARNING)
    logging.getLogger("sqlalchemy.engine").setLevel(logging.WARNING)
    
    return logger

# services/database.py
import os
from sqlalchemy import create_engine, pool
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, Session
from sqlalchemy.pool import StaticPool
import logging

logger = logging.getLogger(__name__)

DATABASE_URL = os.getenv("DATABASE_URL", "mysql+pymysql://user:password@localhost/cloud_hosting")

# Create engine with connection pooling
engine = create_engine(
    DATABASE_URL,
    poolclass=pool.QueuePool,
    pool_size=10,
    max_overflow=20,
    pool_pre_ping=True,
    pool_recycle=3600,
    echo=os.getenv("SQL_DEBUG", "false").lower() == "true"
)

# Create sessionmaker
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# Database dependency
def get_db():
    """Database dependency for FastAPI"""
    db = SessionLocal()
    try:
        yield db
    except Exception as e:
        logger.error(f"Database error: {str(e)}")
        db.rollback()
        raise
    finally:
        db.close()

def init_database():
    """Initialize database tables"""
    from models.database_models import Base
    Base.metadata.create_all(bind=engine)
    logger.info("Database initialized")

def check_database_health() -> bool:
    """Check database connection health"""
    try:
        db = SessionLocal()
        db.execute("SELECT 1")
        db.close()
        return True
    except Exception as e:
        logger.error(f"Database health check failed: {str(e)}")
        return False

class DatabaseManager:
    """Database connection manager"""
    
    def __init__(self):
        self.engine = engine
        self.SessionLocal = SessionLocal
    
    def get_session(self) -> Session:
        """Get database session"""
        return self.SessionLocal()
    
    def create_tables(self):
        """Create all database tables"""
        from models.database_models import Base
        Base.metadata.create_all(bind=self.engine)
    
    def drop_tables(self):
        """Drop all database tables"""
        from models.database_models import Base
        Base.metadata.drop_all(bind=self.engine)
    
    def health_check(self) -> bool:
        """Check database health"""
        return check_database_health()

# Global database manager instance
db_manager = DatabaseManager()

# Transaction context manager
class DatabaseTransaction:
    """Context manager for database transactions"""
    
    def __init__(self, db_session: Session = None):
        self.db_session = db_session or SessionLocal()
        self.should_close = db_session is None
    
    def __enter__(self):
        return self.db_session
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        if exc_type:
            self.db_session.rollback()
        else:
            self.db_session.commit()
        
        if self.should_close:
            self.db_session.close()

# Redis connection for caching
import redis
import json
from typing import Any, Optional

class RedisManager:
    """Redis connection manager"""
    
    def __init__(self):
        redis_url = os.getenv("REDIS_URL", "redis://localhost:6379")
        self.redis_client = redis.from_url(redis_url, decode_responses=True)
        self.default_ttl = 3600  # 1 hour
    
    def get(self, key: str) -> Optional[Any]:
        """Get value from Redis"""
        try:
            value = self.redis_client.get(key)
            return json.loads(value) if value else None
        except Exception as e:
            logger.error(f"Redis get error: {str(e)}")
            return None
    
    def set(self, key: str, value: Any, ttl: int = None) -> bool:
        """Set value in Redis"""
        try:
            ttl = ttl or self.default_ttl
            return self.redis_client.setex(key, ttl, json.dumps(value))
        except Exception as e:
            logger.error(f"Redis set error: {str(e)}")
            return False
    
    def delete(self, key: str) -> bool:
        """Delete key from Redis"""
        try:
            return bool(self.redis_client.delete(key))
        except Exception as e:
            logger.error(f"Redis delete error: {str(e)}")
            return False
    
    def exists(self, key: str) -> bool:
        """Check if key exists in Redis"""
        try:
            return bool(self.redis_client.exists(key))
        except Exception as e:
            logger.error(f"Redis exists error: {str(e)}")
            return False
    
    def health_check(self) -> bool:
        """Check Redis health"""
        try:
            return self.redis_client.ping()
        except Exception as e:
            logger.error(f"Redis health check failed: {str(e)}")
            return False

# Global Redis manager instance
redis_manager = RedisManager()

# Cache decorator
def cache_result(key_prefix: str, ttl: int = 3600):
    """Decorator for caching function results"""
    def decorator(func):
        async def wrapper(*args, **kwargs):
            # Generate cache key
            cache_key = f"{key_prefix}:{hash(str(args) + str(kwargs))}"
            
            # Try to get from cache
            cached_result = redis_manager.get(cache_key)
            if cached_result is not None:
                logger.debug(f"Cache hit for {cache_key}")
                return cached_result
            
            # Execute function
            result = await func(*args, **kwargs)
            
            # Cache result
            redis_manager.set(cache_key, result, ttl)
            logger.debug(f"Cache set for {cache_key}")
            
            return result
        return wrapper
    return decorator

# Health check functions
def check_all_services() -> dict:
    """Check health of all services"""
    return {
        "database": check_database_health(),
        "redis": redis_manager.health_check(),
        "timestamp": datetime.utcnow().isoformat()
    }

# Middleware for request logging
import uuid
from fastapi import Request, Response
from starlette.middleware.base import BaseHTTPMiddleware
import time

class RequestLoggingMiddleware(BaseHTTPMiddleware):
    """Middleware for request logging"""
    
    async def dispatch(self, request: Request, call_next):
        # Generate request ID
        request_id = str(uuid.uuid4())
        
        # Start timer
        start_time = time.time()
        
        # Log request
        logger.info(f"Request started: {request.method} {request.url}", extra={
            "request_id": request_id,
            "method": request.method,
            "url": str(request.url),
            "user_agent": request.headers.get("user-agent"),
            "ip": request.client.host
        })
        
        # Process request
        response = await call_next(request)
        
        # Calculate duration
        duration = time.time() - start_time
        
        # Log response
        logger.info(f"Request completed: {response.status_code}", extra={
            "request_id": request_id,
            "status_code": response.status_code,
            "duration": duration
        })
        
        # Add request ID to response headers
        response.headers["X-Request-ID"] = request_id
        
        return response

# Configuration management
class Config:
    """Application configuration"""
    
    def __init__(self):
        self.hetzner_api_token = os.getenv("HETZNER_API_TOKEN")
        self.internal_api_key = os.getenv("INTERNAL_API_KEY")
        self.database_url = os.getenv("DATABASE_URL")
        self.redis_url = os.getenv("REDIS_URL")
        self.log_level = os.getenv("LOG_LEVEL", "INFO")
        self.environment = os.getenv("ENVIRONMENT", "development")
        self.debug = os.getenv("DEBUG", "false").lower() == "true"
        
        # Validate required settings
        self.validate()
    
    def validate(self):
        """Validate configuration"""
        required_settings = [
            ("HETZNER_API_TOKEN", self.hetzner_api_token),
            ("INTERNAL_API_KEY", self.internal_api_key),
            ("DATABASE_URL", self.database_url)
        ]
        
        missing_settings = [
            name for name, value in required_settings if not value
        ]
        
        if missing_settings:
            raise ValueError(f"Missing required environment variables: {', '.join(missing_settings)}")
    
    def is_production(self) -> bool:
        """Check if running in production"""
        return self.environment.lower() == "production"

# Global config instance
config = Config()

# Graceful shutdown handler
import signal
import sys

class GracefulShutdown:
    """Handle graceful shutdown"""
    
    def __init__(self):
        self.shutdown = False
        signal.signal(signal.SIGINT, self._exit_gracefully)
        signal.signal(signal.SIGTERM, self._exit_gracefully)
    
    def _exit_gracefully(self, signum, frame):
        """Handle shutdown signal"""
        logger.info(f"Received shutdown signal {signum}")
        self.shutdown = True
        # Close database connections
        engine.dispose()
        # Close Redis connections
        redis_manager.redis_client.close()
        sys.exit(0)

# Global shutdown handler
shutdown_handler = GracefulShutdown()