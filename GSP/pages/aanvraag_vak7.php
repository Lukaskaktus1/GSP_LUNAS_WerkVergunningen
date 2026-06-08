<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

$vergunningId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$vergunningId) {
    setFlashMessage('error', 'Ongeldige aanvraag.');
    redirect('mijn_aanvragen.php');
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM werkvergunning WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $vergunningId]);
    $aanvraag = $stmt->fetch();

    if (!is_array($aanvraag) || !magVergunningVak7($aanvraag, $userId, $pdo)) {
        setFlashMessage('error', 'Vak VII is pas beschikbaar nadat alle Vak VI-dagen volledig zijn ingevuld.');
        redirect('aanvraag_vak6.php?id=' . $vergunningId);
    }

    $flash = getFlashMessage();
} catch (Throwable $exception) {
    error_log('aanvraag_vak7 failed: ' . $exception->getMessage());
    setFlashMessage('error', 'Vak VII kon niet worden geladen.');
    redirect('mijn_aanvragen.php');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vak VII afsluiting - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css?v=20260608">
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css?v=20260608">
    <link rel="stylesheet" href="../CSS/local-icons.css?v=20260608">
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon"><i class="far fa-file-lines"></i></div>
        <div class="header-title">
            <h1>Vak VII afsluiting</h1>
            <p>Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(currentUserDisplayName()) ?></span></p>
        </div>
    </div>
    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>
    <div class="header-right">
        <button class="logout-btn" type="button" onclick="window.location.href='aanvraag_vak6.php?id=<?= (int) $vergunningId ?>'">
            <i class="fas fa-arrow-left"></i><span>Terug</span>
        </button>
    </div>
</header>

<main class="main-container">
    <?= flashDialogMarkup($flash ?? null) ?>

    <section class="applications-section">
        <h2 class="section-title"><?= e((string) ($aanvraag['vergunning_nummer'] ?? '')) ?></h2>
        <div class="applications-container">
            <form method="POST" action="aanvraag_vak7_opslaan.php">
                <input type="hidden" name="vergunning_id" value="<?= (int) $vergunningId ?>">

                <div class="form-group">
                    <label>Werkplek proper achtergelaten?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="werkplek_proper" value="ja" required> Ja</label>
                        <label><input type="radio" name="werkplek_proper" value="nee" required> Nee</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Taak volledig afgerond?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="taak_afgerond" value="ja" required> Ja</label>
                        <label><input type="radio" name="taak_afgerond" value="nee" required> Nee</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="opmerking">Opmerking</label>
                    <textarea id="opmerking" name="opmerking" rows="4"></textarea>
                </div>

                <button class="small-btn open-btn" type="submit">Werkvergunning afsluiten</button>
            </form>
        </div>
    </section>
</main>
<script src="../JS/ui-feedback.js"></script>
<script src="../JS/ja-nee-toggle.js"></script>
</body>
</html>
