<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    public $name;
    public $order;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $name, $order)
    {
        $this->data = $data;
        $this->name = $name;
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.OrderCreatedMail')
            ->with([
                'data' => $this->data,
                'name' => $this->name,
                'order' => $this->order,
            ]);
    }
}
