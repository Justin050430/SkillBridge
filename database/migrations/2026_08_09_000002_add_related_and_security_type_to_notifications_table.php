<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds polymorphic-style link columns so a notification can point at the
     * relevant course, assessment, enrollment or user record (used to render
     * an actionable link in the inbox). The notification_type column is a
     * plain string, so no enum alteration is required here.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('related_type')->nullable()->after('notification_type');
            $table->unsignedBigInteger('related_id')->nullable()->after('related_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['related_type', 'related_id']);
        });
    }
};
