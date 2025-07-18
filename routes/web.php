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
