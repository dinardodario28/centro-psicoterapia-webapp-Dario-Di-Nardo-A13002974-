<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['ruolo'] !== 'Segreteria') {
    header('Location: ../login.php');
    exit;
}

$segreteria_id = $_SESSION['user_id'];
$messaggio = '';
$errore = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_triage'])) {
    $id_seduta = $_POST['id_seduta'];
    if ($_POST['azione_triage'] === 'conferma') {
        $pdo->prepare("UPDATE seduta SET ID_Segreteria = :id_seg WHERE ID_Seduta = :id")->execute([':id_seg' => $segreteria_id, ':id' => $id_seduta]);
        header("Location: dashboard.php?view=triage&msg=confermata"); exit;
    } else {
        $pdo->prepare("DELETE FROM seduta WHERE ID_Seduta = :id")->execute([':id' => $id_seduta]);
        header("Location: dashboard.php?view=triage&msg=rifiutata"); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_modifica'])) {
    $id_seduta = $_POST['id_seduta'];
    if ($_POST['azione_modifica'] === 'accetta') {
        $pdo->prepare("UPDATE seduta SET Data = Data_Modifica, Ora = Ora_Modifica, Data_Modifica = NULL, Ora_Modifica = NULL WHERE ID_Seduta = :id")->execute([':id' => $id_seduta]);
        header("Location: dashboard.php?view=spostamenti&msg=spostamento_ok"); exit;
    } else {
        $pdo->prepare("UPDATE seduta SET Data_Modifica = NULL, Ora_Modifica = NULL WHERE ID_Seduta = :id")->execute([':id' => $id_seduta]);
        header("Location: dashboard.php?view=spostamenti&msg=spostamento_no"); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuovo_paziente'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO utente (Nome, Cognome, CodiceFiscale, Email, Password, Telefono, Ruolo) VALUES (:nome, :cognome, :cf, :email, :pass, :tel, 'Paziente')");
        $stmt->execute([':nome' => trim($_POST['nome']), ':cognome' => trim($_POST['cognome']), ':cf' => strtoupper(trim($_POST['cf'])), ':email' => trim($_POST['email']), ':pass' => trim($_POST['password']), ':tel' => trim($_POST['telefono'])]);
        header("Location: dashboard.php?view=home&msg=paziente_ok"); exit;
    } catch (\PDOException $e) {
        $errore = "Errore: Email o Codice Fiscale già esistenti.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuovo_percorso'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO piano_terapeutico (Obiettivo, DataInizio, ID_Paziente, ID_Psicologo) VALUES (:obj, CURDATE(), :id_paz, :id_psi)");
        $stmt->execute([
            ':obj' => trim($_POST['obiettivo']),
            ':id_paz' => $_POST['id_paziente'],
            ':id_psi' => $_POST['id_psicologo']
        ]);
        header("Location: dashboard.php?view=home&msg=percorso_ok"); exit;
    } catch (\PDOException $e) {
        $errore = "Errore durante l'assegnazione: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuova_prenotazione'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO seduta (Data, Ora, Durata, ID_percorso, ID_Approccio, ID_Segreteria) VALUES (:data, :ora, 60, :id_percorso, :id_approccio, :id_seg)");
        $stmt->execute([':data' => $_POST['data'], ':ora' => $_POST['ora'], ':id_percorso' => $_POST['id_percorso'], ':id_approccio' => $_POST['id_approccio'], ':id_seg' => $segreteria_id]);
        header("Location: dashboard.php?view=calendario&msg=prenotazione_diretta_ok"); exit;
    } catch (\PDOException $e) {
        $errore = "Errore durante l'inserimento: " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    $messaggi_ok = [
        'confermata' => "Seduta confermata! Aggiunta al calendario.",
        'rifiutata' => "Richiesta rifiutata ed eliminata.",
        'spostamento_ok' => "Spostamento accettato! Calendario aggiornato.",
        'spostamento_no' => "Spostamento rifiutato.",
        'paziente_ok' => "Nuovo paziente registrato con successo.",
        'percorso_ok' => "Paziente assegnato allo psicologo con successo! Ora puoi prenotargli le sedute.",
        'prenotazione_diretta_ok' => "Prenotazione aggiunta e confermata in calendario."
    ];
    if (array_key_exists($_GET['msg'], $messaggi_ok)) $messaggio = $messaggi_ok[$_GET['msg']];
}

