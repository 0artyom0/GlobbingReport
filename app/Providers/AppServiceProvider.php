<?php

namespace App\Providers;

use App\Models\SmartProcess;
use App\Observers\SmartProcessObserver;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
//        Queue::after(function (JobProcessed $event) {
//
//            // Check if the queue is empty
//            $queueSize = DB::table('jobs')->count();
//
//            if ($queueSize === 0) {
////                dd(1111);
////                return 'queue ended';
////                // Queue is empty, trigger notification or log it
//////                Log::info('Queue has finished processing all jobs.');
////                $this->notifyUser();
//            }
//        });

        SmartProcess::observe(SmartProcessObserver::class);
    }
}
