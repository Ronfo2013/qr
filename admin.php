<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

// =========================
// QUERY UTENTI BLOCCATI (ESCLUSO ANGELO BERNARDINI)
// =========================
$stmtBlocked = $pdo->prepare("
    SELECT email, COUNT(*) AS iscrizioni_non_validate
    FROM utenti
    WHERE (validato = 0 OR validato IS NULL)
    AND email NOT IN ('angelo.bernardini@gmail.com')
    GROUP BY email
    HAVING iscrizioni_non_validate >= 5
");
$stmtBlocked->execute();
$blockedUsers = $stmtBlocked->fetchAll(PDO::FETCH_ASSOC);

// =========================
// QUERY UTENTI HOT
// =========================
$stmtHotUsers = $pdo->query("
    SELECT email, COUNT(*) AS iscrizioni, SUM(validato) AS validati
    FROM utenti
    GROUP BY email
    HAVING iscrizioni > 3 AND validati = iscrizioni
");
$hotUsers = $stmtHotUsers->fetchAll(PDO::FETCH_ASSOC);

// =========================
// QUERY LISTA EVENTI CON STATISTICHE
// =========================
$stmtEvents = $pdo->query("
    SELECT evento, 
        COUNT(*) AS tot_iscritti,
        SUM(CASE WHEN validato = 1 THEN 1 ELSE 0 END) AS validati,
        SUM(CASE WHEN validato = 0 OR validato IS NULL THEN 1 ELSE 0 END) AS da_validare
    FROM utenti
    GROUP BY evento
    ORDER BY STR_TO_DATE(evento, '%d-%m-%Y') DESC
");
$eventsList = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestione Eventi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <header class="d-flex justify-content-between align-items-center bg-primary text-white p-3 rounded">
            <h2>Gestione Eventi</h2>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </header>

        <!-- SEZIONE UTENTI BLOCCATI -->
        <div class="card mt-3 p-3 bg-warning">
            <h3>🚫 Utenti Bloccati</h3>
            <ul>
                <?php if (!empty($blockedUsers)): ?>
                    <?php foreach ($blockedUsers as $blocked): ?>
                        <li>
                            <?= htmlspecialchars($blocked["email"]); ?> - <?= $blocked["iscrizioni_non_validate"]; ?> iscrizioni senza validazione
                            <button class="btn btn-sm btn-danger unlock-user" data-email="<?= htmlspecialchars($blocked["email"]); ?>">Sblocca</button>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Nessun utente bloccato.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- SEZIONE UTENTI HOT -->
        <div class="card mt-3 p-3 bg-success text-white">
            <h3>🔥 Utenti HOT</h3>
            <ul>
                <?php if (!empty($hotUsers)): ?>
                    <?php foreach ($hotUsers as $hot): ?>
                        <li><?= htmlspecialchars($hot["email"]); ?> - <?= $hot["iscrizioni"]; ?> iscrizioni e tutte validate</li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Nessun utente HOT.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- LISTA EVENTI CON STATISTICHE -->
        <div class="card mt-3 p-3">
            <h3>Eventi</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Data Evento</th>
                        <th>Iscritti</th>
                        <th>Validati</th>
                        <th>Da Validare</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventsList as $ev): ?>
                        <tr>
                            <td><?= htmlspecialchars($ev["evento"]); ?></td>
                            <td><?= $ev["tot_iscritti"]; ?></td>
                            <td><?= $ev["validati"]; ?></td>
                            <td><?= $ev["da_validare"]; ?></td>
                            <td>
                                <button class="btn btn-info btn-sm load-details" data-evento="<?= htmlspecialchars($ev['evento']); ?>">
                                    Vedi Iscritti
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- FORM CREAZIONE EVENTO -->
        <div class="card mt-3 p-3">
            <h3>Crea Nuovo Evento</h3>
            <form action="crea_evento.php" method="POST">
                <label for="evento">Data Evento (GG-MM-AAAA):</label>
                <input type="text" id="evento" name="evento" class="form-control" required>
                <label for="titolo">Titolo Evento:</label>
                <input type="text" id="titolo" name="titolo" class="form-control" required>
                <button type="submit" class="btn btn-primary mt-2">Crea Evento</button>
            </form>
        </div>

        <!-- SEZIONE DETTAGLI UTENTI -->
        <div id="dettagliUtenti" class="mt-4"></div>
    </div>

    <!-- AJAX -->
    <script>
    $(document).ready(function() {
        $(".load-details").click(function() {
            let evento = $(this).data("evento");

            $.ajax({
                url: "dettagli_evento.php",
                type: "GET",
                data: { evento: evento },
                success: function(response) {
                    $("#dettagliUtenti").html(response);
                },
                error: function() {
                    alert("Errore nel caricamento dei dettagli.");
                }
            });
        });

        $(".unlock-user").click(function() {
            let email = $(this).data("email");

            $.ajax({
                url: "sblocca_utente.php",
                type: "POST",
                data: { email: email },
                success: function(response) {
                    alert(response);
                    location.reload();
                },
                error: function() {
                    alert("Errore nello sblocco dell'utente.");
                }
            });
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>