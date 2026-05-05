<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
requireRole(['ta']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overzicht - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css">
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
            <p>Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(getCurrentUserRoleLabel()) ?></span></p>
        </div>
    </div>

    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>

    <div class="header-right">
        <button class="logout-btn" onclick="window.location.href='/index.html'">
            <i class="fas fa-home"></i>
            <span>PortfolioPagina</span>
        </button>

        <button class="logout-btn" onclick="window.location.href='keuringen.php'">
            <i class="fas fa-check-circle"></i>
            <span>Keuringen</span>
        </button>

        <button class="logout-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Uitloggen</span>
        </button>
    </div>
</header>

<main class="main-container">
    <section class="quick-actions-section">
        <h2 class="section-title">Snelle acties</h2>

        <div class="quick-actions">
            <div class="action-card highlighted" onclick="window.location.href='../HTML/werkvergunning_vak1.html'">
                <div class="action-card-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="action-card-title">Nieuwe aanvraag</div>
                <div class="action-card-subtitle">Start werkvergunning aanvraag</div>
            </div>

            <div class="action-card" onclick="window.location.href='mijn_aanvragen.php'">
                <div class="action-card-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="action-card-title">Mijn aanvragen</div>
                <div class="action-card-subtitle">Bekijk uw ingediende aanvragen</div>
            </div>

            <div class="action-card" onclick="window.location.href='keuringen.php'">
                <div class="action-card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="action-card-title">Keuringen</div>
                <div class="action-card-subtitle">Openstaande aanvragen keuren</div>
            </div>

            <div class="action-card" onclick="window.location.href='mijn_keuringen.php'">
                <div class="action-card-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="action-card-title">Mijn keuringen</div>
                <div class="action-card-subtitle">Bekijk gekeurde aanvragen</div>
            </div>

            <div class="action-card" onclick="window.location.href='/index.html'">
                <div class="action-card-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="action-card-title">Portfolio</div>
                <div class="action-card-subtitle">Terug naar portfolio</div>
            </div>

            <div class="action-card" onclick="window.location.href='contact.php'">
                <div class="action-card-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="action-card-title">Contact</div>
                <div class="action-card-subtitle">Neem contact op</div>
            </div>
        </div>
    </section>

    <section class="applications-section">
        <h2 class="section-title">Uw keuringen</h2>

        <div class="applications-container">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="empty-state-text">Bekijk openstaande aanvragen via “Keuringen”.</div>
                <button class="empty-state-button" onclick="window.location.href='keuringen.php'">
                    Keuringen bekijken
                </button>
            </div>
        </div>
    </section>
</main>

<script src="https://kit.fontawesome.com/fec428329f.js" crossorigin="anonymous"></script>
</body>
</html>