<?php
require_once 'db_connect.php';

if (!isset($_GET['evento']) || empty($_GET['evento'])) {
    echo "<h4 class='text-danger'>Nessun evento selezionato.</h4>";
    exit;
}

$evento = urldecode($_GET['evento']);

// =========================
// QUERY UTENTI ISCRITTI
// =========================
$stmt = $pdo->prepare("
    SELECT nome, cognome, email, telefono, validato, created_at
    FROM utenti
    WHERE evento = :evento
    ORDER BY created_at DESC
");
$stmt->execute([':evento' => $evento]);
$iscritti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card p-3">
    <h3>Dettagli Iscritti per l'evento: <?= htmlspecialchars($evento); ?></h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Validato</th>
                <th>Data Iscrizione</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($iscritti)): ?>
                <?php foreach ($iscritti as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["nome"]); ?></td>
                        <td><?= htmlspecialchars($row["cognome"]); ?></td>
                        <td><?= htmlspecialchars($row["email"]); ?></td>
                        <td><?= htmlspecialchars($row["telefono"]); ?></td>
                        <td><?= $row["validato"] ? "✅" : "❌"; ?></td>
                        <td><?= htmlspecialchars($row["created_at"]); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">Nessun iscritto per questo evento.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>