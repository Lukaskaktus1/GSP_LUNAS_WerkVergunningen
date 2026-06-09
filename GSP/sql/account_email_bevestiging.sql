-- E-mailbevestiging voor nieuwe accounts.
-- Nieuwe accounts krijgen users.actief = 0 en worden pas actief na klikken op de mail-link.

CREATE TABLE IF NOT EXISTS account_bevestiging_token (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    vervalt_op DATETIME NOT NULL,
    gebruikt_op DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_account_bevestiging_token_hash (token_hash),
    INDEX idx_account_bevestiging_token_user (user_id),
    CONSTRAINT fk_account_bevestiging_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Controleer eerst dubbele e-mails. Als deze SELECT rijen toont,
-- los die dubbele accounts eerst op voor je de unieke index toevoegt.
SELECT email, COUNT(*) AS aantal
FROM users
GROUP BY email
HAVING COUNT(*) > 1;

-- Unieke e-mailindex, zodat hetzelfde e-mailadres nooit 2 accounts krijgt.
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
