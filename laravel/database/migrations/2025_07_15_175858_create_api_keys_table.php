<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('key_hash')->unique();
            $table->string('last_four', 4)->nullable();
            $table->json('permissions')->nullable();
            $table->integer('rate_limit')->default(1000);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            
            $table->index('key_hash');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};