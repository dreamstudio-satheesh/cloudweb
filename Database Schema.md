

# Cloud Hosting Platform - Database Schema Overview

```
-- Cloud Hosting Platform - Comprehensive Database Schema
-- Designed for a full-featured cloud hosting provider

USE cloud_platform;

-- =====================================================
-- CORE USER & AUTHENTICATION TABLES
-- =====================================================

-- Users table (managed by Laravel/WorkOS)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255),
    workos_id VARCHAR(255) UNIQUE,
    role ENUM('admin', 'client', 'support', 'billing') DEFAULT 'client',
    status ENUM('active', 'suspended', 'pending', 'banned') DEFAULT 'pending',
    two_factor_secret TEXT,
    two_factor_recovery_codes TEXT,
    company_name VARCHAR(255),
    tax_id VARCHAR(50),
    phone VARCHAR(20),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(2),
    timezone VARCHAR(50) DEFAULT 'UTC',
    locale VARCHAR(10) DEFAULT 'en',
    remember_token VARCHAR(100),
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_workos_id (workos_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organizations for team management
CREATE TABLE IF NOT EXISTS organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    billing_email VARCHAR(255),
    tax_id VARCHAR(50),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(2),
    status ENUM('active', 'suspended', 'trial') DEFAULT 'active',
    trial_ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organization members
CREATE TABLE IF NOT EXISTS organization_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'member', 'billing') DEFAULT 'member',
    permissions JSON,
    invited_by BIGINT UNSIGNED,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_org_user (organization_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys for programmatic access
CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(255) UNIQUE NOT NULL,
    last_four VARCHAR(4),
    permissions JSON,
    rate_limit INT DEFAULT 1000,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    last_used_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_key_hash (key_hash),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INFRASTRUCTURE TABLES
-- =====================================================

-- Datacenters/Regions
CREATE TABLE IF NOT EXISTS datacenters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    country VARCHAR(2),
    continent VARCHAR(20),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('active', 'maintenance', 'deprecated') DEFAULT 'active',
    capabilities JSON,
    network_zones JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server Types/Plans
CREATE TABLE IF NOT EXISTS server_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    category ENUM('standard', 'dedicated', 'gpu', 'storage', 'memory') DEFAULT 'standard',
    cpu_type VARCHAR(100),
    cpu_cores INT NOT NULL,
    memory_gb DECIMAL(10,2) NOT NULL,
    disk_gb INT NOT NULL,
    disk_type ENUM('ssd', 'nvme', 'hdd') DEFAULT 'ssd',
    network_speed_gbps DECIMAL(5,2) DEFAULT 1,
    gpu_model VARCHAR(100),
    gpu_count INT DEFAULT 0,
    price_hourly DECIMAL(10,6) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    supported_datacenters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_category (category),
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Operating System Images
CREATE TABLE IF NOT EXISTS images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hetzner_id BIGINT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('system', 'snapshot', 'backup', 'custom') DEFAULT 'system',
    os_flavor VARCHAR(50),
    os_version VARCHAR(50),
    architecture ENUM('x86', 'arm') DEFAULT 'x86',
    size_gb DECIMAL(10,2),
    min_disk_size INT DEFAULT 10,
    status ENUM('available', 'creating', 'deprecated') DEFAULT 'available',
    public BOOLEAN DEFAULT FALSE,
    user_id BIGINT UNSIGNED,
    rapid_deploy BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deprecated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SERVER MANAGEMENT TABLES
-- =====================================================

-- Servers/Instances
CREATE TABLE IF NOT EXISTS servers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    hostname VARCHAR(255),
    status ENUM('provisioning', 'running', 'stopped', 'paused', 'rebuilding', 'migrating', 'deleting', 'deleted', 'error') NOT NULL,
    server_type_id BIGINT UNSIGNED NOT NULL,
    datacenter_id BIGINT UNSIGNED NOT NULL,
    image_id BIGINT UNSIGNED,
    ipv4_address VARCHAR(45),
    ipv6_address VARCHAR(255),
    ipv6_network VARCHAR(255),
    root_password_hash VARCHAR(255),
    user_data TEXT,
    labels JSON,
    rescue_enabled BOOLEAN DEFAULT FALSE,
    locked BOOLEAN DEFAULT FALSE,
    backup_enabled BOOLEAN DEFAULT FALSE,
    iso_mounted VARCHAR(255),
    cpu_usage DECIMAL(5,2),
    memory_usage DECIMAL(5,2),
    disk_usage DECIMAL(5,2),
    bandwidth_used_gb DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (server_type_id) REFERENCES server_types(id),
    FOREIGN KEY (datacenter_id) REFERENCES datacenters(id),
    FOREIGN KEY (image_id) REFERENCES images(id),
    INDEX idx_user_id (user_id),
    INDEX idx_org_id (organization_id),
    INDEX idx_hetzner_id (hetzner_id),
    INDEX idx_status (status),
    INDEX idx_datacenter (datacenter_id),
    INDEX idx_ipv4 (ipv4_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SSH Keys
CREATE TABLE IF NOT EXISTS ssh_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT,
    name VARCHAR(255) NOT NULL,
    public_key TEXT NOT NULL,
    fingerprint VARCHAR(255),
    type VARCHAR(20) DEFAULT 'ssh-rsa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server SSH Key associations
CREATE TABLE IF NOT EXISTS server_ssh_keys (
    server_id BIGINT UNSIGNED NOT NULL,
    ssh_key_id BIGINT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (server_id, ssh_key_id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (ssh_key_id) REFERENCES ssh_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NETWORKING TABLES
-- =====================================================

-- Floating IPs
CREATE TABLE IF NOT EXISTS floating_ips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    ptr_record VARCHAR(255),
    datacenter_id BIGINT UNSIGNED NOT NULL,
    server_id BIGINT UNSIGNED,
    type ENUM('ipv4', 'ipv6') DEFAULT 'ipv4',
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (datacenter_id) REFERENCES datacenters(id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_ip (ip_address),
    INDEX idx_server (server_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Private Networks
CREATE TABLE IF NOT EXISTS networks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    ip_range VARCHAR(50) NOT NULL,
    subnets JSON,
    routes JSON,
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_hetzner_id (hetzner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server Network associations
CREATE TABLE IF NOT EXISTS server_networks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    network_id BIGINT UNSIGNED NOT NULL,
    private_ip VARCHAR(45),
    alias_ips JSON,
    mac_address VARCHAR(17),
    attached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (network_id) REFERENCES networks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_server_network (server_id, network_id),
    INDEX idx_network (network_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Firewalls
CREATE TABLE IF NOT EXISTS firewalls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_hetzner_id (hetzner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Firewall Rules
CREATE TABLE IF NOT EXISTS firewall_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firewall_id BIGINT UNSIGNED NOT NULL,
    direction ENUM('in', 'out') NOT NULL,
    protocol ENUM('tcp', 'udp', 'icmp', 'esp', 'gre') NOT NULL,
    source_ips JSON,
    destination_ips JSON,
    port VARCHAR(20),
    description TEXT,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (firewall_id) REFERENCES firewalls(id) ON DELETE CASCADE,
    INDEX idx_firewall (firewall_id),
    INDEX idx_direction (direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server Firewall associations
CREATE TABLE IF NOT EXISTS server_firewalls (
    server_id BIGINT UNSIGNED NOT NULL,
    firewall_id BIGINT UNSIGNED NOT NULL,
    attached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (server_id, firewall_id),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (firewall_id) REFERENCES firewalls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Load Balancers
CREATE TABLE IF NOT EXISTS load_balancers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    datacenter_id BIGINT UNSIGNED NOT NULL,
    public_ip VARCHAR(45),
    algorithm ENUM('round_robin', 'least_connections') DEFAULT 'round_robin',
    status ENUM('running', 'provisioning', 'error') DEFAULT 'provisioning',
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (datacenter_id) REFERENCES datacenters(id),
    INDEX idx_user (user_id),
    INDEX idx_hetzner_id (hetzner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STORAGE TABLES
-- =====================================================

-- Volumes (Block Storage)
CREATE TABLE IF NOT EXISTS volumes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    server_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    size_gb INT NOT NULL,
    datacenter_id BIGINT UNSIGNED NOT NULL,
    status ENUM('creating', 'available', 'attached', 'detaching', 'deleting', 'error') DEFAULT 'creating',
    format VARCHAR(20),
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    FOREIGN KEY (datacenter_id) REFERENCES datacenters(id),
    INDEX idx_user_id (user_id),
    INDEX idx_server_id (server_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshots
CREATE TABLE IF NOT EXISTS snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    resource_type ENUM('server', 'volume') NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255),
    description TEXT,
    size_gb DECIMAL(10,2),
    status ENUM('creating', 'available', 'deleting', 'error') DEFAULT 'creating',
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backups
CREATE TABLE IF NOT EXISTS backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    hetzner_id BIGINT NOT NULL,
    type ENUM('automated', 'manual') DEFAULT 'manual',
    description TEXT,
    size_gb DECIMAL(10,2),
    status ENUM('creating', 'available', 'restoring', 'deleting', 'error') DEFAULT 'creating',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    INDEX idx_server_id (server_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BILLING & USAGE TABLES
-- =====================================================

-- Pricing Plans
CREATE TABLE IF NOT EXISTS pricing_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    resource_type ENUM('server', 'volume', 'floating_ip', 'load_balancer', 'backup', 'bandwidth') NOT NULL,
    price_hourly DECIMAL(10,6),
    price_monthly DECIMAL(10,2),
    price_per_gb DECIMAL(10,6),
    included_traffic_tb INT DEFAULT 0,
    overage_price_per_tb DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_resource_type (resource_type),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('draft', 'pending', 'paid', 'overdue', 'cancelled', 'refunded') DEFAULT 'draft',
    currency VARCHAR(3) DEFAULT 'USD',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    credits_applied DECIMAL(10,2) DEFAULT 0,
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    due_date DATE,
    paid_at TIMESTAMP NULL,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Line Items
CREATE TABLE IF NOT EXISTS invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id BIGINT,
    description TEXT NOT NULL,
    quantity DECIMAL(10,4) NOT NULL,
    unit_price DECIMAL(10,6) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    usage_period_start TIMESTAMP,
    usage_period_end TIMESTAMP,
    metadata JSON,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id),
    INDEX idx_resource (resource_type, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Methods
CREATE TABLE IF NOT EXISTS payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    type ENUM('credit_card', 'paypal', 'bank_transfer', 'crypto') NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    last_four VARCHAR(4),
    card_brand VARCHAR(20),
    exp_month TINYINT,
    exp_year SMALLINT,
    billing_name VARCHAR(255),
    billing_address JSON,
    stripe_payment_method_id VARCHAR(255),
    paypal_email VARCHAR(255),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage Records
CREATE TABLE IF NOT EXISTS usage_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    resource_type VARCHAR(50) NOT NULL,
    resource_id BIGINT NOT NULL,
    metric_type ENUM('compute_hours', 'storage_gb_hours', 'bandwidth_gb', 'requests') NOT NULL,
    quantity DECIMAL(20,6) NOT NULL,
    unit_price DECIMAL(10,6),
    total_cost DECIMAL(10,6),
    recorded_at TIMESTAMP NOT NULL,
    billed BOOLEAN DEFAULT FALSE,
    invoice_id BIGINT UNSIGNED,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    INDEX idx_user_resource_time (user_id, resource_type, recorded_at),
    INDEX idx_billed (billed),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Credits & Promotions
CREATE TABLE IF NOT EXISTS credits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    balance DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    description VARCHAR(255),
    type ENUM('promotional', 'referral', 'compensation', 'manual') DEFAULT 'manual',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    INDEX idx_balance (balance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MONITORING & METRICS TABLES
-- =====================================================

-- Server Metrics (Time Series Data)
CREATE TABLE IF NOT EXISTS server_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    cpu_usage DECIMAL(5,2),
    cpu_load_1min DECIMAL(6,2),
    cpu_load_5min DECIMAL(6,2),
    cpu_load_15min DECIMAL(6,2),
    memory_used_mb INT,
    memory_available_mb INT,
    memory_usage_percent DECIMAL(5,2),
    disk_read_iops INT,
    disk_write_iops INT,
    disk_read_mb DECIMAL(10,2),
    disk_write_mb DECIMAL(10,2),
    network_in_mbps DECIMAL(10,2),
    network_out_mbps DECIMAL(10,2),
    network_packets_in INT,
    network_packets_out INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    INDEX idx_server_time (server_id, recorded_at),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alert Rules
CREATE TABLE IF NOT EXISTS alert_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    resource_type ENUM('server', 'volume', 'load_balancer') NOT NULL,
    resource_id BIGINT,
    metric VARCHAR(50) NOT NULL,
    operator ENUM('gt', 'gte', 'lt', 'lte', 'eq') NOT NULL,
    threshold DECIMAL(10,2) NOT NULL,
    duration_minutes INT DEFAULT 5,
    enabled BOOLEAN DEFAULT TRUE,
    notification_channels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alert History
CREATE TABLE IF NOT EXISTS alert_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_rule_id BIGINT UNSIGNED NOT NULL,
    status ENUM('triggered', 'resolved', 'acknowledged') NOT NULL,
    value DECIMAL(10,2),
    message TEXT,
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by BIGINT UNSIGNED,
    FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rule (alert_rule_id),
    INDEX idx_status (status),
    INDEX idx_triggered (triggered_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uptime Monitors
CREATE TABLE IF NOT EXISTS uptime_monitors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    type ENUM('http', 'https', 'tcp', 'ping') NOT NULL,
    target VARCHAR(255) NOT NULL,
    port INT,
    interval_seconds INT DEFAULT 60,
    timeout_seconds INT DEFAULT 30,
    regions JSON,
    enabled BOOLEAN DEFAULT TRUE,
    notification_channels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_enabled (enabled),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUPPORT & OPERATIONS TABLES
-- =====================================================

-- Support Tickets
CREATE TABLE IF NOT EXISTS tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    category ENUM('technical', 'billing', 'abuse', 'sales', 'other') DEFAULT 'technical',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('open', 'pending', 'resolved', 'closed') DEFAULT 'open',
    assigned_to BIGINT UNSIGNED,
    related_resource_type VARCHAR(50),
    related_resource_id BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    satisfaction_rating TINYINT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Messages
CREATE TABLE IF NOT EXISTS ticket_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    attachments JSON,
    is_internal_note BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance Windows
CREATE TABLE IF NOT EXISTS maintenance_windows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('planned', 'emergency') DEFAULT 'planned',
    affected_services JSON,
    affected_datacenters JSON,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT & ACTIVITY TABLES
-- =====================================================

-- Comprehensive Audit Log
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    organization_id BIGINT UNSIGNED,
    action VARCHAR(255) NOT NULL,
    resource_type VARCHAR(50),
    resource_id BIGINT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    api_key_id BIGINT UNSIGNED,
    request_id VARCHAR(36),
    response_code INT,
    duration_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_created_at (created_at),
    INDEX idx_request_id (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Activity
CREATE TABLE IF NOT EXISTS login_activity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    location_country VARCHAR(2),
    location_city VARCHAR(100),
    provider VARCHAR(50),
    success BOOLEAN DEFAULT TRUE,
    failure_reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- Laravel Queue Jobs
CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_queue_reserved (queue, reserved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed Jobs
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_notifiable (notifiable_type, notifiable_id),
    INDEX idx_read (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings
CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT,
    `type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resource Quotas
