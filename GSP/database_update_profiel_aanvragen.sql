-- Database-uitbreiding voor profielgegevens, wachtwoord reset en extra aanvraagdetails.
-- Voer deze statements eenmalig uit op de GSP-database.

CREATE TABLE IF NOT EXISTS user_profiel (
    user_id INT NOT NULL PRIMARY KEY,
    voornaam VARCHAR(100) NOT NULL,
    achternaam VARCHAR(100) NOT NULL,
    telefoon VARCHAR(40) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiel_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS account_aanvraag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    voornaam VARCHAR(100) NOT NULL,
    achternaam VARCHAR(100) NOT NULL,
    telefoon VARCHAR(40) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('nieuw', 'goedgekeurd', 'geweigerd') NOT NULL DEFAULT 'nieuw',
    aangevraagd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    behandeld_door INT NULL,
    behandeld_op DATETIME NULL,
    INDEX idx_account_aanvraag_email (email),
    INDEX idx_account_aanvraag_status (status),
    CONSTRAINT fk_account_aanvraag_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS user_rol_historiek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    oude_rol VARCHAR(50) NULL,
    nieuwe_rol VARCHAR(50) NOT NULL,
    aangepast_door INT NULL,
    aangepast_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_rol_historiek_user (user_id),
    CONSTRAINT fk_user_rol_historiek_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS wachtwoord_reset_token (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    vervalt_op DATETIME NOT NULL,
    gebruikt_op DATETIME NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wachtwoord_reset_token_hash (token_hash),
    INDEX idx_wachtwoord_reset_token_user (user_id),
    CONSTRAINT fk_wachtwoord_reset_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

ALTER TABLE users
    ADD COLUMN voornaam VARCHAR(100) NULL,
    ADD COLUMN naam VARCHAR(100) NULL,
    ADD COLUMN telefoon VARCHAR(40) NULL,
    ADD COLUMN bevestiging_token VARCHAR(128) NULL,
    ADD COLUMN reset_token VARCHAR(128) NULL,
    ADD COLUMN reset_expires_at DATETIME NULL;

ALTER TABLE werkvergunning
    ADD COLUMN aanvrager_voornaam VARCHAR(100) NULL,
    ADD COLUMN aanvrager_naam VARCHAR(100) NULL,
    ADD COLUMN aanvrager_telefoon VARCHAR(40) NULL,
    ADD COLUMN aanvrager_is_school TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN firma_naam VARCHAR(150) NULL;

CREATE TABLE IF NOT EXISTS vergunning_medewerker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    voornaam VARCHAR(100) NULL,
    naam VARCHAR(100) NULL,
    telefoon VARCHAR(40) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vergunning_medewerker_vergunning (vergunning_id)
);

CREATE TABLE IF NOT EXISTS vergunning_voertuig_attest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    nummerplaat VARCHAR(40) NULL,
    attest_geldig_tot DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vergunning_voertuig_vergunning (vergunning_id)
);
