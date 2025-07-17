from sqlalchemy import Column, Integer, String, Text, DateTime, Boolean, Float, ForeignKey, JSON, Index
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from datetime import datetime

Base = declarative_base()

class User(Base):
    __tablename__ = "users"
    
    id = Column(Integer, primary_key=True, index=True)
    workos_id = Column(String(255), unique=True, index=True)
    email = Column(String(255), unique=True, index=True, nullable=False)
    name = Column(String(255), nullable=False)
    role = Column(String(50), default="client", nullable=False)
    email_verified_at = Column(DateTime)
    password_hash = Column(String(255))
    remember_token = Column(String(100))
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    servers = relationship("Server", back_populates="user")
    ssh_keys = relationship("SSHKey", back_populates="user")
    api_keys = relationship("APIKey", back_populates="user")
    audit_logs = relationship("AuditLog", back_populates="user")
    resource_quotas = relationship("ResourceQuota", back_populates="user")
    invoices = relationship("Invoice", back_populates="user")
    
    def __repr__(self):
        return f"<User(id={self.id}, email={self.email})>"

class Server(Base):
    __tablename__ = "servers"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    name = Column(String(255), nullable=False)
    hostname = Column(String(255))
    status = Column(String(50), default="provisioning", index=True)
    server_type = Column(String(50), nullable=False)
    datacenter = Column(String(50), nullable=False)
    ipv4_address = Column(String(39), index=True)
    ipv6_address = Column(String(39))
    labels = Column(JSON)
    backup_enabled = Column(Boolean, default=False)
    locked = Column(Boolean, default=False)
    monthly_cost = Column(Float)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User", back_populates="servers")
    volumes = relationship("Volume", back_populates="server")
    floating_ips = relationship("FloatingIP", back_populates="server")
    metrics = relationship("ServerMetric", back_populates="server")
    backups = relationship("Backup", back_populates="server")
    actions = relationship("ServerAction", back_populates="server")
    
    def __repr__(self):
        return f"<Server(id={self.id}, name={self.name}, status={self.status})>"

class Volume(Base):
    __tablename__ = "volumes"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    server_id = Column(Integer, ForeignKey("servers.id"), index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    name = Column(String(255), nullable=False)
    size = Column(Integer, nullable=False)
    location = Column(String(50), nullable=False)
    status = Column(String(50), default="creating", index=True)
    linux_device = Column(String(255))
    labels = Column(JSON)
    monthly_cost = Column(Float)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User")
    server = relationship("Server", back_populates="volumes")
    
    def __repr__(self):
        return f"<Volume(id={self.id}, name={self.name}, size={self.size})>"

class FloatingIP(Base):
    __tablename__ = "floating_ips"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    server_id = Column(Integer, ForeignKey("servers.id"), index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    ip_address = Column(String(39), nullable=False, index=True)
    type = Column(String(10), nullable=False)
    location = Column(String(50), nullable=False)
    blocked = Column(Boolean, default=False)
    dns_ptr = Column(JSON)
    labels = Column(JSON)
    monthly_cost = Column(Float)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User")
    server = relationship("Server", back_populates="floating_ips")
    
    def __repr__(self):
        return f"<FloatingIP(id={self.id}, ip={self.ip_address})>"

class SSHKey(Base):
    __tablename__ = "ssh_keys"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    name = Column(String(255), nullable=False)
    fingerprint = Column(String(255), nullable=False)
    public_key = Column(Text, nullable=False)
    labels = Column(JSON)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User", back_populates="ssh_keys")
    
    def __repr__(self):
        return f"<SSHKey(id={self.id}, name={self.name})>"

class Network(Base):
    __tablename__ = "networks"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    name = Column(String(255), nullable=False)
    ip_range = Column(String(18), nullable=False)
    location = Column(String(50), nullable=False)
    labels = Column(JSON)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User")
    
    def __repr__(self):
        return f"<Network(id={self.id}, name={self.name})>"

class Firewall(Base):
    __tablename__ = "firewalls"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    name = Column(String(255), nullable=False)
    rules = Column(JSON)
    labels = Column(JSON)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User")
    
    def __repr__(self):
        return f"<Firewall(id={self.id}, name={self.name})>"

class Backup(Base):
    __tablename__ = "backups"
    
    id = Column(Integer, primary_key=True, index=True)
    server_id = Column(Integer, ForeignKey("servers.id"), nullable=False, index=True)
    hetzner_id = Column(Integer, unique=True, index=True)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    size = Column(Float)
    type = Column(String(50), default="backup")
    status = Column(String(50), default="creating")
    labels = Column(JSON)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    server = relationship("Server", back_populates="backups")
    
    def __repr__(self):
        return f"<Backup(id={self.id}, name={self.name})>"

class ServerMetric(Base):
    __tablename__ = "server_metrics"
    
    id = Column(Integer, primary_key=True, index=True)
    server_id = Column(Integer, ForeignKey("servers.id"), nullable=False, index=True)
    metric_type = Column(String(50), nullable=False)
    value = Column(Float, nullable=False)
    unit = Column(String(20))
    recorded_at = Column(DateTime, default=func.now(), index=True)
    
    # Relationships
    server = relationship("Server", back_populates="metrics")
    
    __table_args__ = (
        Index('idx_server_metric_time', 'server_id', 'metric_type', 'recorded_at'),
    )
    
    def __repr__(self):
        return f"<ServerMetric(server_id={self.server_id}, type={self.metric_type}, value={self.value})>"

class ServerAction(Base):
    __tablename__ = "server_actions"
    
    id = Column(Integer, primary_key=True, index=True)
    server_id = Column(Integer, ForeignKey("servers.id"), nullable=False, index=True)
    hetzner_action_id = Column(Integer, index=True)
    action_type = Column(String(50), nullable=False)
    status = Column(String(50), default="running")
    progress = Column(Integer, default=0)
    started_at = Column(DateTime, default=func.now())
    finished_at = Column(DateTime)
    error_message = Column(Text)
    
    # Relationships
    server = relationship("Server", back_populates="actions")
    
    def __repr__(self):
        return f"<ServerAction(id={self.id}, type={self.action_type}, status={self.status})>"

class APIKey(Base):
    __tablename__ = "api_keys"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    name = Column(String(255), nullable=False)
    key_hash = Column(String(255), nullable=False, unique=True)
    permissions = Column(JSON)
    last_used_at = Column(DateTime)
    expires_at = Column(DateTime)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    deleted_at = Column(DateTime)
    
    # Relationships
    user = relationship("User", back_populates="api_keys")
    
    def __repr__(self):
        return f"<APIKey(id={self.id}, name={self.name})>"

class AuditLog(Base):
    __tablename__ = "audit_logs"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), index=True)
    action = Column(String(100), nullable=False, index=True)
    resource_type = Column(String(50), index=True)
    resource_id = Column(Integer, index=True)
    old_values = Column(JSON)
    new_values = Column(JSON)
    ip_address = Column(String(45))
    user_agent = Column(String(500))
    created_at = Column(DateTime, default=func.now(), index=True)
    
    # Relationships
    user = relationship("User", back_populates="audit_logs")
    
    def __repr__(self):
        return f"<AuditLog(id={self.id}, action={self.action})>"

