-- Database-uitbreiding voor profielgegevens, wachtwoord reset en extra aanvraagdetails.
-- Voer deze statements eenmalig uit op de GSP-database.

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

CREATE TABLE vergunning_medewerker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    voornaam VARCHAR(100) NULL,
    naam VARCHAR(100) NULL,
    telefoon VARCHAR(40) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vergunning_medewerker_vergunning (vergunning_id)
);

CREATE TABLE vergunning_voertuig_attest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    nummerplaat VARCHAR(40) NULL,
    attest_geldig_tot DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vergunning_voertuig_vergunning (vergunning_id)
);
