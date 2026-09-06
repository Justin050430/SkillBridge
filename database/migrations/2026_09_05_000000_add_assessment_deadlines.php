<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expired_at')->nullable();
        });

        // Deploy with the application paused: old values are whole minutes.
        DB::transaction(function () {
            DB::table('assessments')->whereNotNull('time_limit')
                ->update(['time_limit' => DB::raw('time_limit * 60')]);

            DB::table('assessment_attempts')->whereNull('submitted_at')
                ->orderBy('id')->chunkById(200, function ($attempts) {
                    $durations = DB::table('assessments')
                        ->whereIn('id', $attempts->pluck('assessment_id'))
                        ->pluck('time_limit', 'id');

                    foreach ($attempts as $attempt) {
                        $seconds = $durations[$attempt->assessment_id] ?? null;
                        if ($seconds === null) {
                            continue;
                        }

                        $deadline = Carbon::parse($attempt->started_at)->addSeconds((int) $seconds);
                        DB::table('assessment_attempts')->where('id', $attempt->id)->update([
                            'expires_at' => $deadline,
                            'expired_at' => $deadline->lessThanOrEqualTo(now()) ? $deadline : null,
                        ]);
                    }
                });
        });
    }

    public function down(): void
    {
        // The old application cannot represent seconds or consumed timeouts.
        if (DB::table('assessments')->whereRaw('time_limit % 60 <> 0')->exists()
            || DB::table('assessment_attempts')->whereNotNull('expired_at')->exists()) {
            throw new RuntimeException('Restore a pre-countdown backup to roll back second-based durations or expired attempts.');
        }

        DB::table('assessments')->whereNotNull('time_limit')
            ->update(['time_limit' => DB::raw('time_limit / 60')]);
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'expired_at']);
        });
    }
};
