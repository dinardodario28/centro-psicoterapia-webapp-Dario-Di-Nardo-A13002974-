<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['ruolo'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$messaggio = '';
$errore = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_impostazioni'])) {
    $_SESSION['orario_apertura'] = $_POST['apertura'];
    $_SESSION['orario_chiusura'] = $_POST['chiusura'];
    header("Location: dashboard.php?view=impostazioni&msg=impostazioni_ok");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_staff'])) {
    try {
        $ruolo = $_POST['ruolo']; 
        $spec = ($ruolo === 'Psicologo') ? trim($_POST['specializzazione']) : NULL;

        $sql = "INSERT INTO utente (Nome, Cognome, CodiceFiscale, Email, Password, Telefono, Ruolo, Specializzazione) 
                VALUES (:nome, :cognome, :cf, :email, :pass, :tel, :ruolo, :spec)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => trim($_POST['nome']),
            ':cognome' => trim($_POST['cognome']),
            ':cf' => strtoupper(trim($_POST['cf'])),
            ':email' => trim($_POST['email']),
            ':pass' => trim($_POST['password']),
            ':tel' => trim($_POST['telefono']),
            ':ruolo' => $ruolo,
            ':spec' => $spec
        ]);
        header("Location: dashboard.php?view=gestione_staff&msg=staff_ok");
        exit;
    } catch (\PDOException $e) {
        $errore = "Errore durante la registrazione. Forse l'Email o il CF esistono già nel sistema.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rimuovi_staff'])) {
    try {
        $id_da_rimuovere = $_POST['id_utente'];
        $stmt = $pdo->prepare("DELETE FROM utente WHERE ID_Utente = :id");
        $stmt->execute([':id' => $id_da_rimuovere]);
        header("Location: dashboard.php?view=gestione_staff&msg=rimosso_ok");
        exit;
    } catch (\PDOException $e) {
        $errore = "Impossibile rimuovere questo utente. Probabilmente ha dei pazienti o sedute assegnati.";
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'impostazioni_ok') $messaggio = "Impostazioni aggiornate con successo!";
    if ($_GET['msg'] === 'staff_ok') $messaggio = "Nuovo membro dello staff aggiunto!";
    if ($_GET['msg'] === 'rimosso_ok') $messaggio = "Utente rimosso dal sistema.";
}

$orario_apertura = $_SESSION['orario_apertura'] ?? '09:00';
$orario_chiusura = $_SESSION['orario_chiusura'] ?? '19:00';

