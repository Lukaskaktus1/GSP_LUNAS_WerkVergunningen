// Het echte werkvergunningnummer wordt bij het indienen uit de database-teller gehaald.
function initWerkvergunningNummer() {
    const nummerInput = document.getElementById('werkvergunning_nummer');
    if (!nummerInput) return;

    nummerInput.value = isAdminTestMode() ? 'TEST - niet opslaan in database' : 'Automatisch bij indienen';
    nummerInput.readOnly = true;
}

function currentUserRole() {
    return document.body ? (document.body.dataset.userRole || '') : '';
}

function isAdminTestMode() {
    return currentUserRole() === 'admin' && sessionStorage.getItem('admin_test_aanvraag') === 'true';
}

function appendTestParam(url) {
    if (!isAdminTestMode()) return url;

    const separator = url.includes('?') ? '&' : '?';
    return url.includes('test=1') ? url : url + separator + 'test=1';
}

// Functie om te navigeren naar volgende pagina na opslaan
function navigateToNext(url) {
    try {
        if (!validateCurrentPage()) {
            return;
        }
        saveCurrentVak();
        window.location.href = appendTestParam(url);
    } catch (error) {
        console.error('Fout bij opslaan:', error);
        window.location.href = appendTestParam(url);
    }
}

function validateCurrentPage() {
    if (isAdminTestMode()) return true;

    const card = document.querySelector('.form-card');
    if (!card) return true;

    const firmaGroup = document.getElementById('firma_naam_group');
    card.querySelectorAll('input, select, textarea').forEach(function (field) {
        if (field.dataset.optional === 'true') return;
        if (field.disabled || field.readOnly || field.type === 'hidden') return;
        if (['button', 'submit', 'checkbox', 'radio'].includes(field.type)) return;
        if (field.closest('[hidden]')) return;

        field.required = true;
    });

    const invalidFields = Array.from(card.querySelectorAll('input, select, textarea')).filter(function (field) {
        if (field.disabled || field.readOnly || field.type === 'hidden') return false;
        if (field.closest('[hidden]')) return false;
        if (firmaGroup && firmaGroup.hidden && field.closest('#firma_naam_group')) return false;

        return typeof field.checkValidity === 'function' && !field.checkValidity();
    });

    card.querySelectorAll('input[type="radio"][required]').forEach(function (radio) {
        if (radio.closest('[hidden]')) return;
        const groupName = radio.name;
        const escapedName = window.CSS && typeof CSS.escape === 'function'
            ? CSS.escape(groupName)
            : groupName.replace(/"/g, '\\"');
        const checked = card.querySelector('input[type="radio"][name="' + escapedName + '"]:checked');
        if (!checked && !invalidFields.includes(radio)) {
            invalidFields.push(radio);
        }
    });

    let message = document.getElementById('page_validation_message');
    if (!message) {
        message = document.createElement('p');
        message.id = 'page_validation_message';
        message.className = 'validation-message';
        const navigation = document.querySelector('.navigation-buttons');
        if (navigation) {
            navigation.parentNode.insertBefore(message, navigation);
        }
    }

    if (invalidFields.length > 0) {
        const problem = 'Vul eerst alle verplichte velden op deze pagina in.';
        message.textContent = problem;
        if (typeof window.showAppPopup === 'function') {
            window.showAppPopup({
                type: 'error',
                title: 'Stap nog niet volledig',
                message: problem,
                solution: 'Controleer het gemarkeerde veld. Bij keuzevragen moet minstens een geldig antwoord geselecteerd zijn.'
            });
        }
        invalidFields[0].focus();
        return false;
    }

    message.textContent = '';
    return true;
}

function saveCurrentVak() {

    /* =========================
       VAK I
       ========================= */
    const vak1Fields = ['vak1_naam','vak1_tel','vak1_afdeling','firma_naam','vak1_werkbeschrijving'];
    vak1Fields.forEach(id => { const el = document.getElementById(id); if(el) sessionStorage.setItem(id, el.value); });
    // Exzone uit vak1 (radio buttons)
    const exzone = document.querySelector('input[name="vak1_exzone"]:checked');
    if(exzone) sessionStorage.setItem('vak1_exzone', exzone.value);
    const schoolAanvraag = document.querySelector('input[name="aanvrager_is_school"]:checked');
    if(schoolAanvraag) sessionStorage.setItem('aanvrager_is_school', schoolAanvraag.value);
    
    /* =========================
       VAK II
       ========================= */
    const vak2Fields = ['vak2_naam','vak2_firma','vak2_datumwerken','werktijd_van','werktijd_tot','vermoedelijke_duur','geldig_tot','werkzaamheden','vak2_medewerkers'];
    vak2Fields.forEach(id => { 
        const el = document.getElementById(id); 
        if(el) sessionStorage.setItem(id, el.value); 
    });
    // vak2_veiligheidstest (radio)
    const veiligheid = document.querySelector('input[name="vak2_veiligheidstest"]:checked');
    if(veiligheid) sessionStorage.setItem('vak2_veiligheidstest', veiligheid.value);
    const vca = document.querySelector('input[name="vca"]:checked');
    if(vca) sessionStorage.setItem('vca', vca.value);
    saveDynamicRows('medewerkers_table', 'medewerkers');
    saveDynamicRows('voertuigen_table', 'voertuigen_attesten');
    
    // Activiteiten lists
    saveCheckedValues('vak2_act_koud', 'input[data-storage-group="vak2_act_koud"]');
    saveCheckedValues('vak2_act_warm', 'input[data-storage-group="vak2_act_warm"]');
    saveCheckedValues('vak2_vervoer', 'input[data-storage-group="vak2_vervoer"]');
    saveCheckedValues('vak2_stoffen', 'input[data-storage-group="vak2_stoffen"]');
    saveCheckedValues('vak2_chemicalien', 'input[data-storage-group="vak2_chemicalien"]');

    /* =========================
       VAK III
       ========================= */
    const vak3Aandacht = document.getElementById('vak3_aandachtspunten');
    if(vak3Aandacht) sessionStorage.setItem('vak3_aandachtspunten', vak3Aandacht.value);
    const vak3Parkeer = document.getElementById('vak3_parkeerplaats');
    if(vak3Parkeer) sessionStorage.setItem('vak3_parkeerplaats', vak3Parkeer.value);

    /* =========================
       VAK IV
       ========================= */
    const vak4Fields = ['vak4_naam','vak4_afdeling','vak4_aandachtspunten'];
    vak4Fields.forEach(id => {
        const el = document.getElementById(id);
        if(el) sessionStorage.setItem(id, el.value);
    });

    /* =========================
       VAK V - Vergunningen, Toelatingen, Preventie
       ========================= */
    saveCheckedValues('vak5_vergunningen', 'input[type="checkbox"][id^="verg_"]');
    saveCheckedValues('vak5_toelatingen', 'input[type="checkbox"][id^="toel_"]');
    saveCheckedValues('vak5_preventie', 'input[data-storage-group="vak5_preventie"]');

    /* =========================
       VAK VI - BEKRACHTIGING
       ========================= */
    const vak6Fields = ['vak6_afdeling','vak6_uitvoerder'];
    vak6Fields.forEach(id => {
        const el = document.getElementById(id);
        if(el) sessionStorage.setItem(id, el.value);
    });

    // VAK VI TABEL
    const vak6Table = document.getElementById('vak6_tabel');
    if(vak6Table){
        const data=[];
        vak6Table.querySelectorAll('tbody tr').forEach(tr=>{
            const row=[];
            tr.querySelectorAll('td').forEach(td=>{
                const input = td.querySelector('input');
                if(input) row.push(input.type==='checkbox'? (input.checked?'Ja':'Neen') : input.value);
                else row.push(td.textContent.trim());
            });
            data.push(row);
        });
        sessionStorage.setItem('vak6_tabel', JSON.stringify(data));
    }

    /* =========================
       VAK VII - AFMELDING
       ========================= */
    // vak7_inspectie (checkbox)
    const inspectie = document.getElementById('inspectie_ja');
    if(inspectie) sessionStorage.setItem('vak7_inspectie', inspectie.checked ? 'ja' : 'neen');
    
    // VAK VII TABEL
    const vak7Table = document.getElementById('vak7_tabel');
    if(vak7Table){
        const data=[];
        vak7Table.querySelectorAll('tbody tr').forEach(tr=>{
            const row=[];
            tr.querySelectorAll('td').forEach(td=>{
                const input = td.querySelector('input');
                if(input) row.push(input.type==='checkbox'? (input.checked?'Ja':'Neen') : input.value);
                else row.push(td.textContent.trim());
            });
            data.push(row);
        });
        sessionStorage.setItem('vak7_tabel', JSON.stringify(data));
    }

    // LOTO behoort niet meer tot de actieve flow.
    sessionStorage.setItem('loto_required', 'false');

    console.log('✅ Alles opgeslagen naar sessionStorage');
}

/* =========================
   Checkbox helpers
   ========================= */
function saveCheckedValues(storageKey, selector) {
    const checkboxes = document.querySelectorAll(selector);

    // Groep staat niet op de huidige pagina: eerder opgeslagen waarde behouden.
    if (checkboxes.length === 0) {
        return;
    }

    const values = Array.from(checkboxes)
        .filter(function (checkbox) {
            return checkbox.checked;
        })
        .map(function (checkbox) {
            return checkbox.value;
        })
        .filter(function (value) {
            return value !== '';
        });

    sessionStorage.setItem(storageKey, JSON.stringify(values));
}

function restoreCheckedValues(storageKey, selector) {
    let savedValues = [];

    try {
        savedValues = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
    } catch (error) {
        savedValues = [];
    }

    if (!Array.isArray(savedValues)) {
        savedValues = [];
    }

    document.querySelectorAll(selector).forEach(function (checkbox) {
        checkbox.checked = savedValues.includes(checkbox.value);
    });
}

function assignStorageGroup(selector, storageGroup) {
    document.querySelectorAll(selector).forEach(function (checkbox) {
        checkbox.setAttribute('data-storage-group', storageGroup);
    });
}

function collectDynamicRows(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return [];

    return Array.from(table.querySelectorAll('[data-row]')).map(function (row) {
        const item = {};
        row.querySelectorAll('[data-field]').forEach(function (field) {
            item[field.dataset.field] = field.value.trim();
        });
        return item;
    }).filter(function (item) {
        return Object.values(item).some(function (value) {
            return value !== '';
        });
    });
}

function saveDynamicRows(tableId, storageKey) {
    const rows = collectDynamicRows(tableId);
    sessionStorage.setItem(storageKey, JSON.stringify(rows));

    if (storageKey === 'medewerkers') {
        const hidden = document.getElementById('vak2_medewerkers');
        if (hidden) {
            hidden.value = rows.map(function (row) {
                return [row.voornaam, row.naam].filter(Boolean).join(' ');
            }).filter(Boolean).join(', ');
            sessionStorage.setItem('vak2_medewerkers', hidden.value);
        }
    }
}

function createDynamicRow(table, values) {
    const template = table.querySelector('[data-row]');
    if (!template) return null;

    const row = template.cloneNode(true);
    row.querySelectorAll('[data-field]').forEach(function (field) {
        field.value = values && values[field.dataset.field] ? values[field.dataset.field] : '';
    });

    return row;
}

function normalizeDynamicTable(tableId, storageKey) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let storedRows = [];
    try {
        storedRows = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
    } catch (error) {
        storedRows = [];
    }

    if (!Array.isArray(storedRows) || storedRows.length === 0) {
        storedRows = [{}];
    }

    const first = table.querySelector('[data-row]');
    if (!first) return;
    table.innerHTML = '';

    storedRows.forEach(function (rowValues) {
        const row = createDynamicRow({ querySelector: function () { return first; } }, rowValues);
        if (row) table.appendChild(row);
    });
}

