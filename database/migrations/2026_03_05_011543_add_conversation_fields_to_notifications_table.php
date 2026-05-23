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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('conversation_id')->nullable()->after('parent_id');
            $table->foreignId('sender_id')->nullable()->after('user_id')->constrained('users')->onDelete('cascade');
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
            $table->dropForeign(['sender_id']);
            $table->dropColumn(['conversation_id', 'sender_id']);
        });
    }
};
