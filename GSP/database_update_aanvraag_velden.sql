-- Extra velden voor aanvraagwizard (namen gesplitst, uitvoerende organisatie, Vak IV).
-- Voer eenmalig uit op de GSP-database.

DROP PROCEDURE IF EXISTS gsp_add_aanvraag_velden;

DELIMITER //
CREATE PROCEDURE gsp_add_aanvraag_velden()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'aanvrager_voornaam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN aanvrager_voornaam VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'aanvrager_naam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN aanvrager_naam VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'aanvrager_telefoon'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN aanvrager_telefoon VARCHAR(40) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'aanvrager_is_school'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN aanvrager_is_school TINYINT(1) NOT NULL DEFAULT 1;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'firma_naam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN firma_naam VARCHAR(150) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'uitvoerder_voornaam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN uitvoerder_voornaam VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'uitvoerder_naam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN uitvoerder_naam VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'vak2_doel'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN vak2_doel ENUM('school', 'externe') NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'vak2_klas'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN vak2_klas VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'vak4_voornaam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN vak4_voornaam VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'vak4_naam'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN vak4_naam VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'vak4_geen_andere_werk'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN vak4_geen_andere_werk TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
END//
DELIMITER ;

CALL gsp_add_aanvraag_velden();
DROP PROCEDURE IF EXISTS gsp_add_aanvraag_velden;
