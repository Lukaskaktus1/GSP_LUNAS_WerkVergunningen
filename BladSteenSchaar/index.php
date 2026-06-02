<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gameKlaar']) === 'true') {
    header('Content-Type: application/json; charset=UTF-8');

    $link = new mysqli('localhost', 'root', '', 'game_database');
    if ($link->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Verbinding mislukt: ' . $link->connect_error]);
        exit;
    }

    $winnaar = isset($_POST['endTitle']) ? $_POST['endTitle'] : 'Onbekend';
    $score = isset($_POST['endStats']) ? intval(preg_replace('/[^0-9]/', '', $_POST['endStats'])) : 0;
    $tijd = isset($_POST['tijd']) ? $_POST['tijd'] : '00:00';

    $query = "INSERT INTO game_database (endTitle, endStats, tijd) VALUES (?, ?, ?)";
    $stmt = $link->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Fout in SQL-voorbereiding: ' . $link->error]);
        $link->close();
        exit;
    }

    $stmt->bind_param('sis', $winnaar, $score, $tijd);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resultaat opgeslagen']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fout bij het toevoegen: ' . $stmt->error]);
    }

    $stmt->close();
    $link->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blad Schaar Steen - Game</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Motion One: vanilla-JS animatie library -->
    <script src="https://cdn.jsdelivr.net/npm/motion@10/dist/motion.min.js"></script>
    <script src="script.js" defer></script>
