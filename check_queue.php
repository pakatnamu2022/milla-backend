<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUEUE STATUS ===\n";
echo "Jobs in queue: " . DB::table('jobs')->count() . "\n";
echo "Failed jobs: " . DB::table('failed_jobs')->count() . "\n\n";

if (DB::table('jobs')->count() > 0) {
    echo "=== PENDING JOBS ===\n";
    $jobs = DB::table('jobs')->get();
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "ID: {$job->id}\n";
        echo "Queue: {$job->queue}\n";
        echo "Job: {$payload['displayName']}\n";
        echo "Attempts: {$job->attempts}\n";
        echo "---\n";
    }
}

if (DB::table('failed_jobs')->count() > 0) {
    echo "\n=== LAST FAILED JOB ===\n";
    $failed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
    if ($failed) {
        echo "Exception: " . substr($failed->exception, 0, 500) . "\n";
    }
}
