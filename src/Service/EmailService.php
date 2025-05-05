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
        // Convert newlines in content to HTML line breaks for proper formatting
        $formattedContent = nl2br(htmlspecialchars($content));

        // HTML email template
        $html = <<<EOT
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$subject</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Arial', sans-serif; background-color: #f4f7fa; color: #333;">
    <table role="presentation" style="width: 100%; max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(90deg, #28a745, #218838); padding: 20px; text-align: center; border-radius: 10px 10px 0 0;">
                <img src="https://via.placeholder.com/150x50?text=Logo" alt="tirfy agence" style="max-width: 150px; height: auto;">
                <h1 style="color: #ffffff; font-size: 24px; margin: 10px 0; font-weight: 600;">Réponse à votre réclamation</h1>
            </td>
        </tr>
        <!-- Body -->
        <tr>
            <td style="padding: 30px;">
                <h2 style="font-size: 20px; color: #28a745; margin-bottom: 15px;">Bonjour,</h2>
                <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                    $formattedContent
                </p>
                <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                    Merci de votre confiance. Si vous avez d'autres questions, n'hésitez pas à nous contacter.
                </p>
               
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px;">
                <p style="font-size: 14px; color: #6c757d; margin: 0;">
                    &copy; 2025 Votre Entreprise. Tous droits réservés.
              
        </tr>
    </table>
</body>
</html>
EOT;

        // Plain text fallback
        $plainText = strip_tags(str_replace('<br />', "\n", $formattedContent)) . "\n\nMerci de votre confiance.\nVoir les détails dans votre historique de réclamations.\n\n© 2025 Votre Entreprise.\nContact: support@votreentreprise.com";

        $email = (new Email())
            ->from('azizlouati338@gmail.com')
            ->to($to)
            ->subject($subject)
            ->text($plainText)
            ->html($html);

        $this->mailer->send($email);
    }
}