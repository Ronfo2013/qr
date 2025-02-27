<?php
require_once 'db_connect.php';

$dataFile = 'date.json';

// Verifica che il form sia stato inviato correttamente
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["evento"])) {
    $evento = trim($_POST["evento"]);

    // Controllo formato data (deve essere dd-mm-yyyy)
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $evento)) {
        die("Errore: Il formato della data non è corretto. Usa dd-mm-yyyy.");
    }

    // Controllo se l'evento esiste già nel database
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utenti WHERE evento = :evento");
    $stmtCheck->execute([':evento' => $evento]);
    $exists = $stmtCheck->fetchColumn();

    if ($exists > 0) {
        die("Errore: L'evento esiste già nel database.");
    }

    // Inseriamo l'evento nella tabella utenti
    $stmtInsert = $pdo->prepare("INSERT INTO utenti (evento) VALUES (:evento)");
    if (!$stmtInsert->execute([':evento' => $evento])) {
        die("Errore nella creazione dell'evento.");
    }

    // =========================
    // AGGIORNIAMO IL FILE JSON
    // =========================
    if (file_exists($dataFile)) {
        $jsonData = json_decode(file_get_contents($dataFile), true);
        if (!isset($jsonData['events'])) {
            $jsonData['events'] = [];
        }
    } else {
        $jsonData = ["events" => []];
    }

    // Verifica se l'evento è già presente nel JSON
    foreach ($jsonData["events"] as $ev) {
        if ($ev["date"] === $evento) {
            die("Errore: L'evento esiste già in date.json.");
        }
    }

    // Aggiungiamo il nuovo evento al JSON
    $jsonData["events"][] = [
        "date" => $evento,
        "titolo" => "Evento " . $evento
    ];

    // Salviamo il file JSON aggiornato
    if (file_put_contents($dataFile, json_encode($jsonData, JSON_PRETTY_PRINT))) {
        echo "Evento creato con successo!";
    } else {
        echo "Errore nella scrittura del file date.json.";
    }
} else {
    die("Errore: Nessun dato ricevuto.");
}
?>