</head>
<body>

    <!-- ============================================
         HERO HEADER
         ============================================ -->
    <header class="hero">
        <h1>Blad Schaar Steen</h1>
        <p class="subtitle">Best of 5 - wie eerst 3 punten haalt, wint de game</p>
    </header>

    <main class="main-grid">

        <!-- ============================================
             SCOREBOARD
             ============================================ -->
        <section class="scoreboard">
            <div class="score-tile player-tile">
                <div class="score-label">
                    <span class="material-icons">person</span>Jij
                </div>
                <div class="score-value" id="playerScore">0</div>
            </div>
            <div class="score-divider">
                <span class="round-info">Ronde <span id="roundNumber">1</span></span>
                <span class="best-of">van 5</span>
            </div>
            <div class="score-tile computer-tile">
                <div class="score-label">
                    <span class="material-icons">smart_toy</span>Computer
                </div>
                <div class="score-value" id="computerScore">0</div>
            </div>
        </section>

        <!-- ============================================
             ARENA - keuzes worden hier getoond
             ============================================ -->
        <section class="arena" id="arena">
            <div class="hand-display player-hand">
                <div class="hand-label">Jouw keuze</div>
                <div class="hand-icon placeholder" id="playerHand"></div>
            </div>
            <div class="vs-badge" id="vsBadge">
                <span>VS</span>
            </div>
            <div class="hand-display computer-hand">
                <div class="hand-label">Computer</div>
                <div class="hand-icon placeholder" id="computerHand"></div>
            </div>
        </section>

        <!-- ============================================
             RESULT BANNER - verschijnt na elke ronde
             ============================================ -->
        <section class="result-banner" id="resultBanner" hidden>
            <p class="result-text" id="resultText"></p>
        </section>

        <!-- ============================================
             KEUZE-TEGELS - speler klikt hier
             SVG iconen i.p.v. emoji's voor consistente stijl
             ============================================ -->
        <section class="choices" id="choices">
            <p class="choices-title">Maak je keuze:</p>
            <div class="choice-grid">
                <button class="choice-btn" data-keuze="blad">
                    <span class="choice-icon">
                        <!-- Blad: papier met gevouwen hoek -->
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M24 12h38l16 17v59H24z" fill="currentColor" opacity="0.13"/>
                            <path d="M24 12h38l16 17v59H24z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
                            <path d="M62 13v19h16" fill="currentColor" opacity="0.18" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
                            <path d="M35 45h30M35 58h30M35 71h19" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="choice-name">Blad</span>
                    <span class="choice-hint">verslaat steen</span>
                </button>
                <button class="choice-btn" data-keuze="schaar">
                    <span class="choice-icon">
                        <!-- Schaar: duidelijke handgrepen, draaipunt en bladen -->
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="25" cy="28" r="12" fill="currentColor" opacity="0.12" stroke="currentColor" stroke-width="5"/>
                            <circle cx="25" cy="72" r="12" fill="currentColor" opacity="0.12" stroke="currentColor" stroke-width="5"/>
                            <path d="M36 34l19 16M36 66l19-16" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                            <path d="M55 50l34-29-9 26zM55 50l34 29-9-26z" fill="currentColor" opacity="0.22" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
                            <circle cx="55" cy="50" r="5" fill="white" stroke="currentColor" stroke-width="4"/>
                        </svg>
                    </span>
                    <span class="choice-name">Schaar</span>
                    <span class="choice-hint">verslaat blad</span>
                </button>
                <button class="choice-btn" data-keuze="steen">
                    <span class="choice-icon">
                        <!-- Steen: compacte facetvormige rots -->
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M18 60l12-30 25-15 27 13 8 31-20 24-35 4z" fill="currentColor" opacity="0.16"/>
                            <path d="M18 60l12-30 25-15 27 13 8 31-20 24-35 4z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
                            <path d="M30 30l25 20 27-22M55 50l-20 37M55 50l15 33M18 60l37-10" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity="0.55"/>
                        </svg>
                    </span>
                    <span class="choice-name">Steen</span>
                    <span class="choice-hint">verslaat schaar</span>
                </button>
            </div>
        </section>

        <!-- ============================================
             EINDSCHERM - wordt getoond na 3 winsten
             ============================================ -->
        <section class="end-screen" id="endScreen" hidden>
            <div class="end-card">
                <div class="end-shine"></div>
                <span class="end-eyebrow" id="endEyebrow">Game over</span>
                <div class="end-icon" id="endIcon">
                    <!-- Material Icon - wordt vervangen door JS afhankelijk van winnaar -->
                    <span class="material-icons" id="endIconSymbol">emoji_events</span>
                </div>
                <h2 class="end-title" id="endTitle">Jij wint!</h2>
                <p class="end-stats" id="endStats">3 - 0</p>
                <button class="btn-restart" id="restartBtn">
                    <span class="material-icons">refresh</span>Nieuwe game
                </button>
            </div>
        </section>

        <!-- ============================================
             RONDE-HISTORIEK
             ============================================ -->
        <section class="history-section">
            <h3 class="history-title">
                <span class="material-icons">history</span>Geschiedenis
            </h3>
            <div class="history-list" id="historyList">
                <p class="history-empty" id="historyEmpty">Nog geen rondes gespeeld</p>
            </div>
        </section>

        <!-- ============================================
             SPELREGELS
             ============================================ -->
        <section class="rules-section">
            <h3 class="rules-title">
                <span class="material-icons">help_outline</span>Spelregels
            </h3>
            <ul class="rules-list">
                <li><strong>Blad</strong> verslaat <strong>Steen</strong> - papier omwikkelt de steen</li>
                <li><strong>Schaar</strong> verslaat <strong>Blad</strong> - schaar knipt het papier</li>
                <li><strong>Steen</strong> verslaat <strong>Schaar</strong> - steen verplettert de schaar</li>
                <li>Gelijke keuze = gelijkspel, geen punt voor niemand</li>
                <li>Wie als eerste <strong>3 punten</strong> haalt, wint de game</li>
            </ul>
        </section>

    </main>

    <!-- Confetti container -->
    <div class="confetti-layer" id="confettiLayer" aria-hidden="true"></div>

    <footer>
        <p>Blad Schaar Steen &copy; GTI Beveren 2025-2026 - gemaakt met JavaScript</p>
    </footer>
</body>
</html>
