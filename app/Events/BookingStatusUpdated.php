<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BookingStatusUpdated
{
    use Dispatchable;

    public function __construct(public readonly mixed $booking) {}
}