$view = $_GET['view'] ?? 'home'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Segreteria - Centro Psicoterapia</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #F8FAFC; } 
        .dashboard-container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .dashboard-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); padding: 40px; margin-bottom: 30px; border-top: 6px solid #ccc; }
        
        .card-triage { border-top-color: #F59E0B; } 
        .card-spostamenti { border-top-color: #8B5CF6; } 
        .card-calendario { border-top-color: #3B82F6; } 
        .card-nuovo-paziente { border-top-color: #14B8A6; } 
        .card-assegna-percorso { border-top-color: #F43F5E; } 
        .card-nuova-prenotazione { border-top-color: #EC4899; }

        .section-header { display: flex; align-items: center; gap: 15px; margin-bottom: 5px; }
        .section-header h2 { margin: 0; border: none; font-size: 2rem; color: #1E293B; }
        .section-desc { color: #64748B; font-size: 1.1rem; margin-bottom: 30px; }

        .grid-home { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 30px; 
            max-width: 1050px; 
            margin: 30px auto; 
        }
        
        .card-link { 
            background: #FFFFFF; border-radius: 12px; padding: 35px 20px; text-align: center; 
            text-decoration: none; color: #1E293B; border-top: 6px solid #CBD5E1; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease; 
        }
        
        .card-link.triage { border-top-color: #F59E0B; }
        .card-link.spostamenti { border-top-color: #8B5CF6; }
        .card-link.calendario { border-top-color: #3B82F6; }
        .card-link.nuovo-paziente { border-top-color: #14B8A6; }
        .card-link.assegna-percorso { border-top-color: #F43F5E; }
        .card-link.nuova-prenotazione { border-top-color: #EC4899; }
        
        .card-link:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-link h3 { font-size: 1.4rem; margin-top: 15px; border: none; padding: 0; }
        .card-link p { color: #64748B; font-size: 0.95rem; margin-top: 10px; }
        .icon-big { font-size: 3rem; }

        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: #F8FAFC; color: #475569; padding: 18px; font-size: 0.95rem; text-transform: uppercase; border-bottom: 2px solid #E2E8F0; text-align: left; }
        .modern-table td { padding: 20px 18px; vertical-align: middle; border-bottom: 1px solid #F1F5F9; font-size: 1.05rem; color: #334155; }
        .modern-table tr:hover td { background-color: #F8FAFC; }
        .user-name { font-size: 1.15rem; font-weight: 700; color: #0F172A; }
        .doc-name { color: #3B82F6; font-weight: bold; }

        .form-input { width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; margin-bottom: 20px; font-size: 1rem; box-sizing: border-box; }
        .form-row { display: flex; gap: 15px; }
        .form-col { flex: 1; }
        .form-label { font-weight: bold; display: block; margin-bottom: 8px; color: #334155; }

        .btn-action { padding: 10px 15px; font-weight: bold; font-size: 0.95rem; color: white; border: none; border-radius: 6px; cursor: pointer; transition: transform 0.1s; }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-green { background-color: #10B981; }
        .btn-red { background-color: #EF4444; }
        .btn-full { padding: 14px 20px; font-size: 1.1rem; width: 100%; }

        .top-bar-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #64748B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .btn-back:hover { background: #475569; }
    </style>
</head>
<body>
    <header>
        <h1>Area Riservata - Segreteria</h1>
        <nav>
            <a href="dashboard.php" style="background: transparent; color: var(--primary-color); border: 1px solid var(--primary-color);">🏠 Home</a>
            <span>Operatore: <?= htmlspecialchars($_SESSION['nome']) ?></span>
            <a href="../logout.php" style="background-color: #EF4444;">Esci</a>
        </nav>
    </header>

    <div class="dashboard-container">
        
        <?php if ($messaggio): ?>
            <div class="alert alert-success" style="font-size: 1.1rem; padding: 15px; margin-bottom: 30px; border-radius: 8px; border: 1px solid #A7F3D0; background-color: #D1FAE5; color: #065F46;">
                ✅ <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <?php if ($errore): ?>
            <div class="alert" style="font-size: 1.1rem; padding: 15px; margin-bottom: 30px; border-radius: 8px; border: 1px solid #FCA5A5; background-color: #FEF2F2; color: #991B1B;">
                ⚠️ <?= htmlspecialchars($errore) ?>
            </div>
        <?php endif; ?>

        <?php 

        if ($view === 'home'): 
        ?>
            <h2 style="text-align: center; border: none; font-size: 2.2rem; color: #1E293B; margin-top: 20px;">Pannello Operativo</h2>
            
            <div class="grid-home">
                <a href="dashboard.php?view=triage" class="card-link triage"><div class="icon-big">📥</div><h3>Triage (Attesa)</h3><p>Approva nuove prenotazioni web.</p></a>
                <a href="dashboard.php?view=spostamenti" class="card-link spostamenti"><div class="icon-big">🔄</div><h3>Spostamenti</h3><p>Gestisci cambi data/orario.</p></a>
                <a href="dashboard.php?view=calendario" class="card-link calendario"><div class="icon-big">📅</div><h3>Calendario</h3><p>Visualizza sedute confermate.</p></a>
                
                <a href="dashboard.php?view=nuovo_paziente" class="card-link nuovo-paziente"><div class="icon-big">👤</div><h3>1. Registra Paziente</h3><p>Crea anagrafica nuovo utente.</p></a>
                <a href="dashboard.php?view=assegna_percorso" class="card-link assegna-percorso"><div class="icon-big">🤝</div><h3>2. Assegna Percorso</h3><p>Collega paziente a psicologo.</p></a>
                <a href="dashboard.php?view=nuova_prenotazione" class="card-link nuova-prenotazione"><div class="icon-big">☎️</div><h3>3. Prenotazione Rapida</h3><p>Fissa seduta scavalcando il triage.</p></a>
            </div>

        <?php 

        elseif ($view === 'triage'): 
            $richieste = $pdo->query("SELECT s.*, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz, psi.Cognome AS CognomePsi, a.Nome AS Approccio FROM seduta s JOIN piano_terapeutico pt ON s.ID_percorso = pt.ID_percorso JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente JOIN utente psi ON pt.ID_Psicologo = psi.ID_Utente JOIN approccio a ON s.ID_Approccio = a.ID_Approccio WHERE s.ID_Segreteria IS NULL ORDER BY s.Data ASC, s.Ora ASC")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            <div class="dashboard-card card-triage">
                <div class="section-header"><span style="font-size: 2.5rem;">📥</span><h2>Richieste in Attesa</h2></div>
                <?php if (count($richieste) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th>Data e Ora</th><th>Paziente</th><th>Psicologo</th><th>Azioni</th></tr></thead>
                        <tbody>
                            <?php foreach ($richieste as $r): ?>
                                <tr>
                                    <td><strong><?= date('d/m/Y', strtotime($r['Data'])) ?></strong><br>Ore <?= date('H:i', strtotime($r['Ora'])) ?></td>
                                    <td><span class="user-name"><?= htmlspecialchars($r['CognomePaz'] . ' ' . $r['NomePaz']) ?></span></td>
                                    <td class="doc-name">Dott. <?= htmlspecialchars($r['CognomePsi']) ?></td>
                                    <td>
                                        <form action="dashboard.php" method="POST" style="display:flex; gap:10px;">
                                            <input type="hidden" name="id_seduta" value="<?= $r['ID_Seduta'] ?>">
                                            <button type="submit" name="azione_triage" value="conferma" class="btn-action btn-green">Conferma</button>
                                            <button type="submit" name="azione_triage" value="rifiuta" class="btn-action btn-red">Rifiuta</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?><p>Nessuna richiesta in attesa.</p><?php endif; ?>
            </div>

        <?php elseif ($view === 'spostamenti'): 
            $modifiche = $pdo->query("SELECT s.*, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz, psi.Cognome AS CognomePsi FROM seduta s JOIN piano_terapeutico pt ON s.ID_percorso = pt.ID_percorso JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente JOIN utente psi ON pt.ID_Psicologo = psi.ID_Utente WHERE s.Data_Modifica IS NOT NULL ORDER BY s.Data_Modifica ASC")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            <div class="dashboard-card card-spostamenti">
                <div class="section-header"><span style="font-size: 2.5rem;">🔄</span><h2>Spostamenti</h2></div>
                <?php if (count($modifiche) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th>Paziente</th><th>Vecchia Data</th><th style="color: #8B5CF6;">Nuova Proposta</th><th>Azioni</th></tr></thead>
                        <tbody>
                            <?php foreach ($modifiche as $m): ?>
                                <tr>
                                    <td><span class="user-name"><?= htmlspecialchars($m['CognomePaz'] . ' ' . $m['NomePaz']) ?></span></td>
                                    <td><span style="text-decoration: line-through; color: #94A3B8;"><?= date('d/m/Y', strtotime($m['Data'])) ?> alle <?= date('H:i', strtotime($m['Ora'])) ?></span></td>
                                    <td><strong style="color: #8B5CF6;"><?= date('d/m/Y', strtotime($m['Data_Modifica'])) ?></strong> alle <?= date('H:i', strtotime($m['Ora_Modifica'])) ?></td>
                                    <td>
                                        <form action="dashboard.php" method="POST" style="display:flex; gap:10px;">
                                            <input type="hidden" name="id_seduta" value="<?= $m['ID_Seduta'] ?>">
                                            <button type="submit" name="azione_modifica" value="accetta" class="btn-action btn-green">Accetta</button>
                                            <button type="submit" name="azione_modifica" value="rifiuta" class="btn-action btn-red">Rifiuta</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?><p>Nessuna richiesta di spostamento.</p><?php endif; ?>
            </div>

        <?php elseif ($view === 'calendario'): 
            $calendario = $pdo->query("SELECT s.Data, s.Ora, s.Durata, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz, psi.Cognome AS CognomePsi FROM seduta s JOIN piano_terapeutico pt ON s.ID_percorso = pt.ID_percorso JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente JOIN utente psi ON pt.ID_Psicologo = psi.ID_Utente WHERE s.ID_Segreteria IS NOT NULL AND s.Data_Modifica IS NULL AND s.Data >= CURDATE() ORDER BY s.Data ASC, s.Ora ASC")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            <div class="dashboard-card card-calendario">
                <div class="section-header"><span style="font-size: 2.5rem;">📅</span><h2>Calendario Appuntamenti</h2></div>
                <?php if (count($calendario) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th>Data</th><th>Ora</th><th>Paziente</th><th>Psicologo</th></tr></thead>
                        <tbody>
                            <?php foreach ($calendario as $c): ?>
                                <tr>
                                    <td><strong style="color:#3B82F6;"><?= date('d/m/Y', strtotime($c['Data'])) ?></strong></td>
                                    <td><strong><?= date('H:i', strtotime($c['Ora'])) ?></strong></td>
                                    <td><span class="user-name"><?= htmlspecialchars($c['CognomePaz'] . ' ' . $c['NomePaz']) ?></span></td>
                                    <td class="doc-name">Dott. <?= htmlspecialchars($c['CognomePsi']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?><p>Nessun appuntamento nei prossimi giorni.</p><?php endif; ?>
            </div>

        <?php 

        elseif ($view === 'nuovo_paziente'): ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            <div class="dashboard-card card-nuovo-paziente" style="max-width: 700px; margin: 0 auto;">
                <div class="section-header"><span style="font-size: 2.5rem;">👤</span><h2>1. Registra Paziente</h2></div>
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="nuovo_paziente" value="1">
                    <div class="form-row"><div class="form-col"><label class="form-label">Nome:</label><input type="text" name="nome" class="form-input" required></div><div class="form-col"><label class="form-label">Cognome:</label><input type="text" name="cognome" class="form-input" required></div></div>
                    <div class="form-row"><div class="form-col"><label class="form-label">Codice Fiscale:</label><input type="text" name="cf" class="form-input" maxlength="16" required></div><div class="form-col"><label class="form-label">Telefono:</label><input type="text" name="telefono" class="form-input" required></div></div>
                    <label class="form-label">Email (usata per il Login):</label><input type="email" name="email" class="form-input" required>
                    <label class="form-label">Password provvisoria:</label><input type="text" name="password" class="form-input" value="paziente123" required>
                    <button type="submit" class="btn-action btn-full" style="background-color: #14B8A6;">Crea Account Paziente</button>
                </form>
            </div>

        <?php 

        elseif ($view === 'assegna_percorso'): 
            $pazienti = $pdo->query("SELECT ID_Utente, Nome, Cognome, CodiceFiscale FROM utente WHERE Ruolo = 'Paziente' ORDER BY Cognome ASC")->fetchAll();
            $psicologi = $pdo->query("SELECT ID_Utente, Nome, Cognome FROM utente WHERE Ruolo = 'Psicologo' ORDER BY Cognome ASC")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            <div class="dashboard-card card-assegna-percorso" style="max-width: 700px; margin: 0 auto;">
                <div class="section-header"><span style="font-size: 2.5rem;">🤝</span><h2>2. Assegna Percorso</h2></div>
                <p class="section-desc">Collega il paziente a uno psicologo per permettergli di prenotare sedute.</p>
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="nuovo_percorso" value="1">
                    <label class="form-label">Seleziona Paziente:</label>
                    <select name="id_paziente" class="form-input" required>
                        <option value="">-- Scegli Paziente --</option>
                        <?php foreach ($pazienti as $p): ?>
                            <option value="<?= $p['ID_Utente'] ?>"><?= htmlspecialchars($p['Cognome'] . ' ' . $p['Nome']) ?> (CF: <?= htmlspecialchars($p['CodiceFiscale']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">Seleziona Psicologo Curante:</label>
                    <select name="id_psicologo" class="form-input" required>
                        <option value="">-- Scegli Psicologo --</option>
                        <?php foreach ($psicologi as $psi): ?>
                            <option value="<?= $psi['ID_Utente'] ?>">Dott. <?= htmlspecialchars($psi['Cognome'] . ' ' . $psi['Nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">Motivazione/Obiettivo Terapeutico iniziale:</label>
                    <input type="text" name="obiettivo" class="form-input" placeholder="Es. Gestione ansia, Colloquio conoscitivo..." required>
                    <button type="submit" class="btn-action btn-full" style="background-color: #F43F5E;">Apri Percorso Terapeutico</button>
                </form>
            </div>

        <?php 

        elseif ($view === 'nuova_prenotazione'): 
            $piani = $pdo->query("SELECT pt.ID_percorso, pt.Obiettivo, paz.Nome AS NomePaz, paz.Cognome AS CognPaz, psi.Cognome AS CognPsi FROM piano_terapeutico pt JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente JOIN utente psi ON pt.ID_Psicologo = psi.ID_Utente ORDER BY paz.Cognome ASC")->fetchAll();
            $approcci = $pdo->query("SELECT * FROM approccio")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            <div class="dashboard-card card-nuova-prenotazione" style="max-width: 700px; margin: 0 auto;">
                <div class="section-header"><span style="font-size: 2.5rem;">☎️</span><h2>3. Prenotazione Rapida</h2></div>
                <p class="section-desc">Inserisci un appuntamento direttamente nel calendario (scavalcando l'approvazione).</p>
                <?php if (count($piani) > 0): ?>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="nuova_prenotazione" value="1">
                        <label class="form-label">Paziente e Percorso in corso:</label>
                        <select name="id_percorso" class="form-input" required>
                            <option value="">-- Seleziona Paziente --</option>
                            <?php foreach ($piani as $piano): ?>
                                <option value="<?= $piano['ID_percorso'] ?>"><?= htmlspecialchars($piano['CognPaz'] . ' ' . $piano['NomePaz']) ?> (Dott. <?= htmlspecialchars($piano['CognPsi']) ?> - <?= htmlspecialchars($piano['Obiettivo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <label class="form-label">Approccio Terapeutico:</label>
                        <select name="id_approccio" class="form-input" required>
                            <?php foreach ($approcci as $app): ?>
                                <option value="<?= $app['ID_Approccio'] ?>"><?= htmlspecialchars($app['Nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-row">
                            <div class="form-col"><label class="form-label">Data Appuntamento:</label><input type="date" name="data" class="form-input" required min="<?= date('Y-m-d') ?>"></div>
                            <div class="form-col"><label class="form-label">Orario:</label><input type="time" name="ora" class="form-input" required></div>
                        </div>
                        <button type="submit" class="btn-action btn-full" style="background-color: #EC4899;">Salva in Calendario</button>
                    </form>
                <?php else: ?>
                    <p style="color: #EF4444;">Nessun percorso attivo. Crea prima un piano terapeutico tramite "Assegna Percorso".</p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>