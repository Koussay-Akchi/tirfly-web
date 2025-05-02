<?php 
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $email = (new Email())
            ->from('azizlouati338@gmail.com')
            ->to($to)
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
    }
    public function envoyer(string $to, string $subject, string $content): void
    {
        $email = (new Email())
            ->from('azizlouati338@gmail.com') // adapte l'adresse
            ->to($to)
            ->subject($subject)
            ->text($content);

        $this->mailer->send($email);
    }
}