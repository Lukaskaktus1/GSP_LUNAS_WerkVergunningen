// LOTO JavaScript Functionaliteit

// Globale variabelen
let auditTrail = [];
let signatureCanvases = {};

// Initialisatie wanneer de pagina geladen is
document.addEventListener('DOMContentLoaded', function() {
    initializeLOTO();
    initializeSignatures();
    initializeEventListeners();
    loadSavedData();
});

// Initialisatie functies
function initializeLOTO() {
    // Event listeners voor energiebronnen
    const energieCheckboxes = document.querySelectorAll('input[name="energiebronnen"]');
    energieCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateEnergiebronnenDetails);
    });
    
    // Event listener voor aantal sloten
    const aantalSlotenInput = document.getElementById('aantal_sloten');
    if (aantalSlotenInput) {
        aantalSlotenInput.addEventListener('change', updateSlotRegistratie);
    }
    
    // Event listener voor foto upload
    const fotoInput = document.getElementById('loto_foto');
    if (fotoInput) {
        fotoInput.addEventListener('change', handleFotoUpload);
    }
    
    // Auto-fill datum/tijd velden voor handtekeningen
    updateSignatureTimestamps();
}

function initializeSignatures() {
    const signatureIds = ['signature_uitvoerder', 'signature_loto', 'signature_wv'];
    
    signatureIds.forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) {
            setupSignatureCanvas(canvas);
            signatureCanvases[id] = canvas;
        }
    });
}

function initializeEventListeners() {
    // Form validatie bij submit
    const form = document.querySelector('.form-card');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateLOTOForm()) {
                submitLOTO();
            }
        });
    }
    
    // Real-time validatie
    const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('blur', validateField);
    });
}

// Energiebronnen details dynamisch genereren
function updateEnergiebronnenDetails() {
    const selectedEnergies = Array.from(document.querySelectorAll('input[name="energiebronnen"]:checked'))
        .map(cb => cb.value);
    
    const container = document.getElementById('energiebronnen_details');
    if (!container) return;
    
    container.innerHTML = '';
    
    const energieLabels = {
        'elektrisch': 'Elektrisch',
        'mechanisch': 'Mechanisch',
        'hydraulisch': 'Hydraulisch',
        'pneumatisch': 'Pneumatisch',
        'chemisch': 'Chemisch',
        'thermisch': 'Thermisch'
    };
    
    selectedEnergies.forEach(energie => {
        const detailDiv = document.createElement('div');
        detailDiv.className = 'energy-source-detail';
        detailDiv.id = `detail_${energie}`;
        
        detailDiv.innerHTML = `
            <h4>
                <span class="material-symbols-outlined">bolt</span>
                ${energieLabels[energie] || energie}
            </h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Isolatiemethode <span class="required">*</span></label>
                    <input type="text" name="isolatie_${energie}" required placeholder="Bijv. Hoofdschakelaar uit, afsluiters dicht">
                </div>
                <div class="form-group">
                    <label>Vergrendelmethode <span class="required">*</span></label>
                    <input type="text" name="vergrendeling_${energie}" required placeholder="Bijv. Slot op schakelaar, slot op afsluiter">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Restenergie afgelaten <span class="required">*</span></label>
                    <select name="restenergie_${energie}" required>
                        <option value="">Selecteer</option>
                        <option value="ja">Ja</option>
                        <option value="nee">Nee</option>
                    </select>
                </div>
                <div class="form-group" id="restenergie_toelichting_${energie}" style="display: none;">
                    <label>Toelichting restenergie</label>
                    <textarea name="restenergie_toelichting_${energie}" rows="3" placeholder="Beschrijf hoe restenergie is afgelaten..."></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="spanningsloos_${energie}" name="spanningsloos_${energie}" required>
                    <label for="spanningsloos_${energie}">
                        <span class="material-symbols-outlined">check_circle</span>
                        Controle op spanningsloosheid uitgevoerd
                    </label>
                </div>
            </div>
        `;
        
        container.appendChild(detailDiv);
        
        // Event listener voor restenergie dropdown
        const restenergieSelect = detailDiv.querySelector(`select[name="restenergie_${energie}"]`);
        if (restenergieSelect) {
            restenergieSelect.addEventListener('change', function() {
                const toelichtingDiv = document.getElementById(`restenergie_toelichting_${energie}`);
                if (this.value === 'ja' && toelichtingDiv) {
                    toelichtingDiv.style.display = 'block';
                    toelichtingDiv.querySelector('textarea').required = true;
                } else if (toelichtingDiv) {
                    toelichtingDiv.style.display = 'none';
                    toelichtingDiv.querySelector('textarea').required = false;
                }
            });
        }
        
        // Event listener voor validatie
        const inputs = detailDiv.querySelectorAll('input[required], textarea[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
        });
    });
    
    addAuditTrail('Energiebronnen bijgewerkt', `Geselecteerde energiebronnen: ${selectedEnergies.join(', ')}`);
}

