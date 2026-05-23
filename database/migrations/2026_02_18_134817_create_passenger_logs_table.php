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
        Schema::create('passenger_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('stop_id')->constrained()->onDelete('cascade');
            $table->integer('passenger_count');
            $table->integer('boarding_count')->default(0);
            $table->integer('alighting_count')->default(0);
            $table->boolean('is_over_capacity')->default(false);
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passenger_logs');
    }
};
