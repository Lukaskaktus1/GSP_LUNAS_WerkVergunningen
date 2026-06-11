-- Extra opslagvelden voor aanvraagdetails die in de wizard worden ingevuld.
-- Voer eenmalig uit op de GSP-database.

DROP PROCEDURE IF EXISTS gsp_add_extra_aanvraag_opslag;

DELIMITER //
CREATE PROCEDURE gsp_add_extra_aanvraag_opslag()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'vak3_parkeerplaats'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN vak3_parkeerplaats TEXT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'preventie_aanvullend'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN preventie_aanvullend TEXT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'handtekening_opdrachtgever'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN handtekening_opdrachtgever LONGTEXT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'datum_opdrachtgever'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN datum_opdrachtgever DATE NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'handtekening_afdeling'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN handtekening_afdeling LONGTEXT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'werkvergunning'
          AND COLUMN_NAME = 'datum_afdeling'
    ) THEN
        ALTER TABLE werkvergunning ADD COLUMN datum_afdeling DATE NULL;
    END IF;
END//
DELIMITER ;

CALL gsp_add_extra_aanvraag_opslag();
DROP PROCEDURE IF EXISTS gsp_add_extra_aanvraag_opslag;