class ResourceQuota(Base):
    __tablename__ = "resource_quotas"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    resource_type = Column(String(50), nullable=False)
    quota_limit = Column(Integer, nullable=False)
    current_usage = Column(Integer, default=0)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    
    # Relationships
    user = relationship("User", back_populates="resource_quotas")
    
    __table_args__ = (
        Index('idx_user_resource_type', 'user_id', 'resource_type'),
    )
    
    def __repr__(self):
        return f"<ResourceQuota(user_id={self.user_id}, type={self.resource_type})>"

class Invoice(Base):
    __tablename__ = "invoices"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    invoice_number = Column(String(255), unique=True, nullable=False)
    status = Column(String(50), default="draft")
    subtotal = Column(Float, nullable=False)
    tax_amount = Column(Float, default=0)
    total_amount = Column(Float, nullable=False)
    currency = Column(String(3), default="EUR")
    billing_period_start = Column(DateTime, nullable=False)
    billing_period_end = Column(DateTime, nullable=False)
    due_date = Column(DateTime)
    paid_at = Column(DateTime)
    created_at = Column(DateTime, default=func.now())
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now())
    
    # Relationships
    user = relationship("User", back_populates="invoices")
    line_items = relationship("InvoiceLineItem", back_populates="invoice")
    
    def __repr__(self):
        return f"<Invoice(id={self.id}, number={self.invoice_number})>"

class InvoiceLineItem(Base):
    __tablename__ = "invoice_line_items"
    
    id = Column(Integer, primary_key=True, index=True)
    invoice_id = Column(Integer, ForeignKey("invoices.id"), nullable=False, index=True)
    description = Column(String(255), nullable=False)
    quantity = Column(Float, nullable=False)
    unit_price = Column(Float, nullable=False)
    amount = Column(Float, nullable=False)
    resource_type = Column(String(50))
    resource_id = Column(Integer)
    period_start = Column(DateTime)
    period_end = Column(DateTime)
    
    # Relationships
    invoice = relationship("Invoice", back_populates="line_items")
    
    def __repr__(self):
        return f"<InvoiceLineItem(id={self.id}, description={self.description})>"

class UsageRecord(Base):
    __tablename__ = "usage_records"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    resource_type = Column(String(50), nullable=False)
    resource_id = Column(Integer, nullable=False)
    usage_type = Column(String(50), nullable=False)
    quantity = Column(Float, nullable=False)
    unit = Column(String(20), nullable=False)
    unit_price = Column(Float, nullable=False)
    total_cost = Column(Float, nullable=False)
    recorded_at = Column(DateTime, default=func.now(), index=True)
    billing_period = Column(String(7), index=True)  # Format: YYYY-MM
    
    # Relationships
    user = relationship("User")
    
    __table_args__ = (
        Index('idx_usage_billing_period', 'user_id', 'billing_period'),
        Index('idx_usage_recorded_at', 'recorded_at'),
    )
    
    def __repr__(self):
        return f"<UsageRecord(id={self.id}, type={self.usage_type})>"

class Notification(Base):
    __tablename__ = "notifications"
    
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    type = Column(String(50), nullable=False)
    title = Column(String(255), nullable=False)
    message = Column(Text, nullable=False)
    data = Column(JSON)
    read_at = Column(DateTime)
    created_at = Column(DateTime, default=func.now(), index=True)
    
    # Relationships
    user = relationship("User")
    
    def __repr__(self):
        return f"<Notification(id={self.id}, type={self.type})>"