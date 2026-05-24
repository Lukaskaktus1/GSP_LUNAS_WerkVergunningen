// Functie om automatisch een 7-cijferig nummer te genereren en op te slaan
function generateWerkvergunningNummer() {
    let nummer = localStorage.getItem('werkvergunning_last_number');
    if (!nummer) {
        nummer = '0000001';
    } else {
        // Verhoog nummer met 1 en zorg voor 7 cijfers
        nummer = String(parseInt(nummer, 10) + 1).padStart(7, '0');
    }
    localStorage.setItem('werkvergunning_last_number', nummer);
    return nummer;
}

// Functie om werkvergunning nummer te initialiseren of laden
function initWerkvergunningNummer() {
    const nummerInput = document.getElementById('werkvergunning_nummer');
    if (!nummerInput) return;
    
    // Kijk of er al een nummer in sessionStorage staat (voor huidige sessie)
    let huidigNummer = sessionStorage.getItem('werkvergunning_nummer');
    
    // Als er geen nummer is, genereer een nieuw nummer (alleen bij eerste pagina load)
    if (!huidigNummer) {
        huidigNummer = generateWerkvergunningNummer();
        sessionStorage.setItem('werkvergunning_nummer', huidigNummer);
    }
    
    // Vul het input veld
    nummerInput.value = huidigNummer;
    nummerInput.readOnly = true;
}

// Functie om te navigeren naar volgende pagina na opslaan
function navigateToNext(url) {
    try {
        saveCurrentVak();
        window.location.href = url;
    } catch (error) {
        console.error('Fout bij opslaan:', error);
        window.location.href = url;
    }
}

function saveCurrentVak() {

    /* =========================
       VAK I
       ========================= */
    const vak1Fields = ['vak1_naam','vak1_tel','vak1_afdeling','vak1_werkbeschrijving'];
    vak1Fields.forEach(id => { const el = document.getElementById(id); if(el) sessionStorage.setItem(id, el.value); });
    // Exzone uit vak1 (radio buttons)
    const exzone = document.querySelector('input[name="vak1_exzone"]:checked');
    if(exzone) sessionStorage.setItem('vak1_exzone', exzone.value);
    
    /* =========================
       VAK II
       ========================= */
    const vak2Fields = ['vak2_naam','vak2_firma','vak2_datumwerken','vak2_medewerkers'];
    vak2Fields.forEach(id => { 
        const el = document.getElementById(id); 
        if(el) sessionStorage.setItem(id, el.value); 
    });
    // vak2_veiligheidstest (radio)
    const veiligheid = document.querySelector('input[name="vak2_veiligheidstest"]:checked');
    if(veiligheid) sessionStorage.setItem('vak2_veiligheidstest', veiligheid.value);
    
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

    // Sla werkvergunning nummer op als het bestaat
    const nummerInput = document.getElementById('werkvergunning_nummer');
    if (nummerInput && nummerInput.value) {
        sessionStorage.setItem('werkvergunning_nummer', nummerInput.value);
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
        button.addEventListener('click', function () {
            if (button.type === 'submit') {
                return;
            }

            saveCurrentVak();
        });
    });
}

// Call deze functie bij page load
document.addEventListener('DOMContentLoaded', function() {
    initStorageGroupsForPage();
    loadCurrentVakData();
    initWerkvergunningNummer();
    attachNavigationAutoSave();
});
