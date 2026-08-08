<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Course;

foreach (Course::with('mentor')->get() as $course) {
    $mentor = $course->mentor ? $course->mentor->first_name.' '.$course->mentor->last_name.' ('.$course->mentor->id.')' : 'NONE';
    echo $course->id.' | '.$course->title.' | mentor: '.$mentor.PHP_EOL;
}
