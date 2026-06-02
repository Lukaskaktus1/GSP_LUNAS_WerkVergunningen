<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
requireRole(['admin']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testaanvraag bekijken - Werkvergunning Portaal</title>
    <link rel="stylesheet" href="../CSS/overzicht.css">
    <link rel="stylesheet" href="../CSS/aanvraag_bekijken.css">
    <link rel="stylesheet" href="../CSS/local-icons.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="header-icon"><i class="far fa-file-lines"></i></div>
        <div class="header-title">
            <h1>Testaanvraag bekijken</h1>
            <p>Welkom, <span class="role-badge"><i class="fas fa-user"></i> <?= e(currentUserDisplayName()) ?></span></p>
        </div>
    </div>
    <div class="header-center">
        <img src="../IMAGES/logo-beveren.jpg" alt="Beveren Logo" class="header-logo">
    </div>
    <div class="header-right">
        <button class="logout-btn" type="button" onclick="window.location.href='overzicht_admin.php'">
            <i class="fas fa-arrow-left"></i><span>Terug</span>
        </button>
    </div>
</header>

<main class="main-container">
    <section class="applications-section">
        <h2 class="section-title">Testgegevens</h2>
        <div class="applications-container" id="test_detail_container">
            <p>Testaanvraag laden...</p>
        </div>
        <div class="applications-container" style="margin-top:16px;">
            <div class="table-actions">
                <button class="small-btn open-btn" type="button" id="test_edit_btn" hidden>Bewerken</button>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const testId = params.get('id') || '';
    const container = document.getElementById('test_detail_container');
    const editBtn = document.getElementById('test_edit_btn');

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function field(fields, key) {
        return fields && fields[key] ? fields[key] : '';
    }

    function renderRow(label, value) {
        return '<div class="detail-field"><label>' + escapeHtml(label) + '</label><div class="readonly-box">' + escapeHtml(value || 'Niet ingevuld') + '</div></div>';
    }

    let list = [];
    try {
        list = JSON.parse(localStorage.getItem('gsp_admin_test_aanvragen') || '[]');
    } catch (error) {
        list = [];
    }

    const test = list.find(function (item) { return item && item.id === testId; });

    if (!test) {
        container.innerHTML = '<p>Testaanvraag niet gevonden in deze browser.</p>';
        return;
    }

    const fields = (test.data && test.data.fields) || {};
    const lists = (test.data && test.data.lists) || {};

    const rows = [
        renderRow('Testnummer', test.id),
        renderRow('Aanvrager', [field(fields, 'aanvrager_voornaam'), field(fields, 'aanvrager_naam')].filter(Boolean).join(' ') || test.aanvrager),
        renderRow('E-mail', field(fields, 'aanvrager_email') || test.email),
        renderRow('Telefoon', field(fields, 'vak1_tel')),
        renderRow('Wie voert de werken uit?', field(fields, 'vak2_doel') === 'school' ? 'Leerlingen van school' : (field(fields, 'vak2_doel') === 'externe' ? 'Externe firma' : '')),
        renderRow('Uitvoerende organisatie', field(fields, 'vak2_doel') === 'school' ? 'GTI Beveren' : (field(fields, 'vak2_firma') || field(fields, 'firma_naam'))),
        renderRow('Afdeling', field(fields, 'vak1_afdeling')),
        renderRow('Uitvoerder', [field(fields, 'uitvoerder_voornaam'), field(fields, 'uitvoerder_naam')].filter(Boolean).join(' ')),
        renderRow('Werkbeschrijving', field(fields, 'vak1_werkbeschrijving')),
        renderRow('Werkzaamheden', field(fields, 'werkzaamheden')),
        renderRow('Datum werken', field(fields, 'vak2_datumwerken')),
        renderRow('Werktijd', [field(fields, 'werktijd_van'), field(fields, 'werktijd_tot')].filter(Boolean).join(' - ')),
        renderRow('Vermoedelijke duur', field(fields, 'vermoedelijke_duur')),
        renderRow('EX-zone', field(fields, 'vak1_exzone')),
        renderRow('Veiligheidstest', field(fields, 'vak2_veiligheidstest')),
        renderRow('VCA', field(fields, 'vca')),
        renderRow('VCA geldig tot', field(fields, 'geldig_tot')),
        renderRow('Aandachtspunten Vak III', field(fields, 'vak3_aandachtspunten')),
        renderRow('Vak IV aandachtspunten', field(fields, 'afd_geen') === '1' ? 'GEEN' : field(fields, 'vak4_aandachtspunten')),
        renderRow('Afdelingsverantwoordelijke', [field(fields, 'vak4_voornaam'), field(fields, 'vak4_naam')].filter(Boolean).join(' '))
    ];

    container.innerHTML = '<div class="detail-grid">' + rows.join('') + '</div>';

    const listBlocks = [];
    Object.keys(lists).forEach(function (key) {
        let values = lists[key];
        if (typeof values === 'string') {
            try { values = JSON.parse(values); } catch (error) { values = []; }
        }
        if (Array.isArray(values) && values.length > 0) {
            listBlocks.push('<div class="detail-field full"><label>' + escapeHtml(key) + '</label><div class="readonly-box">' + escapeHtml(values.join(', ')) + '</div></div>');
        }
    });

    if (listBlocks.length > 0) {
        container.innerHTML += '<div class="detail-grid" style="margin-top:20px;">' + listBlocks.join('') + '</div>';
    }

    if (editBtn) {
        editBtn.hidden = false;
        editBtn.addEventListener('click', function () {
            window.location.href = '../PHP/werkvergunning_vak1.php?edit_test=' + encodeURIComponent(testId);
        });
    }
})();
</script>
</body>
</html>