function attachDynamicRowHandlers() {
    document.querySelectorAll('[data-add-row]').forEach(function (button) {
        button.addEventListener('click', function () {
            const table = document.getElementById(button.dataset.addRow);
            if (!table) return;

            const row = createDynamicRow(table, {});
            if (row) table.appendChild(row);
        });
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-row');
        if (!removeButton) return;

        const table = removeButton.closest('.dynamic-table');
        const rows = table ? table.querySelectorAll('[data-row]') : [];
        if (rows.length <= 1) {
            removeButton.closest('[data-row]').querySelectorAll('[data-field]').forEach(function (field) {
                field.value = '';
            });
            return;
        }

        removeButton.closest('[data-row]').remove();
    });
}

function updateFirmaVisibility() {
    const group = document.getElementById('firma_naam_group');
    const firmaInput = document.getElementById('firma_naam');
    if (!group || !firmaInput) return;

    const selected = document.querySelector('input[name="aanvrager_is_school"]:checked');
    const isExternal = selected && selected.value === 'nee';
    group.hidden = !isExternal;
    firmaInput.required = Boolean(isExternal);
    if (!isExternal) {
        firmaInput.value = '';
    }
}

function attachSchoolToggle() {
    document.querySelectorAll('input[name="aanvrager_is_school"]').forEach(function (radio) {
        radio.addEventListener('change', updateFirmaVisibility);
    });
    updateFirmaVisibility();
}

