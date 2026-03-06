-- Creazione del database
DROP DATABASE IF EXISTS centro_psicoterapia;
CREATE DATABASE centro_psicoterapia;
USE centro_psicoterapia;

-- --------------------------------------------------------
-- 1. Struttura della tabella FAMIGLIA
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS famiglia (
    Id_Famiglia INT AUTO_INCREMENT PRIMARY KEY,
    NomeFamiglia VARCHAR(100) NOT NULL,
    DataCreazione DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Struttura della tabella UTENTE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS utente (
    ID_Utente INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,
    Cognome VARCHAR(50) NOT NULL,
    CodiceFiscale VARCHAR(16) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Telefono VARCHAR(20) NOT NULL,
    Ruolo ENUM('Admin', 'Segreteria', 'Psicologo', 'Paziente') NOT NULL,
    Specializzazione VARCHAR(100) DEFAULT NULL,
    Id_Famiglia INT DEFAULT NULL,
    FOREIGN KEY (Id_Famiglia) REFERENCES famiglia(Id_Famiglia) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Struttura della tabella APPROCCIO
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS approccio (
    ID_Approccio INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Struttura della tabella PIANO_TERAPEUTICO
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS piano_terapeutico (
    ID_percorso INT AUTO_INCREMENT PRIMARY KEY,
    Obiettivo TEXT NOT NULL,
    DataInizio DATE NOT NULL,
    DataFine DATE DEFAULT NULL,
    ID_Paziente INT NOT NULL,
    ID_Psicologo INT NOT NULL,
    FOREIGN KEY (ID_Paziente) REFERENCES utente(ID_Utente) ON DELETE CASCADE,
    FOREIGN KEY (ID_Psicologo) REFERENCES utente(ID_Utente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Struttura della tabella CARTELLA_CLINICA
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS cartella_clinica (
    Id_Cartella INT AUTO_INCREMENT PRIMARY KEY,
    DataCreazione DATE NOT NULL,
    AnamnesiFamiliare TEXT NOT NULL,
    DiagnosiIniziale TEXT DEFAULT NULL,
    NoteGenerali TEXT DEFAULT NULL,
    ID_percorso INT NOT NULL UNIQUE,
    FOREIGN KEY (ID_percorso) REFERENCES piano_terapeutico(ID_percorso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Struttura della tabella SEDUTA
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS seduta (
    ID_Seduta INT AUTO_INCREMENT PRIMARY KEY,
    Data DATE NOT NULL,
    Ora TIME NOT NULL,
    Durata INT NOT NULL COMMENT 'Durata in minuti',
    NoteCartellaClinica TEXT DEFAULT NULL,
    ID_percorso INT NOT NULL,
    ID_Approccio INT NOT NULL,
    ID_Segreteria INT DEFAULT NULL,
    FOREIGN KEY (ID_percorso) REFERENCES piano_terapeutico(ID_percorso) ON DELETE CASCADE,
    FOREIGN KEY (ID_Approccio) REFERENCES approccio(ID_Approccio) ON DELETE RESTRICT,
    FOREIGN KEY (ID_Segreteria) REFERENCES utente(ID_Utente) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ========================================================
-- INSERIMENTO DATI DI PROVA (ISTANZE)
-- ========================================================

-- Inserimento Famiglie
INSERT INTO famiglia (NomeFamiglia, DataCreazione) VALUES 
('Famiglia Rossi', '2023-01-15'),
('Famiglia Bianchi', '2023-03-22');

-- Inserimento Utenti (Admin, Segreteria, Psicologi, Pazienti)
INSERT INTO utente (Nome, Cognome, CodiceFiscale, Email, Password, Telefono, Ruolo, Specializzazione, Id_Famiglia) VALUES 
('Mario', 'Admin', 'MRDMN00A00A000A', 'admin@centro.it', 'admin123', '3331112222', 'Admin', NULL, NULL),
('Giulia', 'Segretaria', 'GLSGR00A00A000A', 'segreteria@centro.it', 'segreteria123', '3332223333', 'Segreteria', NULL, NULL),
('Laura', 'Dottori', 'LRDTT00A00A000A', 'laura.dottori@centro.it', 'psicologo123', '3334445555', 'Psicologo', 'Psicoterapia Sistemico-Relazionale', NULL),
('Marco', 'Mente', 'MRCMNT00A00A000A', 'marco.mente@centro.it', 'psicologo123', '3336667777', 'Psicologo', 'Psicoterapia Cognitivo-Comportamentale', NULL),
('Paolo', 'Rossi', 'PLORSS00A00A000A', 'paolo.rossi@email.it', 'paziente123', '3338889999', 'Paziente', NULL, 1),
('Anna', 'Rossi', 'NNARSS00A00A000A', 'anna.rossi@email.it', 'paziente123', '3339990000', 'Paziente', NULL, 1),
('Luca', 'Bianchi', 'LCABNC00A00A000A', 'luca.bianchi@email.it', 'paziente123', '3330001111', 'Paziente', NULL, 2);

-- Inserimento Approcci Terapeutici
INSERT INTO approccio (Nome) VALUES 
('Cognitivo-comportamentale'),
('Sistemico-relazionale'),
('Psicodinamico'),
('EMDR');

-- Inserimento Piani Terapeutici
INSERT INTO piano_terapeutico (Obiettivo, DataInizio, DataFine, ID_Paziente, ID_Psicologo) VALUES 
('Risoluzione conflitti familiari', '2023-02-01', NULL, 5, 3),
('Gestione ansia e stress', '2023-04-10', '2023-12-15', 7, 4);

-- Inserimento Cartelle Cliniche (Corretto l'apostrofo)
INSERT INTO cartella_clinica (DataCreazione, AnamnesiFamiliare, DiagnosiIniziale, NoteGenerali, ID_percorso) VALUES 
('2023-02-01', 'Genitori separati, difficoltà di comunicazione genitore-figlio.', 'Dinamiche conflittuali sistemiche.', 'Paziente collaborativo al primo colloquio.', 1),
('2023-04-10', 'Nessun precedente psichiatrico in famiglia.', 'Disturbo d''ansia generalizzato.', 'Si consiglia approccio cognitivo-comportamentale mirato.', 2);

-- Inserimento Sedute
INSERT INTO seduta (Data, Ora, Durata, NoteCartellaClinica, ID_percorso, ID_Approccio, ID_Segreteria) VALUES 
('2023-02-15', '15:00:00', 60, 'Prima seduta conoscitiva. Il paziente ha espresso le sue paure.', 1, 2, 2),
('2023-03-01', '15:00:00', 60, 'Esercizi di comunicazione assertiva svolti con successo.', 1, 2, 2),
('2023-04-17', '10:00:00', 45, 'Inizio tecniche di rilassamento.', 2, 1, 2),
('2024-12-20', '16:00:00', 60, NULL, 1, 2, 2);