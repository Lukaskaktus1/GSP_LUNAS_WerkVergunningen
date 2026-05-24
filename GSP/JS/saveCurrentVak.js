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
    saveCheckboxGroup('activiteit_koud','vak2_act_koud');
    saveCheckboxGroup('activiteit_warm','vak2_act_warm');
    saveCheckboxGroup('vervoer_machine','vak2_vervoer');
    saveCheckboxGroup('schadelijke_stoffen','vak2_stoffen');
    saveCheckboxGroup('chemicalien','vak2_chemicalien');

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
    saveCheckboxGroup('verg_betreding','vak5_vergunningen');
    saveCheckboxGroup('verg_electro','vak5_vergunningen');
    saveCheckboxGroup('verg_graaf','vak5_vergunningen');
    saveCheckboxGroup('verg_hoogte','vak5_vergunningen');
    saveCheckboxGroup('verg_lijnbreking','vak5_vergunningen');
    saveCheckboxGroup('verg_loto','vak5_vergunningen');
    saveCheckboxGroup('verg_stelling','vak5_vergunningen');
    saveCheckboxGroup('verg_tijdelijk','vak5_vergunningen');
    saveCheckboxGroup('verg_vuur','vak5_vergunningen');
    
    saveCheckboxGroup('toel_muur_dak','vak5_toelatingen');
    saveCheckboxGroup('toel_versperren','vak5_toelatingen');
    saveCheckboxGroup('toel_hijsen','vak5_toelatingen');
    saveCheckboxGroup('toel_bluswater','vak5_toelatingen');
    saveCheckboxGroup('toel_werken_bluswater','vak5_toelatingen');
    saveCheckboxGroup('toel_alarm','vak5_toelatingen');
    
    saveCheckboxGroup('preventie','vak5_preventie');

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

    console.log('✅ Alles opgeslagen naar sessionStorage');
}

/* =========================
   Checkbox helper - verzamelt meerdere checkboxes met dezelfde name
   ========================= */
function saveCheckboxGroup(namePattern, storageKey){
    const values=[];
    
    // Als namePattern met wildcards begint, is het een pattern (voor Vak5)
    // Als het exact matched, gebruik exact match
    document.querySelectorAll(`input[type="checkbox"]`).forEach(cb=>{
        if(cb.name === namePattern || cb.name.startsWith(namePattern)) {
            if(cb.checked) {
                values.push(cb.value || cb.nextElementSibling?.textContent?.trim() || 'Ja');
            }
        }
    });
    
    if(values.length) {
        sessionStorage.setItem(storageKey, JSON.stringify(values));
    }
}

/* =========================
   LOTO Data laden vanuit SessionStorage
   ========================= */
function loadLotoData() {
    const lotoForm = document.querySelector('.form-card');
    if(!lotoForm) return;

    // Input / select / textarea
    lotoForm.querySelectorAll('input, select, textarea').forEach(el=>{
        const stored = sessionStorage.getItem(el.id || el.name);
        if(stored!==null){
            if(el.type==='checkbox'){
                el.checked = stored==='Ja';
            } else {
                el.value = stored;
            }
        }
    });

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
    if(storedAudit) auditTrail = JSON.parse(storedAudit);
    updateAuditTrailDisplay(); // bestaande functie
}

// Call deze functie bij page load
document.addEventListener('DOMContentLoaded', function() {
    loadLotoData();
    initWerkvergunningNummer();
});
