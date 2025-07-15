<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SshKeyController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\VolumeController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminServerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    
    // Client routes
    Route::middleware(['role:client'])->group(function () {
        Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
        Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
        Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
        Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
        Route::post('/servers/{server}/power', [ServerController::class, 'power'])->name('servers.power');
        Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.destroy');
        
        // SSH Keys
        Route::resource('ssh-keys', SshKeyController::class);
        
        // Firewalls
        Route::resource('firewalls', FirewallController::class);
        
        // Volumes
        Route::resource('volumes', VolumeController::class);
        Route::post('/volumes/{volume}/attach', [VolumeController::class, 'attach'])->name('volumes.attach');
        Route::post('/volumes/{volume}/detach', [VolumeController::class, 'detach'])->name('volumes.detach');
        
        // Backups
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/servers/{server}/backup', [BackupController::class, 'create'])->name('backups.create');
        Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        
        // Billing
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
        
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });
    
    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        
        // User management
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('admin.users.suspend');
        Route::post('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('admin.users.activate');
        
        // Server management
        Route::get('/servers', [AdminServerController::class, 'index'])->name('admin.servers');
        Route::get('/servers/{server}', [AdminServerController::class, 'show'])->name('admin.servers.show');
        Route::post('/servers/{server}/suspend', [AdminServerController::class, 'suspend'])->name('admin.servers.suspend');
    });
    
    // Shared routes (both admin and client)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});