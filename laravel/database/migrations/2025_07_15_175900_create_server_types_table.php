<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_types', function (Blueprint $table) {
            $table->id();
            $table->integer('hetzner_id')->unique();
            $table->string('name')->unique();
            $table->string('description');
            $table->string('architecture')->default('x86');
            $table->integer('cores');
            $table->string('cpu_type'); // shared or dedicated
            $table->integer('memory'); // in GB
            $table->integer('disk'); // in GB
            $table->string('storage_type')->default('local');
            $table->decimal('price_hourly', 10, 4);
            $table->decimal('price_monthly', 10, 4);
            $table->bigInteger('included_traffic')->nullable();
            $table->decimal('price_per_tb_traffic', 8, 4)->nullable();
            $table->boolean('deprecated')->default(false);
            $table->timestamp('deprecation_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_types');
    }
};
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_types');
    }
};
