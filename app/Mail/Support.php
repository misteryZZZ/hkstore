<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Support extends Mailable
{
    use Queueable, SerializesModels;

    public $data = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
      $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from(config('mail.username'), config('app.name'));

        if(config('mail.reply_to'))
            $this->replyTo(config('mail.reply_to'));

        return $this->markdown('emails.default')
                    ->subject($this->data['subject'])
                    ->with(['message' => $this->data['message']]);
    }
}
