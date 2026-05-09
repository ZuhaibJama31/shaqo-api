<?php

namespace app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BookingCreated
{
    use Dispatchable;

    public function __construct(public readonly mixed $booking) {}
}
