<?php

use App\Models\Assessment;
use App\Services\AssessmentAttemptService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Separate processes exercise real database contention, not sequential mocks.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $argv[1]]);
DB::purge('sqlite');
DB::statement('PRAGMA busy_timeout = 5000');
$assessment = Assessment::findOrFail((int) $argv[2]);
$service = app(AssessmentAttemptService::class);
echo "READY\n";
fflush(STDOUT);
$until = microtime(true) + 15;
while (! file_exists($argv[4]) && microtime(true) < $until) {
    usleep(10000);
    clearstatcache(true, $argv[4]);
}
if (! file_exists($argv[4])) {
    throw new RuntimeException('The concurrency test did not release its workers.');
}

if ($argv[5] === 'start') {
    $attempt = $service->start($assessment, (int) $argv[3]);
    echo json_encode(['attempt' => $attempt?->id]).PHP_EOL;
} else {
    $state = $service->withAttempt($assessment, (int) $argv[3], (int) $argv[6], function ($attempt) {
        if ($attempt->isActive()) {
            $attempt->update(['submitted_at' => now()]);

            return 'submitted';
        }

        return $attempt->hasExpired() ? 'expired' : 'already_submitted';
    });
    echo json_encode(['state' => $state]).PHP_EOL;
}
