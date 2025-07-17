-- Cloud Hosting Platform - FastAPI Compatible Database Schema
-- Optimized for FastAPI backend compatibility

USE cloud_platform;

-- =====================================================
-- CORE USER & AUTHENTICATION TABLES
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255),
    workos_id VARCHAR(255) UNIQUE,
    role ENUM('admin', 'client', 'support', 'billing') DEFAULT 'client',
    status ENUM('active', 'suspended', 'pending', 'banned') DEFAULT 'pending',
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_workos_id (workos_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table for Laravel
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX sessions_user_id_index (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys for programmatic access
CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(255) UNIQUE NOT NULL,
    permissions JSON,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_key_hash (key_hash),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SERVER MANAGEMENT TABLES
-- =====================================================

-- Servers/Instances (FastAPI Compatible)
CREATE TABLE IF NOT EXISTS servers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    hetzner_id BIGINT NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    hostname VARCHAR(255),
    status ENUM('initializing', 'starting', 'running', 'stopping', 'stopped', 'rebooting', 'rebuilding', 'migrating', 'unknown') NOT NULL,
    server_type VARCHAR(50) NOT NULL, -- Store as string to match FastAPI model
    datacenter VARCHAR(50) NOT NULL,  -- Store as string to match FastAPI model
    ipv4_address VARCHAR(45),
    ipv6_address VARCHAR(255),
    labels JSON,
    locked BOOLEAN DEFAULT FALSE,
    monthly_cost DECIMAL(10,2), -- Required by FastAPI model
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_hetzner_id (hetzner_id),
    INDEX idx_status (status),
    INDEX idx_ipv4 (ipv4_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SSH Keys (FastAPI Compatible)
CREATE TABLE IF NOT EXISTS ssh_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    hetzner_id BIGINT,
    name VARCHAR(255) NOT NULL,
    public_key TEXT NOT NULL,
    fingerprint VARCHAR(255),
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volumes (Block Storage) - FastAPI Compatible
CREATE TABLE IF NOT EXISTS volumes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    server_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    size INT NOT NULL, -- Match FastAPI model (integer GB)
    location VARCHAR(50) NOT NULL, -- Store as string
    status ENUM('creating', 'available', 'attached', 'detaching', 'deleting', 'error') DEFAULT 'creating',
    linux_device VARCHAR(255),
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_server_id (server_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Floating IPs - FastAPI Compatible
CREATE TABLE IF NOT EXISTS floating_ips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    server_id BIGINT UNSIGNED,
    hetzner_id BIGINT NOT NULL,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    type ENUM('ipv4', 'ipv6') DEFAULT 'ipv4',
    location VARCHAR(50) NOT NULL,
    blocked BOOLEAN DEFAULT FALSE,
    dns_ptr JSON,
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_ip (ip_address),
    INDEX idx_server (server_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Networks - FastAPI Compatible
CREATE TABLE IF NOT EXISTS networks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    ip_range VARCHAR(50) NOT NULL,
    location VARCHAR(50) NOT NULL,
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_hetzner_id (hetzner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Firewalls - FastAPI Compatible
CREATE TABLE IF NOT EXISTS firewalls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    rules JSON, -- Store rules as JSON directly
    applied_to JSON, -- Resources this firewall applies to
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_hetzner_id (hetzner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backups - FastAPI Compatible
CREATE TABLE IF NOT EXISTS backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    hetzner_id BIGINT NOT NULL,
    name VARCHAR(255),
    description TEXT,
    size DECIMAL(10,2), -- Match FastAPI model
    status ENUM('creating', 'available', 'restoring', 'deleting', 'error') DEFAULT 'creating',
    labels JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    INDEX idx_server_id (server_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MONITORING & OPERATIONS TABLES
-- =====================================================

-- Server Metrics (Time Series Data)
CREATE TABLE IF NOT EXISTS server_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    metric_type VARCHAR(50) NOT NULL, -- 'cpu', 'memory', 'disk', 'network_in', 'network_out'
    value DECIMAL(10,4) NOT NULL,
    unit VARCHAR(20),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    INDEX idx_server_metric_time (server_id, metric_type, recorded_at),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Server Actions/Operations Tracking
CREATE TABLE IF NOT EXISTS server_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    hetzner_action_id BIGINT,
    action_type VARCHAR(50) NOT NULL, -- 'start', 'stop', 'reboot', 'reset', etc.
    status ENUM('running', 'success', 'error') DEFAULT 'running',
    progress INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    error_message TEXT,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    INDEX idx_server_id (server_id),
    INDEX idx_status (status),
    INDEX idx_action_type (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT & SECURITY TABLES
-- =====================================================

-- Comprehensive Audit Log
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(255) NOT NULL,
    resource_type VARCHAR(50),
    resource_id BIGINT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resource Quotas
CREATE TABLE IF NOT EXISTS resource_quotas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    resource_type VARCHAR(50) NOT NULL, -- 'servers', 'volumes', 'floating_ips', etc.
    quota_limit INT NOT NULL,
    current_usage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_resource (user_id, resource_type),
    INDEX idx_resource_type (resource_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BILLING TABLES (SIMPLIFIED)
-- =====================================================

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('draft', 'pending', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    currency VARCHAR(3) DEFAULT 'EUR',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    due_date DATE,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Line Items
CREATE TABLE IF NOT EXISTS invoice_line_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,4) NOT NULL,
    unit_price DECIMAL(10,6) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    resource_type VARCHAR(50),
    resource_id BIGINT,
    period_start TIMESTAMP,
    period_end TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id),
    INDEX idx_resource (resource_type, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage Records
CREATE TABLE IF NOT EXISTS usage_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id BIGINT NOT NULL,
    usage_type VARCHAR(50) NOT NULL,
    quantity DECIMAL(20,6) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    unit_price DECIMAL(10,6) NOT NULL,
    total_cost DECIMAL(10,6),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    billing_period VARCHAR(7), -- Format: YYYY-MM
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_billing_period (user_id, billing_period),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM TABLES FOR LARAVEL
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

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, status) VALUES 
('Admin User', 'admin@cloudplatform.local', '$2y$12$YPVhLxLyD.K5K1xwBAKUMO4hPrbL7LlHqO8nH0nbP0j1K9qhI3zZS', 'admin', 'active');

-- Insert default resource quotas for admin user
INSERT INTO resource_quotas (user_id, resource_type, quota_limit) VALUES
(1, 'servers', 100),
(1, 'volumes', 200),
(1, 'floating_ips', 50),
(1, 'ssh_keys', 100);

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure to clean old metrics data
CREATE PROCEDURE clean_old_metrics()
BEGIN
    DELETE FROM server_metrics WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    DELETE FROM usage_records WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
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