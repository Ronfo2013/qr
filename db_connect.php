<?php
// Imposta i dettagli di connessione al database
$host = "db5017005042.hosting-data.io";
$dbname = "dbs13698425";
$username = "dbu2097736";
$password = "Camilla2020@";

try {
    // Crea la connessione PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Imposta PDO per generare errori dettagliati in caso di problemi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Registra l'errore in un file invece di mostrarlo all'utente
    error_log("Errore di connessione al database: " . $e->getMessage(), 3, __DIR__ . '/error.log');

    // Mostra un messaggio generico senza rivelare dettagli del database
    die("Si è verificato un errore di connessione. Riprova più tardi.");
}
?>