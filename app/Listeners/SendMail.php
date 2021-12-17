<?php

namespace App\Listeners;

use App\Events\NewMail;
use Illuminate\Support\Facades\{ Mail, Cache };

class SendMail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }



    /**
     * Handle the event.
     *
     * @param  NewMail  $event
     * @return void
     */
    public function handle(NewMail $event)
    {
        extract($event->props);

        $reply_to = $reply_to ?? [config('mail.reply_to') ?? config('mail.mailers.smtp.username') => config('app.name')];
        $action   = $action ?? 'send';

        if(config('mail.mailers.smtp.use_queue', $event->queued) && $action === 'send')
        {
          $queued_mails = cache('queued_mails', []);

          $event->props['action'] = 'send';
          $event->props['reply_to'] = $reply_to;

          $queued_mails[] = $event->props;

          Cache::forever('queued_mails', $queued_mails);

          return;
        }

        $data['type'] = $event->props['type'] ?? null;

        $response = Mail::$action($view, $data, function($message) use($to, $subject, $reply_to)
                    {
                        $message = $message->bcc(is_array($to) ? array_filter($to) : trim($to));

                        if($reply_to)
                        {
                            $message->replyTo($reply_to);
                        }

                        $message->subject($subject);
                    });

        if($action === 'render')
          exit($response);

        return $response;
    }
}
