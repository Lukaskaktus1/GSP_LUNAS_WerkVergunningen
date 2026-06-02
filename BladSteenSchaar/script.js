/* ============================================
   Blad Schaar Steen - Game logica
   Concepten die de leerlingen hier zien:
   - Variables (let, const)
   - Math.random()
   - Arrays (de drie keuzes)
   - if/else en switch
   - Functions (modulair denken)
   - Event listeners (klikken)
   - DOM manipulatie (textContent, classList, hidden)
   - setTimeout (vertragingen voor animatie)
   ============================================ */

// Motion One animatie library (vanilla JS variant van Framer Motion)
const { animate } = Motion;

// =========================================================
// CONSTANTEN - onveranderlijke waarden bovenaan zetten
// =========================================================
const KEUZES = ["blad", "schaar", "steen"];

// SVG-strings per keuze - wordt in de arena en historie getoond
// Hetzelfde patroon als in de keuze-knoppen, maar groter formaat
const SVG_BLAD = `
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M24 12h38l16 17v59H24z" fill="currentColor" opacity="0.13"/>
        <path d="M24 12h38l16 17v59H24z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
        <path d="M62 13v19h16" fill="currentColor" opacity="0.18" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
        <path d="M35 45h30M35 58h30M35 71h19" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
    </svg>`;

const SVG_SCHAAR = `
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="25" cy="28" r="12" fill="currentColor" opacity="0.12" stroke="currentColor" stroke-width="5"/>
        <circle cx="25" cy="72" r="12" fill="currentColor" opacity="0.12" stroke="currentColor" stroke-width="5"/>
        <path d="M36 34l19 16M36 66l19-16" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
        <path d="M55 50l34-29-9 26zM55 50l34 29-9-26z" fill="currentColor" opacity="0.22" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
        <circle cx="55" cy="50" r="5" fill="white" stroke="currentColor" stroke-width="4"/>
    </svg>`;

const SVG_STEEN = `
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M18 60l12-30 25-15 27 13 8 31-20 24-35 4z" fill="currentColor" opacity="0.16"/>
        <path d="M18 60l12-30 25-15 27 13 8 31-20 24-35 4z" fill="none" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
        <path d="M30 30l25 20 27-22M55 50l-20 37M55 50l15 33M18 60l37-10" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity="0.55"/>
    </svg>`;

const SVG_VRAAG = `
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <text x="50" y="78" text-anchor="middle" font-family="SF Pro Display, sans-serif"
              font-weight="700" font-size="80" fill="currentColor">?</text>
    </svg>`;

const SYMBOLEN = {
    blad: SVG_BLAD,
    schaar: SVG_SCHAAR,
    steen: SVG_STEEN,
};

// Welke keuze verslaat welke? Als WINT_VAN[a] === b, dan wint a van b
const WINT_VAN = {
    blad: "steen",     // blad omwikkelt steen
    schaar: "blad",    // schaar knipt blad
    steen: "schaar",   // steen verplettert schaar
};

const PUNTEN_VOOR_WINST = 3;   // wie eerst 3 punten haalt, wint de game

// =========================================================
// STATE - alle variabelen die tijdens het spel kunnen wijzigen
// =========================================================
let playerScore = 0;
let computerScore = 0;
let rondeNummer = 1;
let gameKlaar = false;          // wordt true zodra iemand 3 punten heeft
let bezigMetRonde = false;      // voorkomt dubbel klikken tijdens animatie

// =========================================================
// DOM-REFERENTIES - bovenaan ophalen voor performance
// =========================================================
const playerScoreEl = document.getElementById("playerScore");
const computerScoreEl = document.getElementById("computerScore");
const roundNumberEl = document.getElementById("roundNumber");
const playerHandEl = document.getElementById("playerHand");
const computerHandEl = document.getElementById("computerHand");
const resultBanner = document.getElementById("resultBanner");
const resultText = document.getElementById("resultText");
const choicesSection = document.getElementById("choices");
const choiceButtons = document.querySelectorAll(".choice-btn");
const endScreen = document.getElementById("endScreen");
const endIcon = document.getElementById("endIcon");
const endTitle = document.getElementById("endTitle");
const endStats = document.getElementById("endStats");
const endEyebrow = document.getElementById("endEyebrow");
const restartBtn = document.getElementById("restartBtn");
const historyList = document.getElementById("historyList");
const historyEmpty = document.getElementById("historyEmpty");
const confettiLayer = document.getElementById("confettiLayer");

// =========================================================
// CORE FUNCTIES
// =========================================================

/**
 * Computer kiest willekeurig uit de drie opties.
 * Math.random() geeft een getal tussen 0 (incl) en 1 (excl).
 * x KEUZES.length geeft 0..2.999, Math.floor maakt er 0/1/2 van.
 */
function computerKeuze() {
    const index = Math.floor(Math.random() * KEUZES.length);
    return KEUZES[index];
}

/**
 * Bepaal de winnaar van een ronde.
 * Geeft "win", "loss" of "tie" terug.
 */
function bepaalWinnaar(speler, computer) {
    if (speler === computer) {
        return "tie";
    }
    if (WINT_VAN[speler] === computer) {
        return "win";
    }
    return "loss";
}

