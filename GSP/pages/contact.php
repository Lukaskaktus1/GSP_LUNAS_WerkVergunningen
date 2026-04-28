<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $onderwerp = trim((string) ($_POST['contact_onderwerp'] ?? ''));
    $bericht = trim((string) ($_POST['contact_bericht'] ?? ''));

    if ($onderwerp === '' || $bericht === '') {
        setFlashMessage('error', 'Vul onderwerp en bericht in.');
        redirect('contact.php');
    }

    try {
        $pdo = getDbConnection();
        $statement = $pdo->prepare(
            'INSERT INTO contact_bericht (user_id, email, onderwerp, bericht)
             VALUES (:user_id, :email, :onderwerp, :bericht)'
        );
        $statement->execute([
            'user_id' => (int) $_SESSION['user_id'],
            'email' => (string) $_SESSION['email'],
            'onderwerp' => $onderwerp,
            'bericht' => $bericht,
        ]);

        setFlashMessage('success', 'Uw bericht is verzonden.');
        redirect('contact.php');
    } catch (Throwable $exception) {
        error_log('Contact insert failed: ' . $exception->getMessage());
        setFlashMessage('error', 'Het bericht kon niet worden opgeslagen.');
        redirect('contact.php');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/werkvergunning-base.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-left">
            <div class="header-icon">
                <i class="far fa-file-lines"></i>
            </div>
            <div class="header-title">
                <h1>Werkvergunning Portaal</h1>
                <p>Contact</p>
            </div>
        </div>
        <div class="header-center">
            <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
        </div>
        <div class="header-right">
            <button class="logout-btn" onclick="window.location.href='overzicht_<?= e((string) $_SESSION['rol']) ?>.php'">
                <i class="fas fa-arrow-left"></i>
                <span>Terug</span>
            </button>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                <span>Uitloggen</span>
            </button>
        </div>
    </header>

    <main class="main-container">
        <div class="form-card">
            <div class="form-title">
                <span>Contactgegevens</span>
            </div>
            <p>Veiligheidsdienst / TA</p>
            <p>E-mail: veiligheid@example.be</p>
            <p>Tel: 03 000 00 00</p>

            <div class="form-section" style="margin-top:25px;">
                <h2 class="section-title">Contactformulier</h2>

                <?php if ($flash !== null): ?>
                    <p style="margin-bottom:16px;color:<?= $flash['type'] === 'success' ? '#027a48' : '#b42318' ?>;">
                        <?= e($flash['message']) ?>
                    </p>
                <?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="form-group">
                        <label for="contact_onderwerp">Onderwerp</label>
                        <input type="text" id="contact_onderwerp" name="contact_onderwerp" placeholder="Bijvoorbeeld: Vraag over LOTO-procedure" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_bericht">Bericht</label>
                        <textarea id="contact_bericht" name="contact_bericht" rows="5" placeholder="Schrijf hier uw bericht..." required></textarea>
                    </div>
                    <button class="nav-button next" type="submit">
                        Verstuur
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>
