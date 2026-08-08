<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a status column to support status-based soft deletion.
     *
     * Existing users are backfilled to "Active" so no historical data is lost
     * and all existing relationships/IDs remain intact.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')
                ->default('Active')
                ->index()
                ->after('profile_picture');
        });

        // Backfill any existing rows (new column default already handles this,
        // but we make it explicit for clarity).
        DB::table('users')->whereNull('status')->update(['status' => 'Active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