function attachExclusiveCheckPairs() {
    document.addEventListener('change', function (event) {
        const checkbox = event.target;
        if (!(checkbox instanceof HTMLInputElement) || checkbox.type !== 'checkbox' || !checkbox.checked) {
            return;
        }

        const pairId = checkbox.id.endsWith('_ja')
            ? checkbox.id.replace(/_ja$/, '_neen')
            : checkbox.id.endsWith('_neen')
                ? checkbox.id.replace(/_neen$/, '_ja')
                : checkbox.id === 'role_opdrachtgever'
                    ? 'role_afdeling_iov'
                    : checkbox.id === 'role_afdeling_iov'
                        ? 'role_opdrachtgever'
                        : '';

        if (!pairId) return;

        const pair = document.getElementById(pairId);
        if (pair instanceof HTMLInputElement && pair.type === 'checkbox') {
            pair.checked = false;
        }
    });
}

function initVakProgressBar() {
    const card = document.querySelector('.form-card');
    if (!card || document.querySelector('.vak-progress-bar')) return;

    const pages = [
        ['werkvergunning_vak1.php', 'Vak I'],
        ['werkvergunning_vak2.php', 'Vak II'],
        ['werkvergunning_vak2_activiteiten.php', 'Activiteiten'],
        ['werkvergunning_vak2_chemicalien.php', 'Chemie'],
        ['werkvergunning_vak3.php', 'Vak III'],
        ['werkvergunning_vak4.php', 'Vak IV'],
        ['werkvergunning_vak5.php', 'Vak V'],
        ['werkvergunning_vak6.php', 'Vak VI'],
        ['werkvergunning_vak7.php', 'Indienen']
    ];

    const current = window.location.pathname.split('/').pop();
    ensureAanvraagSession();
    sessionStorage.setItem('visited_' + current, 'true');

    const bar = document.createElement('nav');
    bar.className = 'vak-progress-bar';
    bar.setAttribute('aria-label', "Ingevulde pagina's");

    pages.forEach(function (page) {
        const isCurrent = page[0] === current;
        const isVisited = sessionStorage.getItem('visited_' + page[0]) === 'true';

        if (!isAdminTestMode() && !isCurrent && !isVisited) {
            return;
        }

        const link = document.createElement('a');
        link.href = appendTestParam(page[0]);
        link.textContent = page[1];

        if (isCurrent) {
            link.classList.add('is-current');
        } else if (isVisited) {
            link.classList.add('is-complete');
        }

        link.addEventListener('click', function () {
            saveCurrentVak();
        });

        bar.appendChild(link);
    });

    card.insertAdjacentElement('afterend', bar);
}

