<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code; // Variable para almacenar el código 2FA

    /**
     * Crear una nueva instancia del mensaje.
     *
     * @param string $code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Construir el mensaje.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Tu código de verificación')
                    ->view('emails.two_factor_code')
                    ->with([
                        'code' => $this->code,
                    ]);
    }
}
