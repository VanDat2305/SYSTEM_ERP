<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendInvoiceToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $filePath;
    public $fileName;

    public function __construct($order, $filePath, $fileName)
    {
        $this->order = $order;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function build()
    {
        return $this->subject('Hóa đơn điện tử đơn hàng ' . $this->order->order_code)
            ->view('emails.invoice')
            ->with([
                'order' => $this->order,
            ])
            ->attach($this->filePath, [
                'as' => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