CREATE TABLE IF NOT EXISTS resource_quotas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    organization_id BIGINT UNSIGNED,
    resource_type VARCHAR(50) NOT NULL,
    quota_limit INT NOT NULL,
    current_usage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_resource (user_id, resource_type),
    UNIQUE KEY unique_org_resource (organization_id, resource_type),
    INDEX idx_resource_type (resource_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, status) VALUES 
('Admin User', 'admin@cloudplatform.local', '$2y$12$YPVhLxLyD.K5K1xwBAKUMO4hPrbL7LlHqO8nH0nbP0j1K9qhI3zZS', 'admin', 'active');

-- Insert sample datacenters
INSERT INTO datacenters (code, name, city, country, continent, latitude, longitude, status) VALUES
('fsn1', 'Falkenstein DC Park 1', 'Falkenstein', 'DE', 'Europe', 50.47612, 12.37071, 'active'),
('nbg1', 'Nuremberg DC Park 1', 'Nuremberg', 'DE', 'Europe', 49.452102, 11.076665, 'active'),
('hel1', 'Helsinki DC Park 1', 'Helsinki', 'FI', 'Europe', 60.169855, 24.938379, 'active'),
('ash', 'Ashburn, VA', 'Ashburn', 'US', 'North America', 39.043757, -77.487442, 'active'),
('hil', 'Hillsboro, OR', 'Hillsboro', 'US', 'North America', 45.542095, -122.949825, 'active');

