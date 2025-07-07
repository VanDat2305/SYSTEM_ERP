<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendContractToCustomer extends Mailable
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
        // Tự nhận mime type theo đuôi file hoặc truyền vào nếu cần
        $extension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
        $mime = $extension === 'pdf'
            ? 'application/pdf'
            : ($extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/octet-stream');

        return $this->subject('Hợp đồng điện tử đơn hàng ' . $this->order->order_code)
            ->view('emails.contract')
            ->with([
                'order' => $this->order,
            ])
            ->attach($this->filePath, [
                'as' => $this->fileName,
                'mime' => $mime,
            ]);
    }
}
