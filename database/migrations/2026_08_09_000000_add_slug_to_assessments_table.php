<?php

use App\Models\Assessment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique slug to assessments for clean user-facing URLs.
     * Existing rows are backfilled from their title before the column is
     * made unique and non-null.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        // Backfill existing records with a unique slug derived from the title.
        foreach (Assessment::query()->get() as $assessment) {
            $assessment->slug = $assessment->generateUniqueSlug($assessment->title, $assessment->id);
            $assessment->saveQuietly();
        }

        Schema::table('assessments', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropUnique(['assessments_slug_unique']);
            $table->dropColumn('slug');
        });
    }
};