-- Insert default system settings
INSERT INTO system_settings (`key`, `value`, `type`, description) VALUES
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('signup_enabled', 'true', 'boolean', 'Allow new user registrations'),
('default_server_limit', '10', 'integer', 'Default server limit per user'),
('billing_cycle_day', '1', 'integer', 'Day of month for billing cycle'),
('support_email', 'support@cloudplatform.local', 'string', 'Support email address');

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure to clean old metrics data
DELIMITER //
CREATE PROCEDURE clean_old_metrics()
BEGIN
    DELETE FROM server_metrics WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    DELETE FROM usage_records WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND billed = TRUE;
    DELETE FROM login_activity WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    DELETE FROM alert_history WHERE triggered_at < DATE_SUB(NOW(), INTERVAL 60 DAY);
END//

-- Procedure to calculate monthly usage
CREATE PROCEDURE calculate_monthly_usage(IN p_user_id BIGINT, IN p_year INT, IN p_month INT)
BEGIN
    SELECT 
        resource_type,
        resource_id,
        metric_type,
        SUM(quantity) as total_quantity,
        SUM(total_cost) as total_cost
    FROM usage_records
    WHERE user_id = p_user_id
        AND YEAR(recorded_at) = p_year
        AND MONTH(recorded_at) = p_month
    GROUP BY resource_type, resource_id, metric_type;
