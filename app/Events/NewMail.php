<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMail
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $props;
    public $queued;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $props, bool $queued = true)
    {
        $this->props = $props;
        $this->queued = $queued;
    }
}