$view = $_GET['view'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Admin - Centro Psicoterapia</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* --- DESIGN PREMIUM ADMIN --- */
        body { background-color: #F8FAFC; } 
        .dashboard-container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .dashboard-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); padding: 40px; margin-bottom: 30px; border-top: 6px solid #ccc; }
        
        .card-stat { border-top-color: #3B82F6; } 
        .card-impostazioni { border-top-color: #64748B; } 
        .card-staff { border-top-color: #10B981; }

        .section-header { display: flex; align-items: center; gap: 15px; margin-bottom: 5px; }
        .section-header h2 { margin: 0; border: none; font-size: 2rem; color: #1E293B; }
        .section-desc { color: #64748B; font-size: 1.1rem; margin-bottom: 30px; }

        .grid-home { display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; margin-top: 40px; }
        .card-link { background: #FFFFFF; border-radius: 12px; padding: 40px 20px; text-align: center; text-decoration: none; color: #1E293B; width: 320px; border-top: 6px solid #CBD5E1; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .card-link.stat { border-top-color: #3B82F6; }
        .card-link.impostazioni { border-top-color: #64748B; }
        .card-link.staff { border-top-color: #10B981; }
        .card-link:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-link h3 { font-size: 1.5rem; margin-top: 15px; border: none; padding: 0; }
        .card-link p { color: #64748B; font-size: 1rem; margin-top: 10px; }
        .icon-big { font-size: 3.5rem; }

        .grid-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 20px; }
        .stat-box h3 { font-size: 1.2rem; color: #0F172A; margin-top: 0; border-bottom: 2px solid #CBD5E1; padding-bottom: 10px; margin-bottom: 15px; }
        
        .modern-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .modern-table th { background-color: #1E293B; color: #FFFFFF; padding: 12px; text-align: left; }
        .modern-table td { padding: 12px; border-bottom: 1px solid #E2E8F0; color: #334155; vertical-align: middle; }
        .modern-table tr:nth-child(even) td { background-color: #F1F5F9; }
        
        .text-green { color: #10B981; font-weight: bold; }
        .text-blue { color: #3B82F6; font-weight: bold; }

        .filter-bar { 
            background: #FFFFFF; 
            border: 1px solid #E2E8F0; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
            padding: 20px 25px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            flex-wrap: wrap; 
            gap: 20px; 
            border-left: 5px solid #3B82F6; 
        }
        .filter-title { font-weight: 700; color: #1E293B; font-size: 1.15rem; display: flex; align-items: center; gap: 10px; }
        .filter-input-wrap { display: flex; align-items: center; gap: 10px; }
        .filter-input-wrap label { font-weight: 600; color: #475569; font-size: 0.95rem; }
        .filter-input { padding: 10px 15px; border-radius: 8px; border: 1px solid #CBD5E1; font-family: inherit; color: #334155; background-color: #F8FAFC; transition: all 0.2s; }
        .filter-input:focus { outline: none; border-color: #3B82F6; background-color: #FFFFFF; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .filter-actions { display: flex; gap: 10px; }
        .btn-filter { background-color: #3B82F6; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-filter:hover { background-color: #2563EB; transform: translateY(-1px); }
        .btn-reset { background-color: #FFFFFF; color: #64748B; padding: 10px 20px; border-radius: 8px; font-weight: 600; border: 1px solid #CBD5E1; text-decoration: none; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-reset:hover { background-color: #F1F5F9; color: #334155; }
        .status-periodo { background: #EFF6FF; border: 1px dashed #93C5FD; color: #1E40AF; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 30px; font-weight: bold; font-size: 1.1rem; }

        .form-input { width: 100%; padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; margin-bottom: 20px; font-size: 1rem; box-sizing: border-box; }
        .form-row { display: flex; gap: 15px; }
        .form-col { flex: 1; }
        .form-label { font-weight: bold; display: block; margin-bottom: 8px; color: #334155; }

        .btn-action { padding: 12px 20px; font-weight: bold; font-size: 1.05rem; color: white; border: none; border-radius: 6px; cursor: pointer; transition: transform 0.1s; }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-green { background-color: #10B981; width: 100%; }
        .btn-blue { background-color: #3B82F6; }
        .btn-red { background-color: #EF4444; padding: 8px 12px; font-size: 0.9rem; }
        .btn-dark { background-color: #1E293B; width: 100%; }

        .top-bar-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #64748B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .btn-back:hover { background: #475569; }

        .badge-ruolo { padding: 4px 8px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .bg-psi { background-color: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
        .bg-seg { background-color: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    </style>
</head>
<body>
    <header>
        <h1>Pannello di Amministrazione Globale</h1>
        <nav>
            <a href="dashboard.php" style="background: transparent; color: var(--primary-color); border: 1px solid var(--primary-color);">🏠 Home</a>
            <span>Admin: <?= htmlspecialchars($_SESSION['nome']) ?></span>
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
            <h2 style="text-align: center; border: none; font-size: 2.2rem; color: #1E293B; margin-top: 20px;">Gestione Direzione Centro</h2>
            
            <div class="grid-home">
                <a href="dashboard.php?view=statistiche" class="card-link stat">
                    <div class="icon-big">📊</div>
                    <h3>Report Statistici</h3>
                    <p>Visualizza l'andamento del centro e il carico di lavoro dello staff.</p>
                </a>
                <a href="dashboard.php?view=impostazioni" class="card-link impostazioni">
                    <div class="icon-big">⚙️</div>
                    <h3>Impostazioni</h3>
                    <p>Modifica gli orari di apertura e le preferenze globali.</p>
                </a>
                <a href="dashboard.php?view=gestione_staff" class="card-link staff">
                    <div class="icon-big">👥</div>
                    <h3>Gestione Staff</h3>
                    <p>Visualizza, inserisci o disattiva Psicologi e Segreteria.</p>
                </a>
            </div>

        <?php 

        elseif ($view === 'statistiche'): 
            
            $where_date = "";
            $params = [];
            $titolo_periodo = "Tutto il periodo (All-Time)";
            $data_inizio_val = "";
            $data_fine_val = "";

            if (!empty($_GET['data_inizio']) && !empty($_GET['data_fine'])) {
                $data_inizio_val = $_GET['data_inizio'];
                $data_fine_val = $_GET['data_fine'];
                
                $where_date = " AND s.Data BETWEEN :inizio AND :fine ";
                $params = [':inizio' => $data_inizio_val, ':fine' => $data_fine_val];
                $titolo_periodo = "Periodo dal " . date('d/m/Y', strtotime($data_inizio_val)) . " al " . date('d/m/Y', strtotime($data_fine_val));
            }

            $sql_app = "SELECT a.Nome, COUNT(s.ID_Seduta) as Totale 
                        FROM approccio a 
                        LEFT JOIN seduta s ON a.ID_Approccio = s.ID_Approccio $where_date 
                        GROUP BY a.ID_Approccio";
            $stmt_app = $pdo->prepare($sql_app);
            $stmt_app->execute($params);
            $q_approcci = $stmt_app->fetchAll();
            
            $q_utenti = $pdo->query("SELECT Ruolo, COUNT(*) as Totale FROM utente GROUP BY Ruolo")->fetchAll();
            
            $sql_carico = "SELECT u.Nome, u.Cognome, 
                                  COUNT(CASE WHEN s.NoteCartellaClinica IS NOT NULL THEN 1 END) AS SeduteFatte,
                                  COUNT(CASE WHEN s.NoteCartellaClinica IS NULL AND s.Data >= CURDATE() AND s.ID_Segreteria IS NOT NULL THEN 1 END) AS SedutePrenotate
                           FROM utente u
                           LEFT JOIN piano_terapeutico pt ON u.ID_Utente = pt.ID_Psicologo
                           LEFT JOIN seduta s ON pt.ID_percorso = s.ID_percorso $where_date
                           WHERE u.Ruolo = 'Psicologo'
                           GROUP BY u.ID_Utente
                           ORDER BY SeduteFatte DESC";
            $stmt_carico = $pdo->prepare($sql_carico);
            $stmt_carico->execute($params);
            $q_carico = $stmt_carico->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            
            <div class="dashboard-card card-stat">
                <div class="section-header"><span style="font-size: 2.5rem;">📊</span><h2>Cruscotto Statistico</h2></div>
                <p class="section-desc">Panoramica dettagliata sulle performance e l'utilizzo del centro di psicoterapia.</p>
                
                <div class="filter-bar">
                    <div class="filter-title">
                        <span style="font-size: 1.4rem;">🔍</span> Filtra prestazioni per data
                    </div>
                    <form action="dashboard.php" method="GET" style="margin: 0; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                        <input type="hidden" name="view" value="statistiche">
                        
                        <div style="display: flex; gap: 15px;">
                            <div class="filter-input-wrap">
                                <label>Da:</label>
                                <input type="date" name="data_inizio" class="filter-input" value="<?= htmlspecialchars($data_inizio_val) ?>" required>
                            </div>
                            <div class="filter-input-wrap">
                                <label>A:</label>
                                <input type="date" name="data_fine" class="filter-input" value="<?= htmlspecialchars($data_fine_val) ?>" required>
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">✅ Applica Filtro</button>
                            <a href="dashboard.php?view=statistiche" class="btn-reset">🔄 Vedi Totale</a>
                        </div>
                    </form>
                </div>

                <div class="status-periodo">
                    📌 Stai visualizzando: <?= $titolo_periodo ?>
                </div>

                <div class="grid-stats">
                    <div class="stat-box">
                        <h3>📈 Popolarità Approcci</h3>
                        <table class="modern-table">
                            <thead><tr><th>Tipo di Terapia</th><th>Sedute Erogate</th></tr></thead>
                            <tbody>
                                <?php foreach ($q_approcci as $app): ?>
                                    <tr><td><?= htmlspecialchars($app['Nome']) ?></td><td><strong><?= $app['Totale'] ?></strong></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="stat-box" style="grid-column: span 2;">
                        <h3>👨‍⚕️ Carico di Lavoro Psicologi</h3>
                        <table class="modern-table">
                            <thead><tr><th>Dottore</th><th>✅ Sedute Completate</th><th>📅 Prossime Prenotate</th><th>Carico Totale</th></tr></thead>
                            <tbody>
                                <?php foreach ($q_carico as $carico): ?>
                                    <tr>
                                        <td><strong>Dott. <?= htmlspecialchars($carico['Cognome'] . ' ' . $carico['Nome']) ?></strong></td>
                                        <td class="text-green"><?= $carico['SeduteFatte'] ?> sedute</td>
                                        <td class="text-blue"><?= $carico['SedutePrenotate'] ?> prenotate</td>
                                        <td><strong><?= $carico['SeduteFatte'] + $carico['SedutePrenotate'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="stat-box" style="grid-column: span 3;">
                        <h3>👥 Utenti Registrati a Sistema (Storico Totale)</h3>
                        <table class="modern-table" style="width: 50%;">
                            <thead><tr><th>Ruolo nel Sistema</th><th>Totale Account</th></tr></thead>
                            <tbody>
                                <?php foreach ($q_utenti as $ut): ?>
                                    <tr><td><?= htmlspecialchars($ut['Ruolo']) ?></td><td><strong><?= $ut['Totale'] ?></strong></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php 

        elseif ($view === 'impostazioni'): ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            
            <div class="dashboard-card card-impostazioni" style="max-width: 600px; margin: 0 auto;">
                <div class="section-header"><span style="font-size: 2.5rem;">⚙️</span><h2>Impostazioni Generali</h2></div>
                <p class="section-desc">Modifica i parametri di funzionamento del centro.</p>
                
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="salva_impostazioni" value="1">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Orario di Apertura:</label>
                            <input type="time" name="apertura" class="form-input" value="<?= htmlspecialchars($orario_apertura) ?>" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label">Orario di Chiusura:</label>
                            <input type="time" name="chiusura" class="form-input" value="<?= htmlspecialchars($orario_chiusura) ?>" required>
                        </div>
                    </div>
                    
                    <div style="background: #F1F5F9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 0.9rem; color: #64748B;">ℹ️ Questi orari vengono utilizzati come riferimento globale del centro.</p>
                    </div>

                    <button type="submit" class="btn-action btn-dark">Salva Impostazioni</button>
                </form>
            </div>

        <?php 

        elseif ($view === 'gestione_staff'): 
            $staff = $pdo->query("SELECT ID_Utente, Nome, Cognome, Email, Telefono, Ruolo, Specializzazione FROM utente WHERE Ruolo IN ('Psicologo', 'Segreteria') ORDER BY Ruolo, Cognome ASC")->fetchAll();
        ?>
            <div class="top-bar-action"><a href="dashboard.php" class="btn-back">⬅ Torna alla Home</a></div>
            
            <div class="dashboard-card card-staff">
                <div class="section-header"><span style="font-size: 2.5rem;">👥</span><h2>Organico Attuale</h2></div>
                <p class="section-desc">Visualizza e gestisci i dipendenti attualmente attivi nel centro.</p>
                
                <?php if (count($staff) > 0): ?>
                    <table class="modern-table">
                        <thead><tr><th>Ruolo</th><th>Nome e Cognome</th><th>Contatti</th><th>Specializzazione</th><th>Azione</th></tr></thead>
                        <tbody>
                            <?php foreach ($staff as $membro): ?>
                                <tr>
                                    <td>
                                        <span class="badge-ruolo <?= ($membro['Ruolo'] === 'Psicologo') ? 'bg-psi' : 'bg-seg' ?>">
                                            <?= htmlspecialchars($membro['Ruolo']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($membro['Cognome'] . ' ' . $membro['Nome']) ?></strong></td>
                                    <td><small><?= htmlspecialchars($membro['Email']) ?><br><?= htmlspecialchars($membro['Telefono']) ?></small></td>
                                    <td><?= htmlspecialchars($membro['Specializzazione'] ?? '-') ?></td>
                                    <td>
                                        <form action="dashboard.php" method="POST" onsubmit="return confirm('Sei sicuro di voler rimuovere questo utente dal sistema?');">
                                            <input type="hidden" name="id_utente" value="<?= $membro['ID_Utente'] ?>">
                                            <button type="submit" name="rimuovi_staff" class="btn-action btn-red">Rimuovi</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nessun membro dello staff registrato.</p>
                <?php endif; ?>
            </div>

            <div class="dashboard-card" style="border-top-color: #0F172A;">
                <div class="section-header"><span style="font-size: 2.5rem;">➕</span><h2>Aggiungi Nuovo Membro</h2></div>
                <p class="section-desc">Crea un nuovo account per la Segreteria o per uno Psicologo.</p>
                
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="aggiungi_staff" value="1">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Seleziona il Ruolo:</label>
                            <select name="ruolo" class="form-input" id="scelta_ruolo" onchange="toggleSpecializzazione()" required>
                                <option value="Psicologo">Psicologo / Psicoterapeuta</option>
                                <option value="Segreteria">Operatore Segreteria (Front Office)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col"><label class="form-label">Nome:</label><input type="text" name="nome" class="form-input" required></div>
                        <div class="form-col"><label class="form-label">Cognome:</label><input type="text" name="cognome" class="form-input" required></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col"><label class="form-label">Codice Fiscale:</label><input type="text" name="cf" class="form-input" maxlength="16" required></div>
                        <div class="form-col"><label class="form-label">Telefono:</label><input type="text" name="telefono" class="form-input" required></div>
                    </div>
                    
                    <div id="div_specializzazione">
                        <label class="form-label">Specializzazione Terapeutica:</label>
                        <input type="text" name="specializzazione" class="form-input" placeholder="Es. Psicoterapeuta Sistemico-Familiare">
                    </div>
                    
                    <label class="form-label">Email di Lavoro (usata per il Login):</label>
                    <input type="email" name="email" class="form-input" placeholder="nome.cognome@centro.it" required>
                    
                    <label class="form-label">Password provvisoria:</label>
                    <input type="text" name="password" class="form-input" value="staff123" required>
                    
                    <button type="submit" class="btn-action btn-green">Registra nel Sistema</button>
                </form>
            </div>

            <script>
                function toggleSpecializzazione() {
                    var ruolo = document.getElementById("scelta_ruolo").value;
                    var divSpec = document.getElementById("div_specializzazione");
                    if (ruolo === "Segreteria") {
                        divSpec.style.display = "none";
                    } else {
                        divSpec.style.display = "block";
                    }
                }
            </script>

        <?php endif; ?>
    </div>
</body>
</html>