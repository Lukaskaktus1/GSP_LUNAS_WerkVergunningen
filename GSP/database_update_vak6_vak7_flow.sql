-- Vak VI daglogging en Vak VII afsluiting + status uitbreiding.
-- Voer eenmalig uit op de GSP-database.

CREATE TABLE IF NOT EXISTS werkvergunning_vak6_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vergunning_id INT NOT NULL,
    log_datum DATE NOT NULL,
    dag_label VARCHAR(40) NULL,
    van_tijd VARCHAR(20) NULL,
    tot_tijd VARCHAR(20) NULL,
    afdeling_naam VARCHAR(150) NULL,
    afdeling_paraaf VARCHAR(150) NULL,
    uitvoerder_naam VARCHAR(150) NULL,
    uitvoerder_paraaf VARCHAR(150) NULL,
    aantal_uitvoerders INT NULL,
    overdracht_handtekening VARCHAR(255) NULL,
    is_volledig TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_werkvergunning_vak6_log_datum (vergunning_id, log_datum),
    INDEX idx_werkvergunning_vak6_log_vergunning (vergunning_id),
    CONSTRAINT fk_werkvergunning_vak6_log_vergunning
        FOREIGN KEY (vergunning_id) REFERENCES werkvergunning(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS werkvergunning_vak7_afsluiting (
    vergunning_id INT NOT NULL PRIMARY KEY,
    payload_json LONGTEXT NULL,
    is_volledig TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_werkvergunning_vak7_afsluiting_vergunning
        FOREIGN KEY (vergunning_id) REFERENCES werkvergunning(id)
        ON DELETE CASCADE
);

DROP PROCEDURE IF EXISTS gsp_update_werkvergunning_status_enum;

DELIMITER //
CREATE PROCEDURE gsp_update_werkvergunning_status_enum()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'status'
          AND DATA_TYPE = 'enum'
    ) THEN
        ALTER TABLE werkvergunning
            MODIFY status ENUM(
                'concept',
                'ingediend',
                'in_beoordeling',
                'goedgekeurd',
                'afgekeurd',
                'in_uitvoering',
                'vak_vi_voltooid',
                'afgerond',
                'afgemeld',
                'gesloten'
            ) NOT NULL DEFAULT 'concept';
    END IF;
END//
DELIMITER ;

CALL gsp_update_werkvergunning_status_enum();
DROP PROCEDURE IF EXISTS gsp_update_werkvergunning_status_enum;
