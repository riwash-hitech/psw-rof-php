<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendFailCronMail extends Mailable
{
    use Queueable, SerializesModels;

    public $erplyTime;
    public $axTime;
    public $currentTime;
    public $delay;
    public $mailMessage;

    public function __construct($erplyTime, $axTime, $currentTime, $delay, $message)
    {
        $this->erplyTime = $erplyTime;
        $this->axTime = $axTime;
        $this->currentTime = $currentTime;
        $this->delay = $delay;
        $this->mailMessage = $message;
    }

    public function build()
    {
        return $this->subject($this->mailMessage)
            ->view('emails.sync_failed');
    }
}
