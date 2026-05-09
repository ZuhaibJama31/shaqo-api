<?php

namespace App\Providers;

use App\Events\BookingCreated;
use App\Events\BookingStatusUpdated;
use App\Listeners\NotifyAdminOnBookingCreated;
use App\Listeners\NotifyAdminOnClientRegistered;
use App\Listeners\NotifyClientOnBookingStatusUpdated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Registered::class, NotifyAdminOnClientRegistered::class);
        Event::listen(BookingCreated::class, NotifyAdminOnBookingCreated::class);
        Event::listen(BookingStatusUpdated::class, NotifyClientOnBookingStatusUpdated::class);
    }
}