<?php
session_start();

// Imposta una password (questa è solo di esempio)
$correctPassword = "admin123";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? '';

    if ($password === $correctPassword) {
        $_SESSION["logged_in"] = true;
        // Reindirizza alla pagina amministrazione
        header("Location: admin.php");
        exit;
    } else {
        $error = "Password errata.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Reset e stili di base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background: linear-gradient(135deg, #007BFF, #00c6ff);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        /* Card per il form */
        .login-card {
            background: #fff;
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .login-card p.error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        /* Stili del form */
        form {
            display: flex;
            flex-direction: column;
        }
        form label {
            text-align: left;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        form input {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        form input:focus {
            border-color: #007BFF;
            outline: none;
        }
        form button {
            padding: 12px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        form button:hover {
            background: #0056b3;
        }
        /* Link (opzionale) */
        .login-card a {
            color: #007BFF;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 10px;
            display: inline-block;
        }
        .login-card a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Area Riservata</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Inserisci la password" required>
            <button type="submit">Accedi</button>
        </form>
    </div>
</body>
</html>