/**
 * Speel Ã©Ã©n ronde: speler heeft op een keuze geklikt.
 */
async function speelRonde(spelerKeuze) {
    if (bezigMetRonde || gameKlaar) return;
    bezigMetRonde = true;

    // Disable knoppen tijdens animatie
    setKnoppenActief(false);

    // Visueel: highlight de gekozen knop
    document.querySelectorAll(".choice-btn").forEach((b) => b.classList.remove("active"));
    document.querySelector(`[data-keuze="${spelerKeuze}"]`).classList.add("active");

    // Reset hand-display: verberg vorige resultaat
    resultBanner.hidden = true;
    document.querySelectorAll(".hand-display").forEach((d) => d.classList.remove("winner"));

    // 3-2-1 countdown shake animatie
    await speelCountdown();

    // Genereer computer keuze
    const cKeuze = computerKeuze();

    // Toon de echte symbolen (innerHTML omdat het SVG-strings zijn)
    playerHandEl.innerHTML = SYMBOLEN[spelerKeuze];
    computerHandEl.innerHTML = SYMBOLEN[cKeuze];
    zetKeuzeClass(playerHandEl, spelerKeuze);
    zetKeuzeClass(computerHandEl, cKeuze);

    // Reveal animatie
    animate(playerHandEl, { scale: [0.6, 1.15, 1] }, { duration: 0.4 });
    animate(computerHandEl, { scale: [0.6, 1.15, 1] }, { duration: 0.4 });

    // Wacht even op visuele duidelijkheid
    await wacht(400);

    // Bepaal winnaar
    const uitslag = bepaalWinnaar(spelerKeuze, cKeuze);

    // Update scores en historie
    if (uitslag === "win") {
        playerScore++;
        document.querySelector(".player-hand").classList.add("winner");
        toonResult("Jij wint deze ronde!", "win");
    } else if (uitslag === "loss") {
        computerScore++;
        document.querySelector(".computer-hand").classList.add("winner");
        toonResult("Computer wint deze ronde", "loss");
    } else {
        toonResult("Gelijkspel - niemand een punt", "tie");
    }

    // Update score-tegels met pulse
    playerScoreEl.textContent = playerScore;
    computerScoreEl.textContent = computerScore;
    if (uitslag === "win") {
        animate(playerScoreEl, { scale: [1, 1.3, 1] }, { duration: 0.5 });
    } else if (uitslag === "loss") {
        animate(computerScoreEl, { scale: [1, 1.3, 1] }, { duration: 0.5 });
    }

    // Bewaar in geschiedenis
    voegToeAanGeschiedenis(rondeNummer, spelerKeuze, cKeuze, uitslag);

    // Check of game klaar is
    if (playerScore >= PUNTEN_VOOR_WINST || computerScore >= PUNTEN_VOOR_WINST) {
        await wacht(800);
        toonEindscherm();
        return;
    }

    // Volgende ronde voorbereiden
    rondeNummer++;
    roundNumberEl.textContent = rondeNummer;

    // Knoppen weer activeren
    bezigMetRonde = false;
    setKnoppenActief(true);
    document.querySelectorAll(".choice-btn").forEach((b) => b.classList.remove("active"));
}

/**
 * Speel een 3-2-1 shake animatie op de placeholder hands.
 */
async function speelCountdown() {
    playerHandEl.innerHTML = SVG_VRAAG;
    computerHandEl.innerHTML = SVG_VRAAG;
    zetKeuzeClass(playerHandEl);
    zetKeuzeClass(computerHandEl);

    // Shake-effect op beide handen tegelijk
    const shakeOptions = { duration: 0.15, easing: "ease-in-out" };
    for (let i = 0; i < 3; i++) {
        await Promise.all([
            animate(playerHandEl, { y: [0, -10, 0] }, shakeOptions).finished,
            animate(computerHandEl, { y: [0, -10, 0] }, shakeOptions).finished,
        ]);
    }
}

/**
 * Toon het result banner met juiste kleur.
 */
function toonResult(tekst, type) {
    resultText.textContent = tekst;
    resultText.className = `result-text ${type}`;
    resultBanner.hidden = false;
    animate(resultBanner, { opacity: [0, 1], transform: ["translateY(10px)", "translateY(0)"] }, { duration: 0.4 });
}

/**
 * Voeg een rij toe aan de geschiedenis.
 */
function voegToeAanGeschiedenis(nr, speler, computer, uitslag) {
    historyEmpty.hidden = true;

    const labels = { win: "Win", loss: "Verlies", tie: "Gelijk" };
    const row = document.createElement("div");
    row.className = `history-row ${uitslag}`;
    // SVG-iconen worden ingesloten - namen tonen we onder als label
    row.innerHTML = `
        <span class="round-num">R${nr}</span>
        <span class="player-pick keuze-${speler}" title="${speler}">${SYMBOLEN[speler]}</span>
        <span class="vs">vs</span>
        <span class="computer-pick keuze-${computer}" title="${computer}">${SYMBOLEN[computer]}</span>
        <span class="result-badge">${labels[uitslag]}</span>
    `;
    historyList.prepend(row);

    // Slide-in animatie
    animate(row, { opacity: [0, 1], transform: ["translateX(-20px)", "translateX(0)"] }, { duration: 0.3 });
}