END//

-- Procedure to check resource quotas
CREATE PROCEDURE check_resource_quota(IN p_user_id BIGINT, IN p_resource_type VARCHAR(50))
BEGIN
    DECLARE v_quota_limit INT;
    DECLARE v_current_usage INT;
    
    SELECT quota_limit, current_usage 
    INTO v_quota_limit, v_current_usage
    FROM resource_quotas
    WHERE user_id = p_user_id AND resource_type = p_resource_type;
    
    IF v_current_usage >= v_quota_limit THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Resource quota exceeded';
    END IF;
END//

DELIMITER ;
```



## 🏗️ Schema Design Philosophy

This database schema is designed for a **production-ready cloud hosting platform** similar to DigitalOcean, Linode, or Hetzner Cloud. It covers all aspects of running a cloud infrastructure provider.

## 📊 Schema Categories

### 1. **User & Authentication Management**

- **users**: Core user accounts with role-based access (admin, client, support, billing)
- **organizations**: Team/company accounts for B2B customers
- **organization_users**: Team member management with permissions
- **api_keys**: Programmatic access with rate limiting
- **login_activity**: Security tracking and anomaly detection

### 2. **Infrastructure Resources**

- **datacenters**: Geographic locations with capabilities
- **server_types**: Hardware configurations (CPU, RAM, disk)
- **images**: OS templates and custom images
- **servers**: Virtual machine instances with full lifecycle management
- **ssh_keys**: Public key management for secure access

### 3. **Networking**

- **floating_ips**: Transferable IP addresses
- **networks**: Private networking with subnets
- **firewalls**: Security group management
- **firewall_rules**: Inbound/outbound traffic control
- **load_balancers**: Traffic distribution

### 4. **Storage**

- **volumes**: Block storage devices
- **snapshots**: Point-in-time backups
- **backups**: Automated and manual backup management

### 5. **Billing & Usage**

- **pricing_plans**: Flexible pricing models
- **invoices**: Monthly billing with line items
- **invoice_items**: Detailed usage breakdown
- **payment_methods**: Multiple payment options
- **usage_records**: Granular resource consumption tracking
- **credits**: Promotional and compensation credits

### 6. **Monitoring & Alerts**

- **server_metrics**: Time-series performance data
- **alert_rules**: Customizable monitoring thresholds
- **alert_history**: Alert lifecycle tracking
- **uptime_monitors**: External availability monitoring

### 7. **Support & Operations**

- **tickets**: Customer support system
- **ticket_messages**: Threaded conversations
- **maintenance_windows**: Planned downtime management

### 8. **Audit & Compliance**

- **audit_logs**: Comprehensive activity tracking
- **login_activity**: Authentication audit trail
- **resource_quotas**: Usage limits and controls

## 🔑 Key Features

### Multi-tenancy

- Organization-based isolation
- User and organization-level resource ownership
- Flexible permission system

### Scalability

- Optimized indexes for common queries
- Partitioning-ready for metrics tables
- Efficient foreign key relationships

### Security

- Password hashing for sensitive data
- API key hashing with last-four display
- IP tracking for audit trails
- Two-factor authentication support

### Billing Flexibility

- Hourly and monthly pricing
- Usage-based billing
- Credit system for promotions
- Multiple payment methods

### Compliance Ready

- Complete audit logging
- Data retention policies via stored procedures
- Soft deletes for critical resources
- GDPR-compliant user data handling

## 📈 Performance Optimizations

1. **Indexed Columns**:
    - All foreign keys
    - Frequently queried fields (status, email, IP addresses)
    - Time-based queries (created_at, recorded_at)
2. **Stored Procedures**:
    - `clean_old_metrics()`: Automated data retention
    - `calculate_monthly_usage()`: Billing calculations
    - `check_resource_quota()`: Real-time limit enforcement
3. **JSON Fields**:
    - Flexible metadata storage
    - Labels for resource tagging
    - Configuration without schema changes

## 🚀 Extensibility

The schema is designed to be extended with:

- Additional resource types
- New monitoring metrics
- Custom billing models
- Third-party integrations
- Regional compliance requirements

## 💡 Usage Examples

### Creating a New Server

sql

```sql
-- 1. Check user quota
CALL check_resource_quota(user_id, 'servers');

