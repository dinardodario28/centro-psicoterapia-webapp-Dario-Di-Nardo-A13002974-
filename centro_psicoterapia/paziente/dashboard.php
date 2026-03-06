<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['ruolo'] !== 'Paziente') {
    header('Location: ../login.php');
    exit;
}

$paziente_id = $_SESSION['user_id'];
$messaggio = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['richiedi_modifica'])) {
    $id_seduta = $_POST['id_seduta'];
    $nuova_data = $_POST['nuova_data'];
    $nuova_ora = $_POST['nuova_ora'];

    $sql = "UPDATE seduta SET Data_Modifica = :ndata, Ora_Modifica = :nora WHERE ID_Seduta = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ndata' => $nuova_data, ':nora' => $nuova_ora, ':id' => $id_seduta]);
    header("Location: dashboard.php?view=appuntamenti&msg=modifica_ok");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['richiedi_seduta'])) {
    $data_scelta = $_POST['data'];
    $ora_scelta = $_POST['ora'];
    $id_percorso = $_POST['id_percorso'];
    $id_approccio = $_POST['id_approccio'];

    try {
        $sql_insert = "INSERT INTO seduta (Data, Ora, Durata, ID_percorso, ID_Approccio, ID_Segreteria) 
                       VALUES (:data, :ora, 60, :id_percorso, :id_approccio, NULL)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            ':data' => $data_scelta, ':ora' => $ora_scelta, 
            ':id_percorso' => $id_percorso, ':id_approccio' => $id_approccio
        ]);
        header("Location: dashboard.php?view=appuntamenti&msg=prenotazione_ok");
        exit;
    } catch (\PDOException $e) {
        $messaggio = "Errore durante l'invio della richiesta: " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'modifica_ok') $messaggio = "Richiesta di spostamento inviata alla segreteria!";
    if ($_GET['msg'] === 'prenotazione_ok') $messaggio = "Richiesta inviata! Attendi la conferma dalla segreteria.";
}