/**
 * Toon het eindscherm: speler of computer wint de game.
 */
function toonEindscherm() {
    gameKlaar = true;
    setKnoppenActief(false);
    choicesSection.hidden = true;

    const spelerWint = playerScore >= PUNTEN_VOOR_WINST;
    const endIconSymbol = document.getElementById("endIconSymbol");

    if (spelerWint) {
        // Material Icon: trofee voor winst
        endIconSymbol.textContent = "emoji_events";
        endTitle.textContent = "Jij wint de game!";
        endEyebrow.textContent = "Champion";
        toonConfetti();
    } else {
        // Material Icon: robot/computer voor computer-winst
        endIconSymbol.textContent = "smart_toy";
        endTitle.textContent = "Computer wint";
        endEyebrow.textContent = "Game over";
    }

    endStats.textContent = `${playerScore} - ${computerScore}`;
    endScreen.hidden = false;

    animate(
        endScreen,
        {
            opacity: [0, 1],
            transform: ["translateY(40px) scale(0.9)", "translateY(0) scale(1)"],
        },
        { duration: 0.7, easing: [0.16, 1, 0.3, 1] }
    );

    // Scroll naar eindscherm
    setTimeout(() => endScreen.scrollIntoView({ behavior: "smooth", block: "center" }), 300);
}

/**
 * Reset het hele spel.
 */
function resetGame() {
    playerScore = 0;
    computerScore = 0;
    rondeNummer = 1;
    gameKlaar = false;
    bezigMetRonde = false;

    playerScoreEl.textContent = "0";
    computerScoreEl.textContent = "0";
    roundNumberEl.textContent = "1";

    playerHandEl.innerHTML = SVG_VRAAG;
    computerHandEl.innerHTML = SVG_VRAAG;
    zetKeuzeClass(playerHandEl);
    zetKeuzeClass(computerHandEl);

    resultBanner.hidden = true;
    document.querySelectorAll(".hand-display").forEach((d) => d.classList.remove("winner"));
    document.querySelectorAll(".choice-btn").forEach((b) => b.classList.remove("active"));

    // Geschiedenis leegmaken
    historyList.innerHTML = '<p class="history-empty" id="historyEmpty">Nog geen rondes gespeeld</p>';

    endScreen.hidden = true;
    choicesSection.hidden = false;
    setKnoppenActief(true);

    // Scroll naar boven
    window.scrollTo({ top: 0, behavior: "smooth" });
}

/**
 * Activeer of deactiveer alle keuze-knoppen.
 */
function setKnoppenActief(actief) {
    choiceButtons.forEach((btn) => {
        btn.disabled = !actief;
    });
}

function zetKeuzeClass(element, keuze) {
    element.classList.remove("placeholder", "keuze-blad", "keuze-schaar", "keuze-steen");
    element.classList.add(keuze ? `keuze-${keuze}` : "placeholder");
}

/**
 * Helper: wacht X milliseconden via een Promise.
 */
function wacht(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Confetti shower voor de winst.
 */
function toonConfetti() {
    const kleuren = ["#FF9500", "#FF2D55", "#FFCC00", "#34C759", "#5AC8FA"];
    const aantal = 80;

    for (let i = 0; i < aantal; i++) {
        const piece = document.createElement("div");
        piece.className = "confetti-piece";
        piece.style.left = Math.random() * 100 + "vw";
        piece.style.background = kleuren[Math.floor(Math.random() * kleuren.length)];
        piece.style.transform = `rotate(${Math.random() * 360}deg)`;
        confettiLayer.appendChild(piece);

        animate(
            piece,
            {
                transform: [
                    "translateY(0px) rotate(0deg)",
                    `translateY(110vh) rotate(${360 + Math.random() * 720}deg)`,
                ],
                opacity: [1, 1, 0],
            },
            {
                duration: 2.5 + Math.random() * 2,
                easing: "ease-in",
                delay: Math.random() * 0.5,
            }
        ).finished.then(() => piece.remove());
    }
}

// =========================================================
// EVENT LISTENERS
// =========================================================

// Koppel elke keuze-knop aan de speelRonde functie
choiceButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
        const keuze = btn.dataset.keuze;
        speelRonde(keuze);
    });
});

// Reset-knop
restartBtn.addEventListener("click", resetGame);

// Toetsenbord-shortcuts: B = blad, S = schaar, T = sTeen
document.addEventListener("keydown", function (event) {
    if (gameKlaar || bezigMetRonde) return;
    const key = event.key.toLowerCase();
    if (key === "b") speelRonde("blad");
    else if (key === "s") speelRonde("schaar");
    else if (key === "t") speelRonde("steen");
});

// Initialiseer placeholder-style en SVG vraagteken op de hand-icons
playerHandEl.innerHTML = SVG_VRAAG;
computerHandEl.innerHTML = SVG_VRAAG;
zetKeuzeClass(playerHandEl);
zetKeuzeClass(computerHandEl);
