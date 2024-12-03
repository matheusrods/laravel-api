<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CollaboratorProcessed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Dados que serão enviados ao e-mail.
     *
     * @var array
     */
    public $details;

    /**
     * Cria uma nova instância de mensagem.
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Constrói a mensagem.
     */
    public function build()
    {
        return $this->subject('Colaboradores Processados com Sucesso')
                    ->view('emails.collaborator_processed')
                    ->with('details', $this->details);
    }
}
