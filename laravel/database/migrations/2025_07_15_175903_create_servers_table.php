<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->bigInteger('hetzner_id');
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->enum('status', [
                'provisioning', 'running', 'stopped', 'paused', 
                'rebuilding', 'migrating', 'deleting', 'deleted', 'error'
            ]);
            $table->foreignId('server_type_id')->constrained();
            $table->foreignId('datacenter_id')->constrained();
            $table->foreignId('image_id')->nullable()->constrained();
            $table->string('ipv4_address', 45)->nullable();
            $table->string('ipv6_address')->nullable();
            $table->string('ipv6_network')->nullable();
            $table->string('root_password_hash')->nullable();
            $table->text('user_data')->nullable();
            $table->json('labels')->nullable();
            $table->boolean('rescue_enabled')->default(false);
            $table->boolean('locked')->default(false);
            $table->boolean('backup_enabled')->default(false);
            $table->string('iso_mounted')->nullable();
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable();
            $table->decimal('bandwidth_used_gb', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('organization_id');
            $table->index('hetzner_id');
            $table->index('status');
            $table->index('datacenter_id');
            $table->index('ipv4_address');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};