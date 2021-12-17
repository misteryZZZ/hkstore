<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Mail extends Mailable
{
    use SerializesModels;

    public $view_props;
    public $subject;
    public $view;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->subject = $subject;
        $this->view_props = $view_props;
        $this->view = $view;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->from(config('mail.username'), config('app.name'));

        if(filter_var(config('mail.reply_to'), FILTER_VALIDATE_EMAIL))
        {
            $this->replyTo(config('mail.reply_to'), config('app.name'));
        }

        return $this->view($this->view)
                    ->subject($this->subject)
                    ->with($this->view_props);
    }
}