// Slot registratie dynamisch genereren
function updateSlotRegistratie() {
    const aantalSloten = parseInt(document.getElementById('aantal_sloten').value) || 0;
    const container = document.getElementById('slot_registratie');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (aantalSloten > 0) {
        for (let i = 1; i <= aantalSloten; i++) {
            const slotDiv = document.createElement('div');
            slotDiv.className = 'slot-item';
            slotDiv.id = `slot_${i}`;
            
            slotDiv.innerHTML = `
                <h4>
                    <span class="material-symbols-outlined">lock</span>
                    Slot ${i}
                </h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Slotnummer <span class="required">*</span></label>
                        <input type="text" name="slotnummer_${i}" required placeholder="Bijv. SL-001">
                    </div>
                    <div class="form-group">
                        <label>Tag-nummer <span class="required">*</span></label>
                        <input type="text" name="tagnummer_${i}" required placeholder="Bijv. TAG-001">
                    </div>
                    <div class="form-group">
                        <label>Naam eigenaar slot <span class="required">*</span></label>
                        <input type="text" name="slot_eigenaar_${i}" required placeholder="Naam van persoon die slot plaatste">
                    </div>
                </div>
            `;
            
            container.appendChild(slotDiv);
            
            // Event listeners voor validatie
            const inputs = slotDiv.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
            });
        }
        
        addAuditTrail('Slot registratie bijgewerkt', `${aantalSloten} slot(en) geregistreerd`);
    }
}

