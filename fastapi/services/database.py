import os
import logging
from contextlib import asynccontextmanager
from typing import Generator, Optional, AsyncGenerator
from sqlalchemy import create_engine, pool, event, text
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, Session
from sqlalchemy.pool import QueuePool
from sqlalchemy.engine import Engine
from sqlalchemy.exc import SQLAlchemyError, DisconnectionError
import redis
import json
from datetime import datetime, timedelta

from utils.exceptions import DatabaseException
from utils.logging_config import setup_logging

# Setup logging
logger = logging.getLogger(__name__)

class DatabaseManager:
    """
    Database connection and session management
    Handles connection pooling, health checks, and error recovery
    """
    
    def __init__(self):
        self.database_url = os.getenv("DATABASE_URL")
        if not self.database_url:
            raise DatabaseException("DATABASE_URL environment variable is required")
        
        self.engine = None
        self.SessionLocal = None
        self.Base = declarative_base()
        self._initialize_engine()
        self._setup_event_listeners()
    
    def _initialize_engine(self):
        """Initialize SQLAlchemy engine with proper configuration"""
        try:
            self.engine = create_engine(
                self.database_url,
                poolclass=QueuePool,
                pool_size=int(os.getenv("DB_POOL_SIZE", "10")),
                max_overflow=int(os.getenv("DB_MAX_OVERFLOW", "20")),
                pool_pre_ping=True,
                pool_recycle=int(os.getenv("DB_POOL_RECYCLE", "3600")),
                pool_timeout=int(os.getenv("DB_POOL_TIMEOUT", "30")),
                echo=os.getenv("SQL_DEBUG", "false").lower() == "true",
                connect_args={
                    "charset": "utf8mb4",
                    "autocommit": False,
                    "connect_timeout": 10
                }
            )
            
            self.SessionLocal = sessionmaker(
                autocommit=False,
                autoflush=False,
                bind=self.engine
            )
            
            logger.info("Database engine initialized successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize database engine: {str(e)}")
            raise DatabaseException(f"Database initialization failed: {str(e)}")
    
    def _setup_event_listeners(self):
        """Setup SQLAlchemy event listeners for monitoring"""
        
        @event.listens_for(self.engine, "connect")
        def set_sqlite_pragma(dbapi_connection, connection_record):
            """Set connection parameters on connect"""
            if "mysql" in self.database_url:
                with dbapi_connection.cursor() as cursor:
                    cursor.execute("SET SESSION sql_mode='STRICT_TRANS_TABLES'")
                    cursor.execute("SET SESSION innodb_lock_wait_timeout=10")
        
        @event.listens_for(self.engine, "checkout")
        def receive_checkout(dbapi_connection, connection_record, connection_proxy):
            """Log connection checkout"""
            logger.debug("Database connection checked out from pool")
        
        @event.listens_for(self.engine, "checkin")
        def receive_checkin(dbapi_connection, connection_record):
            """Log connection checkin"""
            logger.debug("Database connection checked in to pool")
        
        @event.listens_for(self.engine, "invalidate")
        def receive_invalidate(dbapi_connection, connection_record, exception):
            """Handle connection invalidation"""
            logger.warning(f"Database connection invalidated: {exception}")
    
    def get_session(self) -> Session:
        """Get a new database session"""
        if not self.SessionLocal:
            raise DatabaseException("Database not initialized")
        
        try:
            return self.SessionLocal()
        except Exception as e:
            logger.error(f"Failed to create database session: {str(e)}")
            raise DatabaseException(f"Session creation failed: {str(e)}")
    
    def create_tables(self):
        """Create all database tables"""
        try:
            from models.database_models import Base
            Base.metadata.create_all(bind=self.engine)
            logger.info("Database tables created successfully")
        except Exception as e:
            logger.error(f"Failed to create database tables: {str(e)}")
            raise DatabaseException(f"Table creation failed: {str(e)}")
    
    def drop_tables(self):
        """Drop all database tables (use with caution!)"""
        try:
            from models.database_models import Base
            Base.metadata.drop_all(bind=self.engine)
            logger.warning("All database tables dropped")
        except Exception as e:
            logger.error(f"Failed to drop database tables: {str(e)}")
            raise DatabaseException(f"Table drop failed: {str(e)}")
    
    def health_check(self) -> dict:
        """Comprehensive database health check"""
        health_status = {
            "status": "unhealthy",
            "timestamp": datetime.utcnow().isoformat(),
            "checks": {}
        }
        
        try:
            # Test basic connectivity
            with self.engine.connect() as conn:
                result = conn.execute(text("SELECT 1 as test"))
                test_value = result.scalar()
                health_status["checks"]["connectivity"] = test_value == 1
            
            # Test session creation
            session = self.get_session()
            session.execute(text("SELECT 1"))
            session.close()
            health_status["checks"]["session_creation"] = True
            
            # Check pool status
            pool = self.engine.pool
            health_status["checks"]["pool_info"] = {
                "size": pool.size(),
                "checked_in": pool.checkedin(),
                "checked_out": pool.checkedout(),
                "overflow": pool.overflow(),
                "invalidated": pool.invalidated()
            }
            
            # Overall status
            if all(health_status["checks"][key] for key in ["connectivity", "session_creation"]):
                health_status["status"] = "healthy"
            
        except Exception as e:
            logger.error(f"Database health check failed: {str(e)}")
            health_status["checks"]["error"] = str(e)
        
        return health_status
    
    def cleanup(self):
        """Cleanup database resources"""
        try:
            if self.engine:
                self.engine.dispose()
                logger.info("Database engine disposed")
        except Exception as e:
            logger.error(f"Error during database cleanup: {str(e)}")

