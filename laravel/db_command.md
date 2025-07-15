# Laravel Migration & Model Commands

## Full Command List

```bash
# Update existing User table
php artisan make:migration add_cloud_platform_fields_to_users_table

# Core User & Auth Tables
php artisan make:model Organization -m
php artisan make:model OrganizationUser -m
php artisan make:model ApiKey -m

# Infrastructure Tables
php artisan make:model Datacenter -m
php artisan make:model ServerType -m
php artisan make:model Image -m

# Server Management Tables
php artisan make:model Server -m
php artisan make:model SshKey -m
php artisan make:model ServerSshKey -m

# Networking Tables
php artisan make:model FloatingIp -m
php artisan make:model Network -m
php artisan make:model ServerNetwork -m
php artisan make:model Firewall -m
php artisan make:model FirewallRule -m
php artisan make:model ServerFirewall -m
php artisan make:model LoadBalancer -m

# Storage Tables
php artisan make:model Volume -m
php artisan make:model Snapshot -m
php artisan make:model Backup -m

# Billing & Usage Tables
php artisan make:model PricingPlan -m
php artisan make:model Invoice -m
php artisan make:model InvoiceItem -m
php artisan make:model PaymentMethod -m
php artisan make:model UsageRecord -m
php artisan make:model Credit -m

# Monitoring & Metrics Tables
php artisan make:model ServerMetric -m
php artisan make:model AlertRule -m
php artisan make:model AlertHistory -m
php artisan make:model UptimeMonitor -m

# Support & Operations Tables
php artisan make:model Ticket -m
php artisan make:model TicketMessage -m
php artisan make:model MaintenanceWindow -m

# Audit & Activity Tables
php artisan make:model AuditLog -m
php artisan make:model LoginActivity -m

# System Tables
php artisan make:model SystemSetting -m
php artisan make:model ResourceQuota -m
```

## Run Migrations

```bash
# Run all migrations
php artisan migrate

# Run with seed data
php artisan migrate --seed

# Fresh migration (drops all tables first)
php artisan migrate:fresh --seed
```

## Key Patterns Used

### 1. Foreign Key Constraints
- Use `constrained()` for automatic foreign key creation
- Specify `onDelete()` behavior (cascade, restrict, set null)

### 2. Indexes
- Add indexes on foreign keys
- Add indexes on frequently queried columns (status, hetzner_id, etc.)
- Use composite indexes for multi-column lookups

### 3. Soft Deletes
- Added to User and Server models
- Use `$table->softDeletes()` in migration
- Add `SoftDeletes` trait to model

### 4. JSON Columns
- Used for flexible data like labels, permissions, metadata
- Cast to array in model using `$casts`

### 5. Enum Columns
- Used for status fields with known values
- Provides database-level validation

### 6. Model Relationships
- `BelongsTo` for parent relationships
- `HasMany` for child relationships
- `BelongsToMany` for many-to-many with pivot tables
- Custom pivot data using `withPivot()`

### 7. Model Scopes
- `scopeActive()` for filtering active records
- `scopeForUser()` for user-based access control

### 8. Model Methods
- Status check methods (isRunning(), isActive())
- Permission check methods (canModify(), hasPermission())
- Business logic methods (recordUsage(), revoke())

## Additional Setup Commands

```bash
# Create database seeders
php artisan make:seeder DatacenterSeeder
php artisan make:seeder ServerTypeSeeder
php artisan make:seeder SystemSettingSeeder

# Create form requests for validation
php artisan make:request StoreServerRequest
php artisan make:request UpdateServerRequest

# Create resources for API responses
php artisan make:resource ServerResource
php artisan make:resource ServerCollection

# Create policies for authorization
php artisan make:policy ServerPolicy --model=Server

# Create observers for model events
php artisan make:observer ServerObserver --model=Server

# Create jobs for async processing
php artisan make:job SyncServerStatus
php artisan make:job ProcessServerMetrics

# Create notifications
php artisan make:notification ServerStatusChanged
php artisan make:notification InvoiceGenerated
```

## Environment Configuration

Add to `.env`:

```env
# WorkOS Configuration
WORKOS_API_KEY=your_workos_api_key
WORKOS_CLIENT_ID=your_workos_client_id

# Hetzner API Configuration
HETZNER_API_TOKEN=your_hetzner_token
HETZNER_API_URL=https://api.hetzner.cloud/v1

# FastAPI Service
FASTAPI_URL=http://fastapi:8000
FASTAPI_TOKEN=your_internal_api_token

# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
```

## Next Steps

1. Run the migrations
2. Create seeders for initial data
3. Set up model factories for testing
4. Create controllers and routes
5. Implement the FastAPI integration service
6. Set up job queues for async processing
7. Configure Laravel Sanctum for API authentication
8. Set up Laravel Telescope for debugging (optional)