function ensureAanvraagSession() {
    const params = new URLSearchParams(window.location.search);
    const startsAdminTest = params.get('test') === '1' && currentUserRole() === 'admin';

    if (params.get('new') === '1') {
        clearAanvraagDraftData();
        sessionStorage.setItem('aanvraag_session_id', String(Date.now()));
        sessionStorage.setItem('admin_test_aanvraag', startsAdminTest ? 'true' : 'false');
        window.history.replaceState({}, document.title, window.location.pathname);
        return;
    }

    if (startsAdminTest) {
        sessionStorage.setItem('admin_test_aanvraag', 'true');
    }

    if (currentUserRole() !== 'admin') {
        sessionStorage.removeItem('admin_test_aanvraag');
    }

    if (!sessionStorage.getItem('aanvraag_session_id')) {
        sessionStorage.setItem('aanvraag_session_id', String(Date.now()));
    }
}

function initAdminTestModeUi() {
    if (!isAdminTestMode()) return;

    document.body.classList.add('admin-test-mode');

    const card = document.querySelector('.form-card');
    if (card && !document.querySelector('.test-mode-banner')) {
        const banner = document.createElement('div');
        banner.className = 'test-mode-banner';
        banner.textContent = 'Admin testaanvraag: alle stappen zijn bereikbaar, verplichte velden zijn uitgeschakeld en er wordt niets naar de database gestuurd.';
        card.prepend(banner);
    }

    document.querySelectorAll('input, select, textarea').forEach(function (field) {
        if (field.type === 'hidden') return;
        field.required = false;
    });
}

function clearAanvraagDraftData() {
    const prefixes = ['vak', 'visited_', 'aanvrager_', 'firma_naam', 'medewerkers', 'voertuigen_attesten', 'werktijd_', 'vermoedelijke_duur', 'geldig_tot', 'werkzaamheden', 'vca', 'admin_test_aanvraag'];
    const keysToRemove = [];

    for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (!key) continue;

        if (prefixes.some(function (prefix) { return key.indexOf(prefix) === 0; })) {
            keysToRemove.push(key);
        }
    }

    keysToRemove.forEach(function (key) {
        sessionStorage.removeItem(key);
    });
}

