<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejeeps', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number')->unique();
            $table->string('plate_number')->unique();
            $table->integer('passenger_capacity')->unsigned();
            $table->enum('operational_status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->text('maintenance_notes')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejeeps');
    }
};