class DatabaseTransaction:
    """
    Context manager for database transactions with automatic rollback
    """
    
    def __init__(self, session: Optional[Session] = None):
        self.session = session
        self.should_close = session is None
        self.transaction = None
    
    def __enter__(self) -> Session:
        if self.session is None:
            self.session = db_manager.get_session()
        
        try:
            self.transaction = self.session.begin()
            return self.session
        except Exception as e:
            if self.should_close and self.session:
                self.session.close()
            raise DatabaseException(f"Failed to begin transaction: {str(e)}")
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        try:
            if exc_type:
                self.session.rollback()
                logger.debug("Transaction rolled back due to exception")
            else:
                self.session.commit()
                logger.debug("Transaction committed successfully")
        except Exception as e:
            logger.error(f"Error in transaction cleanup: {str(e)}")
            if self.session:
                self.session.rollback()
        finally:
            if self.should_close and self.session:
                self.session.close()

@asynccontextmanager
async def get_async_session() -> AsyncGenerator[Session, None]:
    """Async context manager for database sessions"""
    session = db_manager.get_session()
    try:
        yield session
        session.commit()
    except Exception as e:
        session.rollback()
        logger.error(f"Database session error: {str(e)}")
        raise DatabaseException(f"Session error: {str(e)}")
    finally:
        session.close()

def get_db() -> Generator[Session, None, None]:
    """
    Database dependency for FastAPI
    Provides automatic session management and error handling
    """
    session = db_manager.get_session()
    try:
        yield session
    except SQLAlchemyError as e:
        session.rollback()
        logger.error(f"Database error in dependency: {str(e)}")
        raise DatabaseException(f"Database operation failed: {str(e)}")
    except Exception as e:
        session.rollback()
        logger.error(f"Unexpected error in database dependency: {str(e)}")
        raise DatabaseException(f"Unexpected database error: {str(e)}")
    finally:
        session.close()

class RedisManager:
    """
    Redis connection and caching management
    """
    
    def __init__(self):
        self.redis_url = os.getenv("REDIS_URL", "redis://localhost:6379")
        self.redis_client = None
        self.default_ttl = int(os.getenv("REDIS_DEFAULT_TTL", "3600"))
        self._initialize_redis()
    
    def _initialize_redis(self):
        """Initialize Redis connection"""
        try:
            self.redis_client = redis.from_url(
                self.redis_url,
                decode_responses=True,
                socket_connect_timeout=5,
                socket_timeout=5,
                retry_on_timeout=True,
                health_check_interval=30
            )
            
            # Test connection
            self.redis_client.ping()
            logger.info("Redis connection initialized successfully")
            
        except Exception as e:
            logger.warning(f"Failed to initialize Redis: {str(e)}")
            self.redis_client = None
    
    def get(self, key: str) -> Optional[any]:
        """Get value from Redis cache"""
        if not self.redis_client:
            return None
        
        try:
            value = self.redis_client.get(key)
            return json.loads(value) if value else None
        except Exception as e:
            logger.error(f"Redis get error for key {key}: {str(e)}")
            return None
    
    def set(self, key: str, value: any, ttl: Optional[int] = None) -> bool:
        """Set value in Redis cache"""
        if not self.redis_client:
            return False
        
        try:
            ttl = ttl or self.default_ttl
            return self.redis_client.setex(key, ttl, json.dumps(value, default=str))
        except Exception as e:
            logger.error(f"Redis set error for key {key}: {str(e)}")
            return False
    
    def delete(self, key: str) -> bool:
        """Delete key from Redis cache"""
        if not self.redis_client:
            return False
        
        try:
            return bool(self.redis_client.delete(key))
        except Exception as e:
            logger.error(f"Redis delete error for key {key}: {str(e)}")
            return False
    
    def exists(self, key: str) -> bool:
        """Check if key exists in Redis"""
        if not self.redis_client:
            return False
        
        try:
            return bool(self.redis_client.exists(key))
        except Exception as e:
            logger.error(f"Redis exists error for key {key}: {str(e)}")
            return False
    
    def health_check(self) -> dict:
        """Redis health check"""
        health_status = {
            "status": "unhealthy",
            "timestamp": datetime.utcnow().isoformat(),
            "info": {}
        }
        
        try:
            if self.redis_client:
                self.redis_client.ping()
                info = self.redis_client.info()
                health_status["status"] = "healthy"
                health_status["info"] = {
                    "connected_clients": info.get("connected_clients"),
                    "used_memory_human": info.get("used_memory_human"),
                    "redis_version": info.get("redis_version")
                }
            else:
                health_status["info"]["error"] = "Redis not initialized"
                
        except Exception as e:
            health_status["info"]["error"] = str(e)
        
        return health_status
    
    def cleanup(self):
        """Cleanup Redis resources"""
        try:
            if self.redis_client:
                self.redis_client.close()
                logger.info("Redis connection closed")
        except Exception as e:
            logger.error(f"Error during Redis cleanup: {str(e)}")