-- 2. Create server record
INSERT INTO servers (user_id, hetzner_id, name, status, server_type_id, datacenter_id, ipv4_address)
VALUES (1, 12345, 'web-server-1', 'provisioning', 1, 1, '192.168.1.100');

-- 3. Log the action
INSERT INTO audit_logs (user_id, action, resource_type, resource_id, ip_address)
VALUES (1, 'server.create', 'servers', LAST_INSERT_ID(), '10.0.0.1');

-- 4. Update quota
UPDATE resource_quotas 
SET current_usage = current_usage + 1 
WHERE user_id = 1 AND resource_type = 'servers';
```

### Monthly Billing

sql

```sql
-- Generate usage report
CALL calculate_monthly_usage(user_id, 2025, 1);

-- Create invoice with line items
INSERT INTO invoices (user_id, invoice_number, status, billing_period_start, billing_period_end)
VALUES (1, 'INV-2025-01-0001', 'pending', '2025-01-01', '2025-01-31');
```

## 🔒 Security Considerations

1. **Sensitive Data**: Passwords and API keys are hashed
2. **PII Protection**: User data can be anonymized for GDPR
3. **Access Control**: Role-based permissions at application level
4. **Audit Trail**: Every action is logged with context
5. **Rate Limiting**: Built into API key system

This schema provides a solid foundation for building a competitive cloud hosting platform with all the features users expect from modern infrastructure providers.