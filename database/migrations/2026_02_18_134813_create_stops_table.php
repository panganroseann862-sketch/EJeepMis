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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->string('stop_name');
            $table->text('location_description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('sequence_order');
            $table->timestamps();
            
            $table->unique(['route_id', 'sequence_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
