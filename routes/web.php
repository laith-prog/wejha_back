<?php

use Illuminate\Support\Facades\Route;

use App\Jobs\TestEmailJob;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email-queue', function () {
    TestEmailJob::dispatch();
    return 'Test email job dispatched!';
});
