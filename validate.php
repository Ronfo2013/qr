<?php
// Impostazione header per una risposta in plain text (utile per AJAX)
header('Content-Type: text/plain; charset=utf-8');

// Parametri di connessione al database
$dbHost = 'db5017005042.hosting-data.io';
$dbName = 'dbs13698425';
$dbUser = 'dbu2097736';
$dbPass = 'Camilla2020@';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Se la connessione fallisce, restituisce immediatamente un messaggio di errore.
    die("Errore di connessione al database: " . $e->getMessage());
}

// Recupera il token passato via GET
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    die('Token mancante.');
}

$token = htmlspecialchars(trim($_GET['token']));

// Prepara e esegue la query per verificare se il token esiste
$stmt = $pdo->prepare("SELECT * FROM utenti WHERE token = :token");
$stmt->execute([':token' => $token]);
$utente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($utente) {
    // Se il token è presente, verifica se è già stato validato
    if ($utente['validato'] == 0) {
        // Aggiorna il campo "validato" nel database
        $update = $pdo->prepare("UPDATE utenti SET validato = 1 WHERE id = :id");
        $update->execute([':id' => $utente['id']]);
        $message = "Accesso valido. Token convalidato per l'utente: " . $utente['nome'] . " " . $utente['cognome'];
    } else {
        $message = "Token già utilizzato.";
    }
} else {
    $message = "Token non valido.";
}

// Restituisce il messaggio di risposta
echo $message;
?>