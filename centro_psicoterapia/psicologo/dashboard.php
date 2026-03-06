<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['ruolo'] !== 'Psicologo') {
    header('Location: ../login.php');
    exit;
}

$psicologo_id = $_SESSION['user_id'];
$messaggio = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_note'])) {
    $id_seduta = (int)$_POST['id_seduta'];
    $note = trim($_POST['note_cliniche']);
    if (!empty($note)) {
        $stmt = $pdo->prepare("UPDATE seduta SET NoteCartellaClinica = :note WHERE ID_Seduta = :id_seduta");
        $stmt->execute([':note' => $note, ':id_seduta' => $id_seduta]);
        header("Location: dashboard.php?view=refertare&msg=referto_ok");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_cartella'])) {
    $id_cartella = (int)$_POST['id_cartella'];
    $id_percorso = (int)$_POST['id_percorso']; 
    $diagnosi = trim($_POST['diagnosi']);
    $note_generali = trim($_POST['note_generali']);

    $stmt = $pdo->prepare("UPDATE cartella_clinica SET DiagnosiIniziale = :diag, NoteGenerali = :note WHERE Id_Cartella = :id_cartella");
    $stmt->execute([':diag' => $diagnosi, ':note' => $note_generali, ':id_cartella' => $id_cartella]);
    header("Location: dashboard.php?view=dettaglio_cartella&id_percorso=$id_percorso&msg=cartella_ok");
    exit;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'referto_ok') $messaggio = "Referto della seduta salvato con successo!";
    if ($_GET['msg'] === 'cartella_ok') $messaggio = "Dati generali della cartella aggiornati!";
}

