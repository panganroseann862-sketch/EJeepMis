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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('ejeep_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['scheduled', 'in_progress', 'paused', 'completed', 'cancelled'])->default('scheduled');
            $table->dateTime('scheduled_start_time');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->integer('current_passenger_count')->default(0);
            $table->integer('max_passenger_count')->default(0);
            $table->boolean('has_route_deviation')->default(false);
            $table->text('deviation_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
