-- Voer eerst deze controle uit.
-- Als hier resultaten uitkomen, los dan eerst dubbele e-mailadressen op
-- voordat je de unieke index op users.email toevoegt.
SELECT email, COUNT(*) AS aantal
FROM users
GROUP BY email
HAVING COUNT(*) > 1;

-- Tabel waarin een leerkracht meerdere klas/vak-koppelingen kan hebben.
-- Er blijft maar 1 account in users; elke extra klas komt als extra rij
-- met dezelfde user_id in deze tabel.
CREATE TABLE IF NOT EXISTS user_klas_vak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    klas VARCHAR(60) NOT NULL,
    vak VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_klas_vak_user (user_id),
    INDEX idx_user_klas_vak_klas (klas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Voorkomt dat dezelfde leerkracht exact dezelfde klas/vak-combinatie dubbel krijgt.
SET @idx_user_klas_vak := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_klas_vak'
      AND INDEX_NAME = 'uniq_user_klas_vak'
);

SET @sql_user_klas_vak := IF(
    @idx_user_klas_vak = 0,
    'ALTER TABLE user_klas_vak ADD UNIQUE KEY uniq_user_klas_vak (user_id, klas, vak)',
    'SELECT ''uniq_user_klas_vak bestaat al'' AS melding'
);

PREPARE stmt_user_klas_vak FROM @sql_user_klas_vak;
EXECUTE stmt_user_klas_vak;
DEALLOCATE PREPARE stmt_user_klas_vak;

-- Voorkomt dat hetzelfde e-mailadres meerdere accounts krijgt.
-- Deze stap lukt alleen als de controle bovenaan geen dubbele e-mails toont.
SET @idx_users_email := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uniq_users_email'
);

SET @sql_users_email := IF(
    @idx_users_email = 0,
    'ALTER TABLE users ADD UNIQUE KEY uniq_users_email (email)',
    'SELECT ''uniq_users_email bestaat al'' AS melding'
);

PREPARE stmt_users_email FROM @sql_users_email;
EXECUTE stmt_users_email;
DEALLOCATE PREPARE stmt_users_email;
