<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{ Mail, Cache };


class sendQueuedMails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queuedMails:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send queued mails';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    { 
      if($queued_mails = Cache::get('queued_mails'))
      {        
        $settings = (object)array_map(function($val)
                    {
                      return json_decode($val, true);
                    },\App\Models\Setting::first()->getAttributes() ?? []);

        $mail = collect($settings->mailer['mail'] ?? []);

        config([
            'mail.mailers.smtp'   => array_merge(config('mail.mailers.smtp'), $mail->except('from')->toArray()),
            'mail.from'           => $mail->only('from')->values()->first() ?? [],
            'mail.reply_to'       => $mail->only('reply_to')->values()->first(),
            'mail.forward_to'     => $mail->only('forward_to')->values()->first(),
        ]);
        
        if(is_array($queued_mails) && !empty($queued_mails))
        {
            while($queued_mail = array_shift($queued_mails))
            {
                extract($queued_mail);

                Mail::$action($view, $data, function($message) use($to, $subject, $reply_to)
                      {
                        $message = $message->bcc(is_array($to) ? array_filter($to) : trim($to));

                        if($reply_to)
                        {
                            $message->replyTo($reply_to);
                        }

                        $message->subject($subject);
                      });
            }

            Cache::put('queued_mails', $queued_mails);
        }
      }
    }
}