def cache_result(key_prefix: str, ttl: Optional[int] = None):
    """
    Decorator for caching function results in Redis
    """
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
            cache_ttl = ttl or redis_manager.default_ttl
            redis_manager.set(cache_key, result, cache_ttl)
            logger.debug(f"Cache set for {cache_key} with TTL {cache_ttl}")
            
            return result
        return wrapper
    return decorator

class DatabaseMetrics:
    """
    Database performance metrics collection
    """
    
    def __init__(self):
        self.query_count = 0
        self.total_query_time = 0
        self.slow_queries = []
        self.error_count = 0
    
    def record_query(self, query_time: float, query: str = None):
        """Record query execution metrics"""
        self.query_count += 1
        self.total_query_time += query_time
        
        # Track slow queries (>1 second)
        if query_time > 1.0:
            self.slow_queries.append({
                "query": query[:100] if query else "Unknown",
                "time": query_time,
                "timestamp": datetime.utcnow().isoformat()
            })
            
            # Keep only last 100 slow queries
            self.slow_queries = self.slow_queries[-100:]
    
    def record_error(self):
        """Record database error"""
        self.error_count += 1
    
    def get_metrics(self) -> dict:
        """Get database metrics summary"""
        avg_query_time = (
            self.total_query_time / self.query_count 
            if self.query_count > 0 else 0
        )
        
        return {
            "total_queries": self.query_count,
            "total_query_time": self.total_query_time,
            "average_query_time": avg_query_time,
            "slow_queries_count": len(self.slow_queries),
            "error_count": self.error_count,
            "recent_slow_queries": self.slow_queries[-5:]  # Last 5
        }
    
    def reset(self):
        """Reset metrics counters"""
        self.query_count = 0
        self.total_query_time = 0
        self.slow_queries = []
        self.error_count = 0

# Global instances
db_manager = DatabaseManager()
redis_manager = RedisManager()
db_metrics = DatabaseMetrics()

def initialize_database():
    """Initialize database and create tables"""
    try:
        db_manager.create_tables()
        logger.info("Database initialization completed")
    except Exception as e:
        logger.error(f"Database initialization failed: {str(e)}")
        raise

def check_all_services() -> dict:
    """Check health of all database services"""
    return {
        "database": db_manager.health_check(),
        "redis": redis_manager.health_check(),
        "metrics": db_metrics.get_metrics(),
        "timestamp": datetime.utcnow().isoformat()
    }

def cleanup_all_services():
    """Cleanup all database services"""
    db_manager.cleanup()
    redis_manager.cleanup()
    logger.info("All database services cleaned up")

# Database utilities
def execute_raw_sql(sql: str, params: dict = None) -> any:
    """Execute raw SQL query safely"""
    with DatabaseTransaction() as session:
        try:
            result = session.execute(text(sql), params or {})
            return result.fetchall()
        except Exception as e:
            logger.error(f"Raw SQL execution failed: {str(e)}")
            raise DatabaseException(f"SQL execution failed: {str(e)}")

def backup_database(backup_path: str = None) -> str:
    """Create database backup (MySQL dump)"""
    import subprocess
    import tempfile
    
    if not backup_path:
        backup_path = f"/tmp/db_backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}.sql"
    
    try:
        # Parse database URL for mysqldump
        from urllib.parse import urlparse
        parsed = urlparse(db_manager.database_url)
        
        cmd = [
            "mysqldump",
            f"--host={parsed.hostname}",
            f"--port={parsed.port or 3306}",
            f"--user={parsed.username}",
            f"--password={parsed.password}",
            "--single-transaction",
            "--routines",
            "--triggers",
            parsed.path.lstrip('/'),
        ]
        
        with open(backup_path, 'w') as backup_file:
            subprocess.run(cmd, stdout=backup_file, check=True)
        
        logger.info(f"Database backup created: {backup_path}")
        return backup_path
        
    except Exception as e:
        logger.error(f"Database backup failed: {str(e)}")
        raise DatabaseException(f"Backup failed: {str(e)}")

# Error recovery functions
def recover_database_connection():
    """Attempt to recover database connection"""
    try:
        db_manager.cleanup()
        db_manager._initialize_engine()
        logger.info("Database connection recovery successful")
        return True
    except Exception as e:
        logger.error(f"Database connection recovery failed: {str(e)}")
        return False