-- Initialize Cloud Hosting Database
CREATE DATABASE IF NOT EXISTS cloud_hosting;
USE cloud_hosting;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workos_id VARCHAR(255) UNIQUE,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') DEFAULT 'client',
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_workos_id (workos_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Create servers table
CREATE TABLE IF NOT EXISTS servers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hetzner_id VARCHAR(255) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    server_type VARCHAR(100) NOT NULL,
    datacenter VARCHAR(100) NOT NULL,
    status ENUM('initializing', 'starting', 'running', 'stopping', 'stopped', 'rebooting', 'rebuilding', 'migrating', 'deleting', 'unknown') DEFAULT 'initializing',
    public_ip VARCHAR(45),
    private_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_hetzner_id (hetzner_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Create audit_logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(255) NOT NULL,
    resource_type VARCHAR(100),
    resource_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource_type (resource_type),
    INDEX idx_created_at (created_at)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_read_at (read_at),
    INDEX idx_created_at (created_at)
);

-- Create admin_users table for staff management
CREATE TABLE IF NOT EXISTS admin_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    department ENUM('sales', 'support', 'tech', 'management') NOT NULL,
    role ENUM('staff', 'supervisor', 'manager', 'admin') DEFAULT 'staff',
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    permissions JSON,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    password_changed_at TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_employee_id (employee_id),
    INDEX idx_department (department),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_last_login (last_login_at)
);

-- Create admin_permissions table
CREATE TABLE IF NOT EXISTS admin_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    module VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module (module),
    INDEX idx_action (action)
);

-- Create admin_role_permissions table
CREATE TABLE IF NOT EXISTS admin_role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department ENUM('sales', 'support', 'tech', 'management') NOT NULL,
    role ENUM('staff', 'supervisor', 'manager', 'admin') NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (department, role, permission_id),
    INDEX idx_department_role (department, role)
);

-- Create admin_activity_logs table
CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100),
    resource_id VARCHAR(255),
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
);

-- Insert default permissions
INSERT INTO admin_permissions (name, description, module, action) VALUES
-- User Management
('users.view', 'View user list and details', 'users', 'view'),
('users.create', 'Create new users', 'users', 'create'),
('users.edit', 'Edit user information', 'users', 'edit'),
('users.delete', 'Delete users', 'users', 'delete'),
('users.suspend', 'Suspend/unsuspend users', 'users', 'suspend'),

-- Server Management
('servers.view', 'View server list and details', 'servers', 'view'),
('servers.create', 'Create new servers', 'servers', 'create'),
('servers.edit', 'Edit server configurations', 'servers', 'edit'),
('servers.delete', 'Delete servers', 'servers', 'delete'),
('servers.control', 'Control server power (start/stop/reboot)', 'servers', 'control'),
('servers.console', 'Access server console', 'servers', 'console'),

-- Billing Management
('billing.view', 'View billing information', 'billing', 'view'),
('billing.edit', 'Edit billing settings', 'billing', 'edit'),
('billing.reports', 'Generate billing reports', 'billing', 'reports'),

-- Support Management
('support.tickets.view', 'View support tickets', 'support', 'view'),
('support.tickets.create', 'Create support tickets', 'support', 'create'),
('support.tickets.edit', 'Edit support tickets', 'support', 'edit'),
('support.tickets.close', 'Close support tickets', 'support', 'close'),

-- Sales Management
('sales.leads.view', 'View sales leads', 'sales', 'view'),
('sales.leads.create', 'Create sales leads', 'sales', 'create'),
('sales.leads.edit', 'Edit sales leads', 'sales', 'edit'),
('sales.quotes.create', 'Create quotes', 'sales', 'create_quote'),

-- System Management
('system.settings.view', 'View system settings', 'system', 'view'),
('system.settings.edit', 'Edit system settings', 'system', 'edit'),
('system.logs.view', 'View system logs', 'system', 'logs'),
('system.maintenance', 'System maintenance operations', 'system', 'maintenance'),

-- Admin Management
('admin.users.view', 'View admin users', 'admin', 'view'),
('admin.users.create', 'Create admin users', 'admin', 'create'),
('admin.users.edit', 'Edit admin users', 'admin', 'edit'),
('admin.users.delete', 'Delete admin users', 'admin', 'delete')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert default role permissions
INSERT INTO admin_role_permissions (department, role, permission_id) VALUES
-- Sales Staff
('sales', 'staff', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('sales', 'staff', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.view')),
('sales', 'staff', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.create')),
('sales', 'staff', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.edit')),
('sales', 'staff', (SELECT id FROM admin_permissions WHERE name = 'sales.quotes.create')),

-- Sales Supervisor
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.create')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.edit')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.view')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.create')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.edit')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'sales.quotes.create')),
('sales', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'billing.view')),

-- Support Staff
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.view')),
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.control')),
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.view')),
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.create')),
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.edit')),
('support', 'staff', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.close')),

-- Support Supervisor
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.edit')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.suspend')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.view')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.control')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.console')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.view')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.create')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.edit')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.close')),
('support', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'billing.view')),

-- Tech Staff
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.view')),
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.create')),
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.edit')),
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.control')),
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'servers.console')),
('tech', 'staff', (SELECT id FROM admin_permissions WHERE name = 'system.logs.view')),

-- Tech Supervisor
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'users.edit')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.view')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.create')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.edit')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.delete')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.control')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'servers.console')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'system.settings.view')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'system.logs.view')),
('tech', 'supervisor', (SELECT id FROM admin_permissions WHERE name = 'system.maintenance')),

-- Management (Full Access)
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'users.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'users.create')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'users.edit')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'users.suspend')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'servers.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'servers.create')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'servers.edit')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'servers.delete')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'servers.control')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'billing.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'billing.edit')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'billing.reports')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'support.tickets.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'sales.leads.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'system.settings.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'system.logs.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'admin.users.view')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'admin.users.create')),
('management', 'manager', (SELECT id FROM admin_permissions WHERE name = 'admin.users.edit'))
ON DUPLICATE KEY UPDATE department = VALUES(department);

-- Insert default admin users
INSERT INTO admin_users (employee_id, email, password, name, department, role, status, created_at) VALUES
('ADMIN001', 'admin@cloudhosting.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'management', 'admin', 'active', NOW()),
('SALES001', 'sales@cloudhosting.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Manager', 'sales', 'supervisor', 'active', NOW()),
('SUPPORT001', 'support@cloudhosting.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Support Manager', 'support', 'supervisor', 'active', NOW()),
('TECH001', 'tech@cloudhosting.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technical Manager', 'tech', 'supervisor', 'active', NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Insert default admin user (keep existing for backward compatibility)
INSERT INTO users (workos_id, email, name, role, status) VALUES
('admin-default', 'admin@cloudhosting.local', 'Default Admin', 'admin', 'active')
ON DUPLICATE KEY UPDATE email = email;