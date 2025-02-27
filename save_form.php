<?php
// Abilita error reporting (da disabilitare in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Imposta l'header per la risposta in JSON
header('Content-Type: application/json');

// Parametri di connessione al database
$dbHost = 'db5017005042.hosting-data.io';
$dbName = 'dbs13698425';
$dbUser = 'dbu2097736';
$dbPass = 'Camilla2020@';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Errore di connessione al database: " . $e->getMessage()]);
    exit;
}

// Definisci la cartella di output
$outputDir = __DIR__ . '/output';

// Crea la cartella se non esiste
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Funzione per generare un token univoco
function generateToken($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle($characters), 0, $length);
}

// Controlla se la richiesta è POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Legge i dati dal form
    $nome       = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $cognome    = htmlspecialchars(trim($_POST['cognome'] ?? ''));
    $evento     = htmlspecialchars(trim($_POST['evento'] ?? ''));
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telefono   = htmlspecialchars(trim($_POST['telefono'] ?? ''));
    $dataNascita= htmlspecialchars(trim($_POST['data-nascita'] ?? ''));
    $citta      = htmlspecialchars(trim($_POST['citta'] ?? ''));
    $consenso   = isset($_POST['consenso']) ? 1 : 0;
    $pubblicita = isset($_POST['pubblicita']) ? 1 : 0;

    // Validazione campi obbligatori
    if (!$nome || !$cognome || !$evento || !$email || !$telefono || !$dataNascita || !$citta || !$consenso) {
        echo json_encode(["success" => false, "message" => "Tutti i campi obbligatori devono essere compilati."]);
        exit;
    }

    // Generazione del token
    $token = generateToken();

    // =========================
    // GENERAZIONE DEL QR CODE
    // =========================
    require_once 'phpqrcode/qrlib.php';
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    $qrFileName = $tempDir . 'qr_' . $token . '.png';
    QRcode::png($token, $qrFileName, QR_ECLEVEL_H, 4);

    // Creazione immagine con sfondo e QR
    $backgroundImagePath = 'sfondo.png';
    if (!file_exists($backgroundImagePath)) {
        echo json_encode(["success" => false, "message" => "Immagine di sfondo non trovata."]);
        exit;
    }
    $background = imagecreatefrompng($backgroundImagePath);
    $qrCodeImg  = imagecreatefrompng($qrFileName);

    // =========================
    // SCRITTURA DEL TESTO SULL'IMMAGINE
    // =========================
    $textColor = imagecolorallocate($background, 0, 0, 0);
    $fontFile  = __DIR__ . '/montserrat.ttf';

    if (!file_exists($fontFile)) {
        echo json_encode(["success" => false, "message" => "Font non trovato."]);
        exit;
    }

    $lines = [
        'Nome: ' . $nome,
        'Cognome: ' . $cognome,
        'Data: ' . $evento,
        'Token: ' . $token
    ];

    $textX = 50;
    $textY = 40;
    foreach ($lines as $line) {
        imagettftext($background, 10, 0, $textX, $textY, $textColor, $fontFile, $line);
        $textY += 30;
    }

    // =========================
    // POSIZIONAMENTO DEL QR CODE
    // =========================
    $destX = 50;
    $destY = $textY + 10;
    imagecopy($background, $qrCodeImg, $destX, $destY, 0, 0, imagesx($qrCodeImg), imagesy($qrCodeImg));

    // Salva l'immagine risultante
    $outputImage = $outputDir . '/output_' . $token . '.png';
    imagepng($background, $outputImage);
    imagedestroy($background);
    imagedestroy($qrCodeImg);
    unlink($qrFileName);

    // =========================
    // GENERAZIONE DEL PDF
    // =========================
    require_once 'fpdf/fpdf.php';
    list($imgWidth, $imgHeight) = getimagesize($backgroundImagePath);
    $dpi = 150;
    $widthMm  = $imgWidth  * (25.4 / $dpi);
    $heightMm = $imgHeight * (25.4 / $dpi);

    $pdf = new FPDF('L', 'mm', array($widthMm, $heightMm));
    $pdf->SetMargins(0, 0, 0);
    $pdf->AddPage('L');
    $pdf->Image($outputImage, 0, 0, $widthMm, $heightMm);

    $outputPdf = $outputDir . '/output_' . $token . '.pdf';
    $pdf->Output('F', $outputPdf);

    if (!file_exists($outputPdf)) {
        echo json_encode(["success" => false, "message" => "Errore nella generazione del PDF."]);
        exit;
    }

    // =========================
    // INVIO EMAIL CON PDF
    // =========================
    require_once 'send.php';
    if (!sendMail($email, $outputPdf)) {
        echo json_encode(["success" => false, "message" => "Errore nell'invio dell'email."]);
        exit;
    }

    // =========================
    // SALVATAGGIO SU DATABASE
    // =========================
    try {
        $stmt = $pdo->prepare("
            INSERT INTO utenti (nome, cognome, evento, email, telefono, data_nascita, citta, consenso, pubblicita, token, pdf, immagine)
            VALUES (:nome, :cognome, :evento, :email, :telefono, :data_nascita, :citta, :consenso, :pubblicita, :token, :pdf, :immagine)
        ");
        $stmt->execute([
            ':nome'       => $nome,
            ':cognome'    => $cognome,
            ':evento'     => $evento,
            ':email'      => $email,
            ':telefono'   => $telefono,
            ':data_nascita'=> $dataNascita,
            ':citta'      => $citta,
            ':consenso'   => $consenso,
            ':pubblicita' => $pubblicita,
            ':token'      => $token,
            ':pdf'        => $outputPdf,
            ':immagine'   => $outputImage
        ]);

        echo json_encode(["success" => true, "message" => "Iscrizione avvenuta con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore durante l'iscrizione."]);
    }
}
?>