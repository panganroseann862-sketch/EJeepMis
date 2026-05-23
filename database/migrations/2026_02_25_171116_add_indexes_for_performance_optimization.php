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
        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('status');
            $table->index(['role', 'status']);
        });

        // Add indexes to ejeeps table
        Schema::table('ejeeps', function (Blueprint $table) {
            $table->index('operational_status');
        });

        // Add indexes to routes table
        Schema::table('routes', function (Blueprint $table) {
            $table->index('status');
        });

        // Add indexes to schedules table
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('status');
            $table->index('day_of_week');
            $table->index(['driver_id', 'status']);
            $table->index(['route_id', 'status']);
        });

        // Add indexes to trips table
        Schema::table('trips', function (Blueprint $table) {
            $table->index('status');
            $table->index('has_route_deviation');
            $table->index(['driver_id', 'status']);
            $table->index(['status', 'scheduled_start_time']);
            $table->index(['status', 'actual_end_time']);
        });

        // Add indexes to passenger_logs table
        Schema::table('passenger_logs', function (Blueprint $table) {
            $table->index('trip_id');
            $table->index('recorded_at');
        });

        // Add indexes to notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('is_read');
            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['role', 'status']);
        });

        // Drop indexes from ejeeps table
        Schema::table('ejeeps', function (Blueprint $table) {
            $table->dropIndex(['operational_status']);
        });

        // Drop indexes from routes table
        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        // Drop indexes from schedules table
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['day_of_week']);
            $table->dropIndex(['driver_id', 'status']);
            $table->dropIndex(['route_id', 'status']);
        });

        // Drop indexes from trips table
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['has_route_deviation']);
            $table->dropIndex(['driver_id', 'status']);
            $table->dropIndex(['status', 'scheduled_start_time']);
            $table->dropIndex(['status', 'actual_end_time']);
        });

        // Drop indexes from passenger_logs table
        Schema::table('passenger_logs', function (Blueprint $table) {
            $table->dropIndex(['trip_id']);
            $table->dropIndex(['recorded_at']);
        });

        // Drop indexes from notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['is_read']);
            $table->dropIndex(['user_id', 'is_read']);
        });
    }
};
