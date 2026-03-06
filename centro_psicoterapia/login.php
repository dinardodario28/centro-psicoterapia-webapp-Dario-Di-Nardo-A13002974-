<?php
session_start();
require_once 'config/database.php';

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare('SELECT * FROM utente WHERE Email = ?');
    $stmt->execute([$email]);
    $utente = $stmt->fetch();


    if ($utente && $password === $utente['Password']) {
        
        $_SESSION['user_id'] = $utente['ID_Utente'];
        $_SESSION['ruolo'] = $utente['Ruolo'];
        $_SESSION['nome'] = $utente['Nome'];

        switch ($utente['Ruolo']) {
            case 'Admin':
                header('Location: admin/dashboard.php');
                break;
            case 'Segreteria':
                header('Location: segreteria/dashboard.php');
                break;
            case 'Psicologo':
                header('Location: psicologo/dashboard.php');
                break;
            case 'Paziente':
                header('Location: paziente/dashboard.php');
                break;
        }
        exit;
    } else {
        $errore = 'Email o password errati!';
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - Centro Psicoterapia</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Accesso Area Riservata</h2>
    <?php if ($errore): ?>
        <p style="color: red;"><?= htmlspecialchars($errore) ?></p>
    <?php endif; ?>
    
    <form action="login.php" method="POST" onsubmit="return validaLogin()">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br><br>
        <button type="submit">Accedi</button>
    </form>

    <script src="js/script.js"></script>
</body>
</html>