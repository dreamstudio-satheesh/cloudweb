<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('restrict');
            $table->string('billing_email')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->nullable();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};