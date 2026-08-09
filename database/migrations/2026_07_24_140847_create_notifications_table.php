<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

            $table->string('title');

            $table->text('message');

            // Plain string rather than an enum so new types (e.g. 'Security')
            // work consistently across MySQL and SQLite. The allowed values
            // are enforced at the application layer by the command classes.
            $table->string('notification_type', 20);

            $table->boolean('is_read')->default(false);

            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