$view = $_GET['view'] ?? 'home'; 

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Paziente - Centro Psicoterapia</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #F8FAFC; } 
        
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .dashboard-card {
            background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px; margin-bottom: 30px; border-top: 6px solid #ccc;
        }

        .card-appuntamenti { border-top-color: #3B82F6; } 
        .card-nuova { border-top-color: #10B981; } 
        .card-percorso { border-top-color: #8B5CF6; } 

        .section-header { display: flex; align-items: center; gap: 15px; margin-bottom: 5px; }
        .section-header h2 { margin: 0; border: none; font-size: 2rem; color: #1E293B; }
        .section-desc { color: #64748B; font-size: 1.1rem; margin-bottom: 30px; }

        .grid-home { display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; margin-top: 20px; }
        .card-link {
            background: #FFFFFF; border-radius: 12px; padding: 40px 30px; text-align: center;
            text-decoration: none; color: #1E293B; width: 320px; border-top: 6px solid #CBD5E1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease;
        }
        .card-link.appuntamenti { border-top-color: #3B82F6; }
        .card-link.nuova { border-top-color: #10B981; }
        .card-link.percorso { border-top-color: #8B5CF6; }
        .card-link:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-link h3 { font-size: 1.5rem; margin-top: 15px; border: none; padding: 0; }
        .card-link p { color: #64748B; font-size: 1rem; margin-top: 10px; }
        .icon-big { font-size: 3.5rem; }

        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: #F8FAFC; color: #475569; padding: 18px; font-size: 0.95rem; text-transform: uppercase; border-bottom: 2px solid #E2E8F0; text-align: left; }
        .modern-table td { padding: 20px 18px; vertical-align: middle; border-bottom: 1px solid #F1F5F9; font-size: 1.05rem; color: #334155; }
        .modern-table tr:hover td { background-color: #F8FAFC; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; display: inline-block; text-align: center; }
        .badge-attesa { background-color: #FEF3C7; color: #D97706; border: 1px solid #FDE68A; }
        .badge-confermata { background-color: #D1FAE5; color: #059669; border: 1px solid #A7F3D0; }
        .badge-modifica { background-color: #DBEAFE; color: #2563EB; border: 1px solid #BFDBFE; }
        .badge-completata { background-color: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }

        .table-input { width: auto; padding: 8px; border: 1px solid #CBD5E1; border-radius: 6px; background-color: #FFF; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; margin-bottom: 20px; font-size: 1rem; }
        .btn-action { padding: 10px 15px; font-weight: bold; font-size: 0.9rem; color: white; border: none; border-radius: 6px; cursor: pointer; transition: transform 0.1s, box-shadow 0.2s; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-blue { background-color: #3B82F6; }
        .btn-green { background-color: #10B981; width: 100%; padding: 14px; font-size: 1.1rem; }

        .top-bar-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #64748B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: background 0.2s; }
        .btn-back:hover { background: #475569; }
    </style>
</head>
<body>
    <header>
        <h1>Centro di Psicoterapia - Area Paziente</h1>
        <nav>
            <a href="dashboard.php" style="background: transparent; color: var(--primary-color); border: 1px solid var(--primary-color);">🏠 Home</a>
            <span>Ciao, <?= htmlspecialchars($_SESSION['nome']) ?></span>
            <a href="../logout.php" style="background-color: #EF4444;">Esci</a>
        </nav>
    </header>

    <div class="dashboard-container">
        
        <?php if ($messaggio): ?>
            <div class="alert alert-success" style="font-size: 1.1rem; padding: 15px; margin-bottom: 30px; border-radius: 8px; border: 1px solid #A7F3D0; background-color: #D1FAE5; color: #065F46;">
                ✅ <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <?php 

        if ($view === 'home'): 
        ?>
            <h2 style="text-align: center; border: none; font-size: 2.2rem; color: #1E293B; margin-top: 20px;">Come possiamo aiutarti oggi?</h2>
            <div class="grid-home">
                <a href="dashboard.php?view=appuntamenti" class="card-link appuntamenti">
                    <div class="icon-big">📅</div>
                    <h3>I Tuoi Appuntamenti</h3>
                    <p>Visualizza lo storico e chiedi di spostare una seduta futura.</p>
                </a>
                <a href="dashboard.php?view=nuova_seduta" class="card-link nuova">
                    <div class="icon-big">➕</div>
                    <h3>Prenota Nuova Seduta</h3>
                    <p>Scegli data e ora per richiedere un nuovo incontro.</p>
                </a>
                <a href="dashboard.php?view=percorso" class="card-link percorso">
                    <div class="icon-big">🌱</div>
                    <h3>Il Mio Percorso</h3>
                    <p>Visualizza i dettagli del tuo piano terapeutico attuale.</p>
                </a>
            </div>

        <?php 

        elseif ($view === 'appuntamenti'): 
            $stmt_sedute = $pdo->prepare("
                SELECT s.*, a.Nome AS Approccio, u.Nome AS NomePsico, u.Cognome AS CognomePsico
                FROM seduta s
                JOIN piano_terapeutico pt ON s.ID_percorso = pt.ID_percorso
                JOIN approccio a ON s.ID_Approccio = a.ID_Approccio
                JOIN utente u ON pt.ID_Psicologo = u.ID_Utente
                WHERE pt.ID_Paziente = ?
                ORDER BY s.Data DESC, s.Ora DESC
            ");
            $stmt_sedute->execute([$paziente_id]);
            $sedute = $stmt_sedute->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            
            <div class="dashboard-card card-appuntamenti">
                <div class="section-header"><span style="font-size: 2.5rem;">📅</span><h2>I Tuoi Appuntamenti</h2></div>
                <p class="section-desc">Qui trovi lo storico delle tue sedute e i prossimi incontri programmati.</p>
                
                <?php if (count($sedute) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th>Data e Ora</th><th>Dottore</th><th>Stato</th><th>Azione (Sposta)</th></tr></thead>
                        <tbody>
                            <?php foreach ($sedute as $seduta): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($seduta['Data'])) ?></strong><br>
                                        <span style="color:#64748B;">Ore <?= date('H:i', strtotime($seduta['Ora'])) ?></span>
                                    </td>
                                    <td>Dott. <?= htmlspecialchars($seduta['CognomePsico']) ?></td>
                                    <td>
                                        <?php if (is_null($seduta['ID_Segreteria'])): ?>
                                            <span class="badge badge-attesa">⏳ In attesa di conferma</span>
                                        <?php elseif (!is_null($seduta['Data_Modifica'])): ?>
                                            <span class="badge badge-modifica">🔄 Spostamento richiesto al<br><?= date('d/m/Y', strtotime($seduta['Data_Modifica'])) ?></span>
                                        <?php elseif (!is_null($seduta['NoteCartellaClinica'])): ?>
                                            <span class="badge badge-completata">✅ Completata</span>
                                        <?php else: ?>
                                            <span class="badge badge-confermata">🗓️ Confermata</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!is_null($seduta['ID_Segreteria']) && is_null($seduta['Data_Modifica']) && $seduta['Data'] >= date('Y-m-d') && is_null($seduta['NoteCartellaClinica'])): ?>
                                            <form action="dashboard.php" method="POST" style="display:flex; gap:5px; align-items:center;">
                                                <input type="hidden" name="id_seduta" value="<?= $seduta['ID_Seduta'] ?>">
                                                <input type="date" name="nuova_data" class="table-input" required min="<?= date('Y-m-d') ?>">
                                                <input type="time" name="nuova_ora" class="table-input" required>
                                                <button type="submit" name="richiedi_modifica" class="btn-action btn-blue">Sposta</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #CBD5E1;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Non hai ancora appuntamenti registrati.</p>
                <?php endif; ?>
            </div>

        <?php 

        elseif ($view === 'nuova_seduta'): 
            $stmt_piani = $pdo->prepare("
                SELECT pt.ID_percorso, pt.Obiettivo, u.Cognome AS CognomePsicologo 
                FROM piano_terapeutico pt
                JOIN utente u ON pt.ID_Psicologo = u.ID_Utente
                WHERE pt.ID_Paziente = ?
            ");
            $stmt_piani->execute([$paziente_id]);
            $piani = $stmt_piani->fetchAll();
            $approcci = $pdo->query("SELECT * FROM approccio")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            
            <div class="dashboard-card card-nuova" style="max-width: 600px; margin: 0 auto;">
                <div class="section-header"><span style="font-size: 2.5rem;">➕</span><h2>Prenota Incontro</h2></div>
                <p class="section-desc">Seleziona le tue preferenze per la prossima seduta.</p>
                
                <?php if (count($piani) > 0): ?>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="richiedi_seduta" value="1">
                        
                        <label style="font-weight: bold; margin-bottom: 5px; display: block;">Il tuo Percorso (Dottore):</label>
                        <select name="id_percorso" class="form-input" required>
                            <?php foreach ($piani as $piano): ?>
                                <option value="<?= $piano['ID_percorso'] ?>">Dott. <?= htmlspecialchars($piano['CognomePsicologo']) ?> - <?= htmlspecialchars($piano['Obiettivo']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label style="font-weight: bold; margin-bottom: 5px; display: block;">Tipo di Approccio:</label>
                        <select name="id_approccio" class="form-input" required>
                            <?php foreach ($approcci as $app): ?>
                                <option value="<?= $app['ID_Approccio'] ?>"><?= htmlspecialchars($app['Nome']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <label style="font-weight: bold; margin-bottom: 5px; display: block;">Data Preferita:</label>
                                <input type="date" name="data" class="form-input" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div style="flex: 1;">
                                <label style="font-weight: bold; margin-bottom: 5px; display: block;">Ora Preferita:</label>
                                <input type="time" name="ora" class="form-input" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-action btn-green">Invia Richiesta alla Segreteria</button>
                    </form>
                <?php else: ?>
                    <p style="color: #EF4444;">Non hai ancora un piano terapeutico attivo. Contatta la segreteria.</p>
                <?php endif; ?>
            </div>

        <?php 

        elseif ($view === 'percorso'): 
            $stmt = $pdo->prepare("
                SELECT pt.*, u.Nome AS NomePsico, u.Cognome AS CognomePsico 
                FROM piano_terapeutico pt
                JOIN utente u ON pt.ID_Psicologo = u.ID_Utente
                WHERE pt.ID_Paziente = ?
            ");
            $stmt->execute([$paziente_id]);
            $piani = $stmt->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            
            <div class="dashboard-card card-percorso">
                <div class="section-header"><span style="font-size: 2.5rem;">🌱</span><h2>Il Mio Percorso</h2></div>
                <p class="section-desc">Riepilogo del piano terapeutico concordato con il tuo specialista.</p>
                
                <?php if (count($piani) > 0): ?>
                    <?php foreach ($piani as $piano): ?>
                        <div style="background: #F8FAFC; padding: 25px; border-radius: 12px; border-left: 6px solid #8B5CF6; margin-bottom: 20px;">
                            <h3 style="margin-top: 0; color: #1E293B;">Obiettivo: <?= htmlspecialchars($piano['Obiettivo']) ?></h3>
                            <p><strong>Psicologo Assegnato:</strong> Dott. <?= htmlspecialchars($piano['CognomePsico'] . ' ' . $piano['NomePsico']) ?></p>
                            <p><strong>Iniziato il:</strong> <?= date('d/m/Y', strtotime($piano['DataInizio'])) ?></p>
                            <?php if ($piano['DataFine']): ?>
                                <p><strong>Data Fine Prevista:</strong> <?= date('d/m/Y', strtotime($piano['DataFine'])) ?></p>
                            <?php else: ?>
                                <p><strong>Stato:</strong> <span style="color: #10B981; font-weight: bold;">In corso</span></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nessun percorso attivo al momento.</p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>