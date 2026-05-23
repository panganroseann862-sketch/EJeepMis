<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('location')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'responded', 'resolved'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_alerts');
    }
};