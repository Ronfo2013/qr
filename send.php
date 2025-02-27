<?php
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $pdfFile) {
    // Controllo file
    if (!file_exists($pdfFile)) {
        return [false, "File PDF non trovato."];
    }
    // Controllo email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [false, "Indirizzo email non valido."];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.ionos.it';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@opiumpordenone.com'; 
        $mail->Password   = 'Camilla2020@'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@opiumpordenone.com', 'Opium Pordenone');
        $mail->addAddress($to);

        $mail->Subject = 'Il tuo coupon Omaggio per Sabato';
        $mail->Body    = 'In allegato trovi il PDF con il tuo coupon omaggio, ti chiedo di mostrarlo all ingresso entro le 00.30';
        $mail->AltBody = 'In allegato trovi il coupon omaggio valido entro le 24.00.';
        $mail->addAttachment($pdfFile, basename($pdfFile));

        $mail->send();
        // Se non ci sono eccezioni, mail inviata
        return [true, "Mail inviata correttamente."];
    } catch (Exception $e) {
        // Ritorna false + messaggio di errore
        return [false, "Errore nell'invio della mail: " . $mail->ErrorInfo];
    }
}

// Facoltativo: se vuoi testare direttamente da riga di comando
/*
$to = "destinatario@example.com";
$pdfFile = "percorso/del/tuo_file.pdf";
list($ok, $msg) = sendMail($to, $pdfFile);
var_dump($ok, $msg);
*/