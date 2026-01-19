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
       VAK I t/m V (bestaande logica)
       ========================= */
    // … behouden zoals eerder …
    const vak1Fields = ['vak1_naam','vak1_tel','vak1_afdeling','vak1_werkbeschrijving'];
    vak1Fields.forEach(id => { const el = document.getElementById(id); if(el) sessionStorage.setItem(id, el.value); });
    const exzones = []; document.querySelectorAll('input[name="exzone"]:checked').forEach(cb => exzones.push(cb.value||'Ja'));
    if(exzones.length) sessionStorage.setItem('vak1_exzone', exzones.join(','));
    
    const vak2Fields = ['vak2_naam','vak2_firma','vak2_veiligheidstest','vak2_datumwerken','vak2_medewerkers'];
    vak2Fields.forEach(id => { const el=document.getElementById(id); if(el) sessionStorage.setItem(id, el.value); });
    saveCheckboxGroup('activiteit_koud','vak2_act_koud');
    saveCheckboxGroup('activiteit_warm','vak2_act_warm');
    saveCheckboxGroup('vervoer_machine','vak2_vervoer');
    saveCheckboxGroup('schadelijke_stoffen','vak2_stoffen');
    saveCheckboxGroup('chemicalien','vak2_chemicalien');

    const vak3Aandacht=document.getElementById('vak3_aandachtspunten');
    if(vak3Aandacht) sessionStorage.setItem('vak3_aandachtspunten',vak3Aandacht.value||vak3Aandacht.textContent);
    const vak3Parkeer=document.getElementById('vak3_parkeerplaats');
    if(vak3Parkeer) sessionStorage.setItem('vak3_parkeerplaats',vak3Parkeer.value);

    const vak4Fields=['vak4_naam','vak4_afdeling'];
    vak4Fields.forEach(id=>{const el=document.getElementById(id);if(el) sessionStorage.setItem(id,el.value);});
    const vak4Aandacht=document.getElementById('vak4_aandachtspunten');
    if(vak4Aandacht) sessionStorage.setItem('vak4_aandachtspunten',vak4Aandacht.value||vak4Aandacht.textContent);

    saveCheckboxGroup('vergunningen','vak5_vergunningen');
    saveCheckboxGroup('toelatingen','vak5_toelatingen');
    saveCheckboxGroup('preventie','vak5_preventie');

    /* =========================
       VAK VI – LOTO / BEKRACHTIGING
       ========================= */
    const lotoForm = document.querySelector('.form-card'); // of specifieke ID als beschikbaar
    if(lotoForm) {
        // Alle inputs en selects
        lotoForm.querySelectorAll('input, select, textarea').forEach(el=>{
            if(el.type==='checkbox') {
                sessionStorage.setItem(el.id || el.name, el.checked ? 'Ja' : 'Neen');
            } else {
                sessionStorage.setItem(el.id || el.name, el.value);
            }
        });

        // Handtekeningen (canvas)
        lotoForm.querySelectorAll('canvas').forEach(canvas=>{
            if(canvas.id && canvas.toDataURL){
                sessionStorage.setItem(canvas.id, canvas.toDataURL());
            }
        });

        // Audit trail opslaan
        sessionStorage.setItem('vak6_auditTrail', JSON.stringify(auditTrail || []));
    }

    /* =========================
       VAK VI TABEL
       ========================= */
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
       VAK VII TABEL
       ========================= */
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

    console.log('✅ Alles opgeslagen inclusief LOTO');
}

/* =========================
   Checkbox helper
   ========================= */
function saveCheckboxGroup(name, storageKey){
    const values=[];
    document.querySelectorAll(`input[name="${name}"]:checked`).forEach(cb=>{
        values.push(cb.value || cb.nextElementSibling?.textContent || 'Ja');
    });
    if(values.length) sessionStorage.setItem(storageKey, values.join(','));
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
