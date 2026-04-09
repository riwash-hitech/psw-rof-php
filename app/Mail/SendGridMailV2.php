<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendGridMailV2 extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;
    public $from;
    public $payload;

    /**
     * Create a new message instance.
     *
     * @param  string  $subject
     * @param  string  $body
     * @return void
     */
    public function __construct($subject, $body, $payload)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->payload = $payload;
        
    }

    public function build()
    {
        $message = $this->view('emails.pickup')->subject($this->subject);

       
        return $message;
        // dd($this->from);
        // return $this->subject($this->subject)
        //             ->view('emails.sendgrid')
        //             ->with([
        //                 'body' => $this->body,
        //             ])->from($this->from);
    
        // return $this->view('emails.sendgrid')
        // ->from("notifications@psw.com.au")
        // ->subject($this->from);
    }
    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Send Grid Mail V2',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