$view = $_GET['view'] ?? 'home'; 

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Psicologo - Centro Psicoterapia</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #F1F5F9; } 
        
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .dashboard-card {
            background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px; margin-bottom: 30px; border-top: 6px solid #ccc;
        }

        .card-referti { border-top-color: #F59E0B; } 
        .card-agenda { border-top-color: #3B82F6; } 
        .card-cartelle { border-top-color: #10B981; } 

        .section-header { display: flex; align-items: center; gap: 15px; margin-bottom: 5px; }
        .section-header h2 { margin: 0; border: none; font-size: 2rem; color: #1E293B; }
        .section-desc { color: #64748B; font-size: 1.1rem; margin-bottom: 30px; }

        .grid-home { display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; margin-top: 20px; }
        .card-link {
            background: #FFFFFF; border-radius: 12px; padding: 40px 30px; text-align: center;
            text-decoration: none; color: #1E293B; width: 320px; border-top: 6px solid #CBD5E1;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease;
        }
        .card-link.referti { border-top-color: #F59E0B; }
        .card-link.agenda { border-top-color: #3B82F6; }
        .card-link.cartelle { border-top-color: #10B981; }
        .card-link:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-link h3 { font-size: 1.5rem; margin-top: 15px; border: none; padding: 0; }
        .card-link p { color: #64748B; font-size: 1rem; margin-top: 10px; }
        .icon-big { font-size: 3.5rem; }

        .modern-table { width: 100%; border-collapse: collapse; }
        .modern-table th { background-color: #F8FAFC; color: #475569; padding: 18px; font-size: 0.95rem; text-transform: uppercase; border-bottom: 2px solid #E2E8F0; text-align: left; }
        .modern-table td { padding: 20px 18px; vertical-align: middle; border-bottom: 1px solid #F1F5F9; font-size: 1.05rem; color: #334155; }
        .modern-table tr:hover td { background-color: #F8FAFC; }
        .patient-name { font-size: 1.2rem; font-weight: 700; color: #0F172A; }

        .table-input { width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; background-color: #F8FAFC; font-family: inherit; transition: all 0.2s ease; resize: vertical; }
        .table-input:focus { background-color: #FFFFFF; border-color: #3B82F6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        .btn-action { padding: 12px 20px; font-weight: bold; font-size: 1rem; color: white; border: none; border-radius: 8px; cursor: pointer; transition: transform 0.1s, box-shadow 0.2s; text-align: center; text-decoration: none; display: inline-block; width: 100%; box-sizing: border-box; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-orange { background-color: #F59E0B; }
        .btn-blue { background-color: #3B82F6; }
        .btn-green { background-color: #10B981; }

        .top-bar-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #64748B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: background 0.2s; }
        .btn-back:hover { background: #475569; }

        .timeline-container { border-left: 4px solid #3B82F6; margin-left: 20px; padding-left: 30px; margin-top: 30px; }
        .timeline-item { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 25px; margin-bottom: 25px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -41px; top: 25px; width: 16px; height: 16px; background: #3B82F6; border: 4px solid #F1F5F9; border-radius: 50%; }
        .timeline-date { color: #3B82F6; font-weight: bold; font-size: 1.1rem; margin-bottom: 10px; }
        .timeline-note { color: #334155; font-size: 1.1rem; font-style: italic; line-height: 1.6; }
    </style>
</head>
<body>
    <header>
        <h1>Area Riservata - Psicologo</h1>
        <nav>
            <a href="dashboard.php" style="background: transparent; color: var(--primary-color); border: 1px solid var(--primary-color);">🏠 Home</a>
            <span>Dott. <?= htmlspecialchars($_SESSION['nome']) ?></span>
            <a href="../logout.php" style="background-color: #EF4444;">Esci (Logout)</a>
        </nav>
    </header>

    <div class="dashboard-container">
        
        <?php if ($messaggio): ?>
            <div class="alert alert-success" style="font-size: 1.2rem; padding: 20px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                ✅ <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <?php 

        if ($view === 'home'): 
        ?>
            <h2 style="text-align: center; border: none; font-size: 2.2rem; color: #1E293B; margin-top: 20px;">Scegli un'area di lavoro:</h2>
            <div class="grid-home">
                <a href="dashboard.php?view=refertare" class="card-link referti">
                    <div class="icon-big">📝</div>
                    <h3>Da Refertare</h3>
                    <p>Inserisci le note cliniche per le sedute concluse.</p>
                </a>
                <a href="dashboard.php?view=agenda" class="card-link agenda">
                    <div class="icon-big">📅</div>
                    <h3>Agenda</h3>
                    <p>Visualizza il calendario dei prossimi appuntamenti.</p>
                </a>
                <a href="dashboard.php?view=cartelle" class="card-link cartelle">
                    <div class="icon-big">🗂️</div>
                    <h3>Cartelle Cliniche</h3>
                    <p>Accedi allo storico e aggiorna la diagnosi dei pazienti.</p>
                </a>
            </div>

        <?php 

        elseif ($view === 'refertare'): 
            $stmt = $pdo->prepare("
                SELECT s.ID_Seduta, s.Data, s.Ora, s.Durata, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz, pt.Obiettivo
                FROM seduta s
                JOIN piano_terapeutico pt ON s.ID_percorso = pt.ID_percorso
                JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente
                WHERE pt.ID_Psicologo = ? AND s.ID_Segreteria IS NOT NULL AND s.NoteCartellaClinica IS NULL AND s.Data <= CURDATE()
                ORDER BY s.Data ASC, s.Ora ASC
            ");
            $stmt->execute([$psicologo_id]);
            $sedute = $stmt->fetchAll();
        ?>
            <div class="top-bar-action">
                <a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a>
            </div>
            
            <div class="dashboard-card card-referti">
                <div class="section-header"><span style="font-size: 2.5rem;">📝</span><h2>Sedute da Refertare</h2></div>
                <p class="section-desc">Elenco delle sedute completate per cui devi ancora inserire le note cliniche.</p>
                
                <?php if (count($sedute) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th style="width:15%">Data</th><th style="width:20%">Paziente</th><th style="width:45%">Referto</th><th style="width:20%">Azione</th></tr></thead>
                        <tbody>
                            <?php foreach ($sedute as $s): ?>
                                <tr>
                                    <td><strong><?= date('d/m/Y', strtotime($s['Data'])) ?></strong><br><span style="color:#64748B;">Ore <?= date('H:i', strtotime($s['Ora'])) ?></span></td>
                                    <td><span class="patient-name"><?= htmlspecialchars($s['CognomePaz'] . ' ' . $s['NomePaz']) ?></span><br><small style="color:#64748B;">Obj: <?= htmlspecialchars($s['Obiettivo']) ?></small></td>
                                    <form action="dashboard.php" method="POST">
                                        <td>
                                            <textarea name="note_cliniche" rows="3" class="table-input" required placeholder="Scrivi qui gli appunti della seduta..."></textarea>
                                            <input type="hidden" name="id_seduta" value="<?= $s['ID_Seduta'] ?>">
                                        </td>
                                        <td><button type="submit" name="salva_note" class="btn-action btn-orange">Salva Referto</button></td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #10B981; font-weight: bold; font-size: 1.2rem;">✅ Nessun referto in sospeso. Ottimo lavoro!</p>
                <?php endif; ?>
            </div>

        <?php 

        elseif ($view === 'agenda'): 
            $stmt = $pdo->prepare("
                SELECT s.Data, s.Ora, s.Durata, a.Nome AS Approccio, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz
                FROM seduta s
                JOIN piano_terapeutico pt ON s.ID_percorso = pt.ID_percorso
                JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente
                JOIN approccio a ON s.ID_Approccio = a.ID_Approccio
                WHERE pt.ID_Psicologo = ? AND s.ID_Segreteria IS NOT NULL AND s.Data > CURDATE()
                ORDER BY s.Data ASC, s.Ora ASC
            ");
            $stmt->execute([$psicologo_id]);
            $sedute = $stmt->fetchAll();
        ?>
            <div class="top-bar-action">
                <a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a>
            </div>
            
            <div class="dashboard-card card-agenda">
                <div class="section-header"><span style="font-size: 2.5rem;">📅</span><h2>Prossimi Appuntamenti</h2></div>
                <p class="section-desc">La tua agenda per i prossimi giorni.</p>
                
                <?php if (count($sedute) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th>Data</th><th>Ora</th><th>Paziente</th><th>Approccio</th><th>Durata</th></tr></thead>
                        <tbody>
                            <?php foreach ($sedute as $s): ?>
                                <tr>
                                    <td><strong style="color:#3B82F6; font-size:1.1rem;"><?= date('d/m/Y', strtotime($s['Data'])) ?></strong></td>
                                    <td><strong><?= date('H:i', strtotime($s['Ora'])) ?></strong></td>
                                    <td><span class="patient-name"><?= htmlspecialchars($s['CognomePaz'] . ' ' . $s['NomePaz']) ?></span></td>
                                    <td><?= htmlspecialchars($s['Approccio']) ?></td>
                                    <td><?= htmlspecialchars($s['Durata']) ?> min</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 1.1rem;">Non hai appuntamenti in programma.</p>
                <?php endif; ?>
            </div>

        <?php 

        elseif ($view === 'cartelle'): 
            $stmt = $pdo->prepare("
                SELECT cc.Id_Cartella, pt.ID_percorso, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz, pt.Obiettivo
                FROM cartella_clinica cc
                JOIN piano_terapeutico pt ON cc.ID_percorso = pt.ID_percorso
                JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente
                WHERE pt.ID_Psicologo = ?
                ORDER BY paz.Cognome ASC
            ");
            $stmt->execute([$psicologo_id]);
            $cartelle = $stmt->fetchAll();
        ?>
            <div class="top-bar-action">
                <a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a>
            </div>
            
            <div class="dashboard-card card-cartelle">
                <div class="section-header"><span style="font-size: 2.5rem;">🗂️</span><h2>I Tuoi Pazienti</h2></div>
                <p class="section-desc">Seleziona un paziente per accedere allo storico completo delle sue sedute.</p>
                
                <?php if (count($cartelle) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th style="width:40%">Paziente</th><th style="width:40%">Obiettivo Percorso</th><th style="width:20%">Azione</th></tr></thead>
                        <tbody>
                            <?php foreach ($cartelle as $c): ?>
                                <tr>
                                    <td><span class="patient-name"><?= htmlspecialchars($c['CognomePaz'] . ' ' . $c['NomePaz']) ?></span></td>
                                    <td><?= htmlspecialchars($c['Obiettivo']) ?></td>
                                    <td>
                                        <a href="dashboard.php?view=dettaglio_cartella&id_percorso=<?= $c['ID_percorso'] ?>" class="btn-action btn-blue">Apri Cartella</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #64748B; font-size: 1.1rem;">Non hai ancora pazienti in cura.</p>
                <?php endif; ?>
            </div>

        <?php 

        elseif ($view === 'dettaglio_cartella' && isset($_GET['id_percorso'])): 
            $id_percorso = (int)$_GET['id_percorso'];

            $stmt_cc = $pdo->prepare("
                SELECT cc.*, paz.Nome AS NomePaz, paz.Cognome AS CognomePaz 
                FROM cartella_clinica cc
                JOIN piano_terapeutico pt ON cc.ID_percorso = pt.ID_percorso
                JOIN utente paz ON pt.ID_Paziente = paz.ID_Utente
                WHERE cc.ID_percorso = ? AND pt.ID_Psicologo = ?
            ");
            $stmt_cc->execute([$id_percorso, $psicologo_id]);
            $cartella = $stmt_cc->fetch();

            if (!$cartella) { echo "<p>Cartella non trovata.</p>"; exit; }

            $stmt_storico = $pdo->prepare("
                SELECT Data, Ora, NoteCartellaClinica FROM seduta 
                WHERE ID_percorso = ? AND NoteCartellaClinica IS NOT NULL 
                ORDER BY Data DESC, Ora DESC
            ");
            $stmt_storico->execute([$id_percorso]);
            $storico_note = $stmt_storico->fetchAll();
        ?>
            <div class="top-bar-action">
                <a href="dashboard.php?view=cartelle" class="btn-back">⬅ Torna all'elenco pazienti</a>
            </div>

            <div class="dashboard-card card-cartelle">
                <div class="section-header"><span style="font-size: 2.5rem;">👤</span><h2>Cartella di <?= htmlspecialchars($cartella['CognomePaz'] . ' ' . $cartella['NomePaz']) ?></h2></div>
                
                <div style="background: #F8FAFC; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #10B981;">
                    <p style="margin: 0;"><strong>Anamnesi Familiare Iniziale:</strong><br><?= htmlspecialchars($cartella['AnamnesiFamiliare']) ?></p>
                </div>
                
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="id_cartella" value="<?= $cartella['Id_Cartella'] ?>">
                    <input type="hidden" name="id_percorso" value="<?= $id_percorso ?>">
                    
                    <label style="font-weight: bold; display:block; margin-bottom: 5px;">Diagnosi Attuale:</label>
                    <input type="text" name="diagnosi" class="table-input" value="<?= htmlspecialchars($cartella['DiagnosiIniziale'] ?? '') ?>" style="margin-bottom: 20px;">
                    
                    <label style="font-weight: bold; display:block; margin-bottom: 5px;">Note Generali del Percorso:</label>
                    <textarea name="note_generali" class="table-input" rows="3" style="margin-bottom: 20px;"><?= htmlspecialchars($cartella['NoteGenerali'] ?? '') ?></textarea>
                    
                    <button type="submit" name="salva_cartella" class="btn-action btn-green" style="width: auto;">Aggiorna Dati Generali</button>
                </form>
            </div>

            <h2 style="font-size: 2rem; color: #1E293B; margin-top: 50px;">Storico Sedute</h2>
            <div class="timeline-container">
                <?php if (count($storico_note) > 0): ?>
                    <?php foreach ($storico_note as $nota): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">📅 Seduta del <?= date('d/m/Y', strtotime($nota['Data'])) ?> (Ore <?= date('H:i', strtotime($nota['Ora'])) ?>)</div>
                            <div class="timeline-note">"<?= nl2br(htmlspecialchars($nota['NoteCartellaClinica'])) ?>"</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="timeline-item">
                        <p style="margin: 0; color: #64748B;">Nessun referto passato registrato per questo paziente.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>