<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('TestEmailJob is running');
        
        try {
            Mail::raw('Test email from queue', function($message) {
                $message->to(config('mail.from.address'))
                        ->subject('Test Email from Queue');
            });
            
            Log::info('Test email sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send test email: ' . $e->getMessage());
        }
    }
} 