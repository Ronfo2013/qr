<?php
// Abilita error reporting (disabilita in produzione)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Parametri di connessione al database
$dbHost = 'db5017005042.hosting-data.io';
$dbName = 'dbs13698425';
$dbUser = 'dbu2097736';
$dbPass = 'Camilla2020@';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

// Funzione per creare un token casuale alfanumerico
function generateToken($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $token;
}

// Se il form viene inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera e sanitizza i dati inviati
    $nome    = isset($_POST['nome']) ? htmlspecialchars(trim($_POST['nome'])) : '';
    $cognome = isset($_POST['cognome']) ? htmlspecialchars(trim($_POST['cognome'])) : '';
    $data    = isset($_POST['data']) ? htmlspecialchars(trim($_POST['data'])) : '';
    $evento  = isset($_POST['evento']) ? htmlspecialchars(trim($_POST['evento'])) : '';
    $email   = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : '';

    // Verifica che tutti i campi siano compilati correttamente
    if(empty($nome) || empty($cognome) || empty($data) || empty($evento) || empty($email)) {
        die('Tutti i campi sono obbligatori e l\'email deve essere valida.');
    }

    // Genera il token univoco
    $token = generateToken();

    // --------------------------
    // GENERAZIONE DEL QR CODE E DELL'IMMAGINE
    // --------------------------
    require_once 'phpqrcode/qrlib.php';

    // Cartella temporanea per il QR code
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    $qrFileName = $tempDir . 'qr_' . $token . '.png';

    // Genera il QR code partendo dal token
    QRcode::png($token, $qrFileName, QR_ECLEVEL_H, 4);

    // Carica l’immagine di sfondo
    $backgroundImagePath = 'sfondo.png';
    if(!file_exists($backgroundImagePath)){
        die('Immagine di sfondo non trovata.');
    }
    $background = imagecreatefrompng($backgroundImagePath);
    if(!$background){
        die('Errore nel caricamento dell\'immagine di sfondo.');
    }

    // Carica l’immagine del QR code
    $qrCodeImg = imagecreatefrompng($qrFileName);
    if(!$qrCodeImg){
        die('Errore nel caricamento del QR code.');
    }

    // Ottieni le dimensioni delle immagini
    $bgWidth  = imagesx($background);
    $bgHeight = imagesy($background);
    $qrWidth  = imagesx($qrCodeImg);
    $qrHeight = imagesy($qrCodeImg);

    // Posizionamento: in basso a destra (margine 20px)
    $padding = 20;
    $destX = $bgWidth - $qrWidth - $padding;
    $destY = $bgHeight - $qrHeight - $padding;

    // Inserisci il QR code nell'immagine di sfondo
    imagecopy($background, $qrCodeImg, $destX, $destY, 0, 0, $qrWidth, $qrHeight);

    // Aggiungi testo sull'immagine
    $textColor = imagecolorallocate($background, 0, 0, 0);
    $fontFile = __DIR__ . '/arial.ttf';
    if (!file_exists($fontFile)) {
        die('Font non trovato: ' . $fontFile);
    }
    $fontSize = 16;
    $lineHeight = 30;
    $textX = 20;
    $textY = 40;

    $lines = [
        'Nome: '    . $nome,
        'Cognome: ' . $cognome,
        'Data: '    . $data,
        'Evento: '  . $evento,
        'Token: '   . $token
    ];

    foreach ($lines as $line) {
        imagettftext($background, $fontSize, 0, $textX, $textY, $textColor, $fontFile, $line);
        $textY += $lineHeight;
    }

    // Salva l’immagine finale in formato PNG
    $outputImage = 'output_' . $token . '.png';
    if(!imagepng($background, $outputImage)){
        die('Errore nel salvataggio dell\'immagine.');
    }

    imagedestroy($background);
    imagedestroy($qrCodeImg);
    unlink($qrFileName);

    // --------------------------
    // GENERAZIONE DEL PDF CON FPDF
    // --------------------------
    require_once 'fpdf/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    // Inserimento dell'immagine nel PDF (posizionamento e dimensione in mm)
    $pdf->Image($outputImage, 10, 10, 190);
    $outputPdf = 'output_' . $token . '.pdf';
    $pdf->Output('F', $outputPdf);

    // --------------------------
    // INVIO DEL PDF VIA EMAIL (send.php)
    // --------------------------
    require_once 'send.php';
    $mailSent = sendMail($email, $outputPdf);

    // --------------------------
    // SALVATAGGIO DATI UTENTE NEL DATABASE
    // --------------------------
    $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, data_evento, evento, email, token, pdf, immagine) VALUES (:nome, :cognome, :data_evento, :evento, :email, :token, :pdf, :immagine)");
    $params = [
        ':nome'       => $nome,
        ':cognome'    => $cognome,
        ':data_evento'=> $data,
        ':evento'     => $evento,
        ':email'      => $email,
        ':token'      => $token,
        ':pdf'        => $outputPdf,
        ':immagine'   => $outputImage
    ];
    
    if(!$stmt->execute($params)) {
        die('Errore nel salvataggio dei dati.');
    }

    echo '<p>Dati salvati correttamente.</p>';
    echo $mailSent 
         ? '<p>Email inviata con successo a ' . htmlspecialchars($email) . '.</p>' 
         : '<p>Errore nell\'invio della email.</p>';
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Generazione PDF con QR Code e Salvataggio Utenti</title>
</head>
<body>
    <h1>Genera il PDF e registra l'accesso</h1>
    <form method="post" action="">
        <label for="nome">Nome:</label><br>
        <input type="text" id="nome" name="nome" required><br><br>

        <label for="cognome">Cognome:</label><br>
        <input type="text" id="cognome" name="cognome" required><br><br>

        <label for="data">Data Evento:</label><br>
        <input type="date" id="data" name="data" required><br><br>

        <label for="evento">Nome Evento:</label><br>
        <input type="text" id="evento" name="evento" required><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <input type="submit" value="Genera PDF e invia Email">
    </form>
</body>
</html>