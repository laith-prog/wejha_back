<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\TestEmailJob;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email-queue', function () {
    TestEmailJob::dispatch();
    return 'Test email job dispatched!';
});

Route::get('/process-pending-jobs', function () {
    Artisan::call('jobs:process-pending');
    return Artisan::output();
});

Route::get('/test-email-direct', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('Test email sent directly', function($message) {
            $message->to(config('mail.from.address'))
                    ->subject('Direct Test Email');
        });
        return 'Direct email sent successfully!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Route::get('/queue-status', function () {
    $output = [];
    
    // Check jobs in queue
    $jobCount = \Illuminate\Support\Facades\DB::table('jobs')->count();
    $output[] = "Jobs in queue: {$jobCount}";
    
    // Check failed jobs
    $failedCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
    $output[] = "Failed jobs: {$failedCount}";
    
    // Check if worker is running
    exec('ps aux | grep "queue:work" | grep -v grep', $processOutput);
    $output[] = "Queue worker processes: " . count($processOutput);
    if (count($processOutput) > 0) {
        $output[] = "Worker processes:";
        foreach ($processOutput as $process) {
            $output[] = $process;
        }
    }
    
    return implode("<br>", $output);
});

Route::get('/start-queue-worker', function () {
    // Start the queue worker in the background
    exec('nohup php artisan queue:work --tries=3 --verbose > /dev/null 2>&1 &');
    return redirect('/queue-status');
});
