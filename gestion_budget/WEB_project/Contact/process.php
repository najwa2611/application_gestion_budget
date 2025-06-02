<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $name     = htmlspecialchars(trim($_POST['name']));
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone    = htmlspecialchars(trim($_POST['phone']));
    $subject  = htmlspecialchars(trim($_POST['subject']));
    $message  = htmlspecialchars(trim($_POST['message']));

    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        die("Tous les champs obligatoires doivent être remplis.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Adresse email invalide.");
    }

    // PHPMailer configuration
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fadwatech111@gmail.com'; // Votre email Gmail
        $mail->Password   = 'qzxfnrhfucuuhvng';      // Votre mot de passe d'application
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Special configuration for Gmail
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Sender and recipient
        $mail->setFrom($email, $name); // Email du formulaire comme expéditeur
        $mail->addAddress('fadwatech111@gmail.com', 'Support Technique'); // Votre email comme destinataire
        
        // Important headers for Gmail
        $mail->addCustomHeader('Sender', 'fadwatech111@gmail.com');
        $mail->addCustomHeader('X-Sender', 'fadwatech111@gmail.com');
        $mail->addReplyTo($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "[Contact] $subject - $name";
        
        // Email body with professional styling
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { color: #2c3e50; border-bottom: 2px solid #f1c40f; padding-bottom: 10px; }
                .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.9em; color: #7f8c8d; }
                .message { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #f1c40f; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nouveau message de contact</h2>
                </div>
                
                <p><strong>De :</strong> $name &lt;$email&gt;</p>
                <p><strong>Téléphone :</strong> $phone</p>
                <p><strong>Sujet :</strong> $subject</p>
                
                <div class='message'>
                    <h3>Message :</h3>
                    <p>".nl2br($message)."</p>
                </div>
                
                <div class='footer'>
                    <p>Cet email a été envoyé via le formulaire de contact de votre site web.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version for email clients that don't support HTML
        $mail->AltBody = "Nouveau message de contact\n\n"
                       . "De: $name <$email>\n"
                       . "Téléphone: $phone\n"
                       . "Sujet: $subject\n\n"
                       . "Message:\n"
                       . $message;

        $mail->send();
        
        // Redirect back with success message
        header('Location: contact.html?status=success');
        exit();
        
    } catch (Exception $e) {
        // Redirect back with error message
        header('Location: contact.html?status=error&message='.urlencode($mail->ErrorInfo));
        exit();
    }
} else {
    header('Location: contact.html');
    exit();
}