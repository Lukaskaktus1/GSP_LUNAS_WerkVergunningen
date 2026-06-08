-- Klassen/vakken, beoordelaarshandtekening, foto en voertuigtype.
-- Voer eenmalig uit op de GSP-database.

DROP PROCEDURE IF EXISTS gsp_add_column_if_missing;

DELIMITER //
CREATE PROCEDURE gsp_add_column_if_missing(
    IN tableName VARCHAR(64),
    IN columnName VARCHAR(64),
    IN columnDefinition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = tableName
    ) AND NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = tableName
          AND COLUMN_NAME = columnName
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

CALL gsp_add_column_if_missing('user_profiel', 'klas', 'VARCHAR(60) NULL');
CALL gsp_add_column_if_missing('user_profiel', 'vak', 'VARCHAR(80) NULL');

CREATE TABLE IF NOT EXISTS user_klas_vak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    klas VARCHAR(60) NOT NULL,
    vak VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_klas_vak_user (user_id),
    INDEX idx_user_klas_vak_klas (klas)
);

CALL gsp_add_column_if_missing('werkvergunning', 'vak2_klas', 'VARCHAR(60) NULL');
CALL gsp_add_column_if_missing('werkvergunning', 'vak1_foto_data', 'LONGTEXT NULL');

CALL gsp_add_column_if_missing('vergunning_voertuig_attest', 'voertuig_type', 'VARCHAR(80) NULL');

CREATE TABLE IF NOT EXISTS werkvergunning_beoordeling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    beoordelaar_user_id INT NOT NULL,
    beoordelaar_rol VARCHAR(40) NOT NULL,
    actie VARCHAR(40) NOT NULL,
    naam VARCHAR(150) NULL,
    handtekening LONGTEXT NULL,
    opmerking TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_werkvergunning_beoordeling_vergunning (vergunning_id),
    CONSTRAINT fk_werkvergunning_beoordeling_vergunning
        FOREIGN KEY (vergunning_id) REFERENCES werkvergunning(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS aanvraag_actie_verzoek (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    aanvrager_user_id INT NOT NULL,
    actie VARCHAR(40) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aanvraag_actie_verzoek_vergunning (vergunning_id),
    INDEX idx_aanvraag_actie_verzoek_status (status)
);

DROP PROCEDURE IF EXISTS gsp_add_column_if_missing;