// Uitvoerder toevoegen
function addUitvoerder() {
    const container = document.getElementById('uitvoerders_list');
    if (!container) return;
    
    const personDiv = document.createElement('div');
    personDiv.className = 'person-item';
    
    const index = container.children.length + 1;
    
    personDiv.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label>Naam uitvoerder <span class="required">*</span></label>
                <input type="text" name="uitvoerder_naam[]" required>
            </div>
            <div class="form-group">
                <label>E-mail <span class="required">*</span></label>
                <input type="email" name="uitvoerder_email[]" required>
            </div>
            <div class="form-group">
                <label>Telefoon <span class="required">*</span></label>
                <input type="tel" name="uitvoerder_tel[]" required>
            </div>
        </div>
        <button type="button" class="remove-person-btn" onclick="removeUitvoerder(this)">
            <span class="material-symbols-outlined">delete</span>
            Verwijderen
        </button>
    `;
    
    container.appendChild(personDiv);
    
    addAuditTrail('Uitvoerder toegevoegd', `Uitvoerder ${index} toegevoegd`);
}

function removeUitvoerder(button) {
    const personItem = button.closest('.person-item');
    if (personItem && document.getElementById('uitvoerders_list').children.length > 1) {
        personItem.remove();
        addAuditTrail('Uitvoerder verwijderd', 'Uitvoerder verwijderd uit lijst');
    } else {
        alert('Er moet minimaal één uitvoerder aanwezig zijn.');
    }
}

// Foto upload handler
function handleFotoUpload(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('foto_preview');
    if (!previewContainer) return;
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const fotoDiv = document.createElement('div');
                fotoDiv.className = 'foto-preview-item';
                fotoDiv.innerHTML = `
                    <img src="${e.target.result}" alt="LOTO Foto">
                    <button type="button" class="remove-foto" onclick="removeFoto(this)">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                `;
                previewContainer.appendChild(fotoDiv);
            };
            reader.readAsDataURL(file);
        }
    });
    
    addAuditTrail('Foto\'s geüpload', `${files.length} foto(s) toegevoegd`);
}

function removeFoto(button) {
    const fotoItem = button.closest('.foto-preview-item');
    if (fotoItem) {
        fotoItem.remove();
        addAuditTrail('Foto verwijderd', 'Foto verwijderd uit lijst');
    }
}

// Handtekening functionaliteit
function setupSignatureCanvas(canvas) {
    // Set canvas size based on container
    const container = canvas.parentElement;
    const rect = container.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = 150;
    
    // Scale for high DPI displays
    const dpr = window.devicePixelRatio || 1;
    const rect2 = canvas.getBoundingClientRect();
    canvas.width = rect2.width * dpr;
    canvas.height = 150 * dpr;
    
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.strokeStyle = '#1678fa';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    
    function getMousePos(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        return {
            x: (e.clientX - rect.left) * scaleX / dpr,
            y: (e.clientY - rect.top) * scaleY / dpr
        };
    }
    
    function getTouchPos(e) {
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        return {
            x: (touch.clientX - rect.left) * scaleX / dpr,
            y: (touch.clientY - rect.top) * scaleY / dpr
        };
    }
    
    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        const pos = e.type.includes('touch') ? getTouchPos(e) : getMousePos(e);
        lastX = pos.x;
        lastY = pos.y;
    }
    
    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        
        const pos = e.type.includes('touch') ? getTouchPos(e) : getMousePos(e);
        
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        
        lastX = pos.x;
        lastY = pos.y;
    }
    
    function stopDrawing(e) {
        if (e) {
            e.preventDefault();
        }
        if (isDrawing) {
            isDrawing = false;
            updateSignatureTimestamp(canvas.id);
            addAuditTrail('Handtekening gezet', `Handtekening gezet op ${canvas.id}`);
        }
    }
    
    // Mouse events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Touch events
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing, { passive: false });
    canvas.addEventListener('touchcancel', stopDrawing, { passive: false });
    
    // Prevent scrolling while drawing
    canvas.addEventListener('touchmove', function(e) {
        if (isDrawing) {
            e.preventDefault();
        }
    }, { passive: false });
}

function clearSignature(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        updateSignatureTimestamp(canvasId);
        addAuditTrail('Handtekening gewist', `Handtekening gewist op ${canvasId}`);
    }
}

function updateSignatureTimestamps() {
    const signatureIds = ['signature_uitvoerder', 'signature_loto', 'signature_wv'];
    signatureIds.forEach(id => {
        updateSignatureTimestamp(id);
    });
}

function updateSignatureTimestamp(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    try {
        const ctx = canvas.getContext('2d');
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const isSigned = !imageData.data.every(channel => channel === 0);
        
        if (isSigned) {
            const now = new Date();
            const fieldName = canvasId.replace('signature_', '') + '_datum_handtekening';
            const timestampInput = document.querySelector(`input[name="${fieldName}"]`);
            if (timestampInput) {
                timestampInput.value = now.toISOString().slice(0, 16);
            }
        }
    } catch (e) {
        console.error('Fout bij controleren handtekening:', e);
    }
}

// Validatie
function validateField(event) {
    const field = event.target;
    const formGroup = field.closest('.form-group');
    
    if (formGroup) {
        if (field.validity.valid) {
            formGroup.classList.remove('has-error');
            const errorMsg = formGroup.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        } else {
            formGroup.classList.add('has-error');
            if (!formGroup.querySelector('.error-message')) {
                const errorMsg = document.createElement('div');
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = `
                    <span class="material-symbols-outlined">error</span>
                    Dit veld is verplicht
                `;
                formGroup.appendChild(errorMsg);
            }
        }
    }
}

function validateLOTOForm() {
    // Check eerst of formulier container zichtbaar is
    const formContainer = document.getElementById('loto_form_container');
    if (formContainer && formContainer.style.display === 'none') {
        return true; // Als formulier verborgen is (NVT), is validatie niet nodig
    }
    
    let isValid = true;
    const requiredFields = document.querySelectorAll('#loto_form_container input[required], #loto_form_container textarea[required], #loto_form_container select[required]');
    
    requiredFields.forEach(field => {
        if (!field.validity.valid && !field.disabled) {
            isValid = false;
            const formGroup = field.closest('.form-group');
            if (formGroup) {
                formGroup.classList.add('has-error');
            }
        }
    });
    
    // Check checklist items (alleen als formulier zichtbaar is)
    const checklistItems = document.querySelectorAll('#loto_form_container input[name="checklist"]');
    if (checklistItems.length > 0) {
        const allChecked = Array.from(checklistItems).every(item => item.checked);
        
        if (!allChecked) {
            isValid = false;
            alert('Alle checklist items moeten worden aangevinkt voordat u kunt verzenden.');
        }
    }
    
    // Check handtekeningen (alleen als formulier zichtbaar is)
    const signatureIds = ['signature_uitvoerder', 'signature_loto', 'signature_wv'];
    const signatureLabels = {
        'signature_uitvoerder': 'uitvoerder',
        'signature_loto': 'LOTO-verantwoordelijke',
        'signature_wv': 'werkvergunningverstrekker'
    };
    
    for (const id of signatureIds) {
        const canvas = document.getElementById(id);
        if (canvas && formContainer && formContainer.contains(canvas)) {
            try {
                const ctx = canvas.getContext('2d');
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const isSigned = !imageData.data.every(channel => channel === 0);
                
                if (!isSigned) {
                    isValid = false;
                    alert(`Handtekening voor ${signatureLabels[id] || id.replace('signature_', '')} is verplicht.`);
                    break;
                }
            } catch (e) {
                console.error('Fout bij controleren handtekening:', e);
                isValid = false;
                alert(`Fout bij controleren handtekening voor ${signatureLabels[id] || id.replace('signature_', '')}.`);
                break;
            }
        }
    }
    
    // Check energiebronnen (alleen als formulier zichtbaar is)
    const selectedEnergies = document.querySelectorAll('#loto_form_container input[name="energiebronnen"]:checked');
    if (selectedEnergies.length === 0 && formContainer && formContainer.style.display !== 'none') {
        isValid = false;
        alert('Selecteer minimaal één energiebron.');
    }
    
    return isValid;
}

// Audit Trail
function addAuditTrail(action, details) {
    const timestamp = new Date().toISOString();
    const user = sessionStorage.getItem('userEmail') || 'Onbekend';
    
    auditTrail.push({
        timestamp: timestamp,
        action: action,
        details: details,
        user: user
    });
    
    updateAuditTrailDisplay();
}

function updateAuditTrailDisplay() {
    const container = document.getElementById('audit_trail_content');
    const section = document.getElementById('audit_trail_section');
    
    if (!container || !section) return;
    
    if (auditTrail.length > 0) {
        section.style.display = 'block';
        container.innerHTML = '';
        
        auditTrail.slice().reverse().forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'audit-trail-item';
            
            const date = new Date(item.timestamp);
            const formattedDate = date.toLocaleString('nl-NL', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            itemDiv.innerHTML = `
                <div class="timestamp">${formattedDate}</div>
                <div class="action">${item.action}</div>
                <div class="details">${item.details}</div>
                <div class="user">Door: ${item.user}</div>
            `;
            
            container.appendChild(itemDiv);
        });
    }
}

// Opslaan en verzenden
function saveLOTO() {
    const status = document.querySelector('input[name="loto_status"]:checked')?.value;
    
    // Als NVT, sla alleen status op
    if (status === 'nvt') {
        const lotoData = {
            status: 'nvt',
            werkvergunningnummer: document.getElementById('werkvergunningnummer')?.value || '',
            saved_at: new Date().toISOString()
        };
        localStorage.setItem('loto_data_' + lotoData.werkvergunningnummer, JSON.stringify(lotoData));
        sessionStorage.setItem('loto_status', 'nvt');
        showSuccessMessage('LOTO status opgeslagen als NVT.');
        return;
    }
    
    // Validatie alleen als volledig ingevuld
    if (!validateLOTOForm()) {
        alert('Controleer de formuliervelden voordat u opslaat.');
        return;
    }
    
    const lotoData = collectLOTOData();
    lotoData.status = 'concept';
    
    // Opslaan in localStorage (in productie zou dit naar een server gaan)
    localStorage.setItem('loto_data_' + lotoData.werkvergunningnummer, JSON.stringify(lotoData));
    sessionStorage.setItem('loto_status', 'ingevuld');
    
    addAuditTrail('LOTO opgeslagen', 'LOTO gegevens opgeslagen');
    
    showSuccessMessage('LOTO gegevens succesvol opgeslagen.');
}

function submitLOTO() {
    const status = document.querySelector('input[name="loto_status"]:checked')?.value;
    
    // Als NVT, sla alleen status op
    if (status === 'nvt') {
        const lotoData = {
            status: 'nvt',
            werkvergunningnummer: document.getElementById('werkvergunningnummer')?.value || '',
            submitted_at: new Date().toISOString()
        };
        localStorage.setItem('loto_data_' + lotoData.werkvergunningnummer, JSON.stringify(lotoData));
        sessionStorage.setItem('loto_status', 'nvt');
        
        // Ga naar volgende stap
        navigateFromLOTO();
        return;
    }
    
    // Validatie alleen als volledig ingevuld
    if (!validateLOTOForm()) {
        alert('Controleer de formuliervelden voordat u verdergaat.');
        return;
    }
    
    const lotoData = collectLOTOData();
    lotoData.status = 'ingediend';
    lotoData.submitted_at = new Date().toISOString();
    
    // Opslaan in localStorage (in productie zou dit naar een server gaan)
    localStorage.setItem('loto_data_' + lotoData.werkvergunningnummer, JSON.stringify(lotoData));
    sessionStorage.setItem('loto_status', 'ingevuld');
    
    addAuditTrail('LOTO verzonden', 'LOTO succesvol verzonden en goedgekeurd');
    
    showSuccessMessage('LOTO succesvol verzonden en goedgekeurd.');
    
    // Navigeer naar volgende stap
    navigateFromLOTO();
}

function navigateFromLOTO() {
    // Controleer waar we vandaan komen en ga naar volgende stap
    const referrer = document.referrer;
    if (referrer.includes('werkvergunning_vak5') || referrer.includes('werkvergunning_preventie')) {
        window.location.href = 'werkvergunning_preventie.html';
    } else {
        window.location.href = 'werkvergunning_vak6.html';
    }
}

function collectLOTOData() {
    const formData = new FormData(document.querySelector('.form-card'));
    const data = {};
    
    // Basis gegevens
    data.werkvergunningnummer = document.getElementById('werkvergunningnummer').value;
    data.installatie_naam = document.getElementById('installatie_naam').value;
    data.installatie_id = document.getElementById('installatie_id').value;
    data.locatie = document.getElementById('locatie').value;
    data.loto_datum = document.getElementById('loto_datum').value;
    data.loto_tijd = document.getElementById('loto_tijd').value;
    
    // Energiebronnen
    data.energiebronnen = Array.from(document.querySelectorAll('input[name="energiebronnen"]:checked'))
        .map(cb => cb.value);
    data.energie_overige = document.getElementById('energie_overige').value;
    
    // Energiebronnen details
    data.energiebronnen_details = {};
    data.energiebronnen.forEach(energie => {
        data.energiebronnen_details[energie] = {
            isolatie: document.querySelector(`input[name="isolatie_${energie}"]`)?.value,
            vergrendeling: document.querySelector(`input[name="vergrendeling_${energie}"]`)?.value,
            restenergie: document.querySelector(`select[name="restenergie_${energie}"]`)?.value,
            restenergie_toelichting: document.querySelector(`textarea[name="restenergie_toelichting_${energie}"]`)?.value,
            spanningsloos: document.querySelector(`input[name="spanningsloos_${energie}"]`)?.checked
        };
    });
    
    // Slot registratie
    const aantalSloten = parseInt(document.getElementById('aantal_sloten').value) || 0;
    data.sloten = [];
    for (let i = 1; i <= aantalSloten; i++) {
        data.sloten.push({
            slotnummer: document.querySelector(`input[name="slotnummer_${i}"]`)?.value,
            tagnummer: document.querySelector(`input[name="tagnummer_${i}"]`)?.value,
            eigenaar: document.querySelector(`input[name="slot_eigenaar_${i}"]`)?.value
        });
    }
    
    // Betrokken personen
    data.uitvoerders = [];
    document.querySelectorAll('input[name="uitvoerder_naam[]"]').forEach((input, index) => {
        data.uitvoerders.push({
            naam: input.value,
            email: document.querySelectorAll('input[name="uitvoerder_email[]"]')[index]?.value,
            telefoon: document.querySelectorAll('input[name="uitvoerder_tel[]"]')[index]?.value
        });
    });
    
    data.loto_verantwoordelijke = {
        naam: document.getElementById('loto_verantwoordelijke_naam').value,
        email: document.getElementById('loto_verantwoordelijke_email').value,
        telefoon: document.getElementById('loto_verantwoordelijke_tel').value
    };
    
    data.toezichthouder = {
        naam: document.getElementById('toezichthouder_naam').value,
        email: document.getElementById('toezichthouder_email').value,
        telefoon: document.getElementById('toezichthouder_tel').value
    };
    
    // Checklist
    data.checklist = Array.from(document.querySelectorAll('input[name="checklist"]:checked'))
        .map(cb => cb.value);
    
    // Vrijgave
    data.vrijgave_checklist = Array.from(document.querySelectorAll('input[name="vrijgave_checklist"]:checked'))
        .map(cb => cb.value);
    data.volgorde_verwijderen = document.getElementById('volgorde_verwijderen').value;
    data.opheffen_datum = document.getElementById('opheffen_datum').value;
    data.opheffen_tijd = document.getElementById('opheffen_tijd').value;
    
    // Handtekeningen (als base64)
    data.handtekeningen = {
        uitvoerder: getSignatureData('signature_uitvoerder'),
        loto: getSignatureData('signature_loto'),
        wv: getSignatureData('signature_wv')
    };
    
    data.handtekening_gegevens = {
        uitvoerder_naam: document.querySelector('input[name="uitvoerder_naam_handtekening"]')?.value,
        uitvoerder_datum: document.querySelector('input[name="uitvoerder_datum_handtekening"]')?.value,
        loto_naam: document.querySelector('input[name="loto_naam_handtekening"]')?.value,
        loto_datum: document.querySelector('input[name="loto_datum_handtekening"]')?.value,
        wv_naam: document.querySelector('input[name="wv_naam_handtekening"]')?.value,
        wv_datum: document.querySelector('input[name="wv_datum_handtekening"]')?.value
    };
    
    // Audit trail
    data.audit_trail = auditTrail;
    
    return data;
}

function getSignatureData(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
        return canvas.toDataURL('image/png');
    }
    return null;
}

function loadSavedData() {
    const werkvergunningnummer = document.getElementById('werkvergunningnummer').value;
    if (!werkvergunningnummer) return;
    
    const savedData = localStorage.getItem('loto_data_' + werkvergunningnummer);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            // Herstel formulier gegevens (vereist specifieke implementatie per veld)
            console.log('LOTO data geladen:', data);
        } catch (e) {
            console.error('Fout bij laden van opgeslagen data:', e);
        }
    }
}

function showSuccessMessage(message) {
    const formCard = document.querySelector('.form-card');
    if (formCard) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.innerHTML = `
            <span class="material-symbols-outlined">check_circle</span>
            <span>${message}</span>
        `;
        formCard.insertBefore(successDiv, formCard.firstChild);
        
        setTimeout(() => {
            successDiv.remove();
        }, 5000);
    }
}