function initStorageGroupsForPage() {
    // Vak 2 activiteiten en stoffen
    assignStorageGroup('input[type="checkbox"][id^="koud_"]', 'vak2_act_koud');
    assignStorageGroup('input[type="checkbox"][id^="warm_"]', 'vak2_act_warm');
    assignStorageGroup('input[type="checkbox"][id^="vervoer_"]', 'vak2_vervoer');
    assignStorageGroup('input[type="checkbox"][id^="stoffen_"]', 'vak2_stoffen');

    // Vak 2 chemicalien
    assignStorageGroup('input[type="checkbox"][id^="chem_"]', 'vak2_chemicalien');

    // Vak 5 preventie
    assignStorageGroup('.preventie-grid input[type="checkbox"]', 'vak5_preventie');
}

/* =========================
   Form data laden vanuit SessionStorage
   ========================= */
function loadCurrentVakData() {
    const lotoForm = document.querySelector('.form-card');
    if(!lotoForm) return;

    // Input / select / textarea
    lotoForm.querySelectorAll('input, select, textarea').forEach(el=>{
        const key = el.id || el.name;
        if (!key) return;
        if (el.dataset.preserveValue === 'true') return;

        const stored = sessionStorage.getItem(key);
        if (stored !== null) {
            if (stored.startsWith('[') || stored.startsWith('{')) {
                return;
            }

            if (el.type === 'checkbox') {
                el.checked = (stored === 'ja' || stored === 'Ja' || stored === 'true' || stored === '1' || stored === el.value);
            } else if (el.type === 'radio') {
                el.checked = stored === el.value;
            } else {
                el.value = stored;
            }
        }
    });

    // Herstel checkboxgroepen met exacte storage keys.
    restoreCheckedValues('vak2_act_koud', 'input[data-storage-group="vak2_act_koud"]');
    restoreCheckedValues('vak2_act_warm', 'input[data-storage-group="vak2_act_warm"]');
    restoreCheckedValues('vak2_vervoer', 'input[data-storage-group="vak2_vervoer"]');
    restoreCheckedValues('vak2_stoffen', 'input[data-storage-group="vak2_stoffen"]');
    restoreCheckedValues('vak2_chemicalien', 'input[data-storage-group="vak2_chemicalien"]');
    restoreCheckedValues('vak5_vergunningen', 'input[type="checkbox"][id^="verg_"]');
    restoreCheckedValues('vak5_toelatingen', 'input[type="checkbox"][id^="toel_"]');
    restoreCheckedValues('vak5_preventie', 'input[data-storage-group="vak5_preventie"]');
    normalizeDynamicTable('medewerkers_table', 'medewerkers');
    normalizeDynamicTable('voertuigen_table', 'voertuigen_attesten');

    // Canvas handtekeningen
    lotoForm.querySelectorAll('canvas').forEach(canvas=>{
        const data = sessionStorage.getItem(canvas.id);
        if(data){
            const ctx = canvas.getContext('2d');
            const img = new Image();
            img.onload = function(){ ctx.drawImage(img,0,0,canvas.width,canvas.height); }
            img.src = data;
        }
    });

    // Audit Trail herstellen
    const storedAudit = sessionStorage.getItem('vak6_auditTrail');
    if(storedAudit && typeof auditTrail !== 'undefined') {
        auditTrail = JSON.parse(storedAudit);
    }
    if (typeof updateAuditTrailDisplay === 'function') {
        updateAuditTrailDisplay();
    }
}

function attachNavigationAutoSave() {
    document.querySelectorAll('.navigation-buttons .nav-button').forEach(function (button) {
        button.addEventListener('click', function (event) {
            const label = button.textContent.trim().toLowerCase();
    const shouldValidate = label.includes('volgende') || label.includes('indienen');

            if (shouldValidate && !validateCurrentPage()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            if (button.type === 'submit') {
                return;
            }

            saveCurrentVak();
        }, true);
    });
}

// Call deze functie bij page load
document.addEventListener('DOMContentLoaded', function() {
    ensureAanvraagSession();
    initStorageGroupsForPage();
    loadCurrentVakData();
    initWerkvergunningNummer();
    attachDynamicRowHandlers();
    attachSchoolToggle();
    attachExclusiveCheckPairs();
    initVakProgressBar();
    initAdminTestModeUi();
    attachNavigationAutoSave();
});
