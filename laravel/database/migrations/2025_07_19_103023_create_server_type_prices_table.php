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
        Schema::create('server_type_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_type_id')->constrained();
            $table->string('location');
            $table->decimal('price_hourly_gross', 16, 10);
            $table->decimal('price_hourly_net', 16, 10);
            $table->decimal('price_monthly_gross', 16, 10);
            $table->decimal('price_monthly_net', 16, 10);
            $table->bigInteger('included_traffic');
            $table->decimal('price_per_tb_traffic_gross', 16, 10);
            $table->decimal('price_per_tb_traffic_net', 16, 10);
            $table->timestamps();
            
            $table->unique(['server_type_id', 'location']);
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_type_prices');
    }
};
