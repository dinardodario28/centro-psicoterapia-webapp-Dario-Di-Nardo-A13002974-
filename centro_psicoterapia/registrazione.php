<?php
session_start();
require_once 'config/database.php';

$messaggio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome']));
    $cognome = htmlspecialchars(trim($_POST['cognome']));
    $cf = strtoupper(trim($_POST['codiceFiscale'])); 
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $ruolo = 'Paziente'; 

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messaggio = "Formato email non valido.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO utente (Nome, Cognome, CodiceFiscale, Email, Password, Telefono, Ruolo) 
                    VALUES (:nome, :cognome, :cf, :email, :password, :telefono, :ruolo)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':cognome' => $cognome,
                ':cf' => $cf,
                ':email' => $email,
                ':password' => $passwordHash, 
                ':telefono' => $telefono,
                ':ruolo' => $ruolo
            ]);

            $messaggio = "Registrazione completata con successo! Ora puoi effettuare il login.";
            
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $messaggio = "Errore: L'Email o il Codice Fiscale risultano già registrati nel sistema.";
            } else {
                $messaggio = "Errore di sistema: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione Paziente - Centro Psicoterapia</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Centro di Psicoterapia Familiare</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="login.php">Accedi</a>
        </nav>
    </header>

    <main>
        <h2>Registrazione Nuovo Paziente</h2>
        
        <?php if ($messaggio): ?>
            <p class="alert"><?= $messaggio ?></p>
        <?php endif; ?>

        <form action="registrazione.php" method="POST" onsubmit="return validaRegistrazione();">
            <fieldset>
                <legend>I tuoi Dati Personali</legend>

                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>

                <label for="cognome">Cognome:</label>
                <input type="text" id="cognome" name="cognome" required>

                <label for="codiceFiscale">Codice Fiscale:</label>
                <input type="text" id="codiceFiscale" name="codiceFiscale" maxlength="16" required>

                <label for="telefono">Telefono:</label>
                <input type="text" id="telefono" name="telefono" required>
            </fieldset>

            <fieldset>
                <legend>Credenziali di Accesso</legend>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </fieldset>

            <button type="submit" style="margin-top: 15px;">Registrati</button>
        </form>
    </main>

    <script src="js/script.js"></script>
</body>
</html>