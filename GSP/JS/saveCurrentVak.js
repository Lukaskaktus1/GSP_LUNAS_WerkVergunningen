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

function inlineInputStorageKey(el) {
    if (el && el.type === 'radio' && el.name) {
        return el.name;
    }

    if (!el || (el.id || el.name)) {
        return el ? (el.id || el.name) : '';
    }

    const checkbox = el.closest('.checkbox-item')?.querySelector('input[type="checkbox"][id]');
    if (!checkbox) {
        return '';
    }

    return checkbox.id + '_tekst';
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
    const vak2SchoolGroup = document.getElementById('vak2_school_group') || document.getElementById('vak2_klas_group');
    const vak2FirmaGroup = document.getElementById('vak2_firma_group');
    const vak4Group = document.getElementById('vak4_aandachtspunten_group');
    const invalidFields = Array.from(card.querySelectorAll('input, select, textarea')).filter(function (field) {
        if (!field.required) return false;
        if (field.disabled || field.readOnly || field.type === 'hidden') return false;
        if (field.closest('[hidden]')) return false;
        if (firmaGroup && firmaGroup.hidden && field.closest('#firma_naam_group')) return false;
        if (vak2SchoolGroup && vak2SchoolGroup.hidden && (field.closest('#vak2_school_group') || field.closest('#vak2_klas_group'))) return false;
        if (vak2FirmaGroup && vak2FirmaGroup.hidden && field.closest('#vak2_firma_group')) return false;
        if (vak4Group && (vak4Group.classList.contains('is-disabled-group') || document.getElementById('afd_geen')?.checked) && field.closest('#vak4_aandachtspunten_group')) return false;

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

function saveFieldValue(el) {
    if (!el || el.disabled) return;
    const key = inlineInputStorageKey(el);
    if (!key || el.type === 'file') return;

    if (el.type === 'checkbox') {
        if (el.id === 'afd_geen') {
            sessionStorage.setItem('afd_geen', el.checked ? '1' : '');
            return;
        }
        sessionStorage.setItem(key, el.checked ? (el.value || '1') : '');
        return;
    }

    if (el.type === 'radio') {
        if (el.checked) sessionStorage.setItem(key, el.value);
        return;
    }

    sessionStorage.setItem(key, el.value);
}

function saveAllVisibleFields() {
    const card = document.querySelector('.form-card');
    if (!card) return;

    card.querySelectorAll('input, select, textarea').forEach(function (el) {
        if (el.type === 'radio' && !el.checked) return;
        saveFieldValue(el);
    });
}

function attachInlineInputIds() {
    document.querySelectorAll('.checkbox-item input[type="text"], .checkbox-item input[type="date"]').forEach(function (field) {
        if (field.id || field.name) return;

        const checkbox = field.closest('.checkbox-item')?.querySelector('input[type="checkbox"][id]');
        if (!checkbox) return;

        const key = checkbox.id + '_tekst';
        field.id = key;
        field.name = key;
        field.dataset.optional = 'true';
    });
}

function attachOtherTextAutoCheck() {
    document.querySelectorAll('.checkbox-item').forEach(function (item) {
        const checkbox = item.querySelector('input[type="checkbox"]');
        const inlineFields = item.querySelectorAll('input[type="text"], input[type="date"], textarea');

        if (!checkbox || inlineFields.length === 0) {
            return;
        }

        inlineFields.forEach(function (field) {
            field.addEventListener('input', function () {
                if (field.value.trim() === '') {
                    saveFieldValue(field);
                    return;
                }

                checkbox.checked = true;
                checkbox.disabled = false;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                saveFieldValue(checkbox);
                saveFieldValue(field);
            });

            field.addEventListener('change', function () {
                if (field.value.trim() !== '') {
                    checkbox.checked = true;
                    checkbox.disabled = false;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }

                saveFieldValue(checkbox);
                saveFieldValue(field);
            });
        });
    });
}

function saveCurrentVak() {
    saveAllVisibleFields();

    const vak2Doel = document.querySelector('input[name="vak2_doel"]:checked');
    if (vak2Doel) {
        sessionStorage.setItem('vak2_doel', vak2Doel.value);
        sessionStorage.setItem('aanvrager_is_school', vak2Doel.value === 'school' ? 'ja' : 'nee');
        if (vak2Doel.value === 'school') {
            sessionStorage.setItem('vak2_school_uitvoerder', 'GTI Beveren');
            sessionStorage.setItem('firma_naam', 'GTI Beveren');
            const schoolInput = document.getElementById('vak2_school_uitvoerder');
            if (schoolInput) schoolInput.value = 'GTI Beveren';
        } else {
            const firmaInput = document.getElementById('vak2_firma');
            if (firmaInput && firmaInput.value.trim() !== '') {
                sessionStorage.setItem('firma_naam', firmaInput.value.trim());
            }
        }
    }

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
    const afdGeen = document.getElementById('afd_geen');
    if (afdGeen && afdGeen.checked) {
        sessionStorage.setItem('afd_geen', '1');
        sessionStorage.removeItem('vak4_aandachtspunten');
    }

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
    const template = table.__rowTemplate || table.querySelector('[data-row]');
    if (!template) return null;

    const row = template.cloneNode(true);
    row.hidden = false;
    row.querySelectorAll('[data-field]').forEach(function (field) {
        const value = values && values[field.dataset.field] ? values[field.dataset.field] : '';
        field.value = value;
        if (field instanceof HTMLSelectElement && value && field.value !== value) {
            field.dataset.pendingValue = value;
        }
        field.disabled = false;
    });

    return row;
}

function rememberDynamicTableTemplates() {
    document.querySelectorAll('.dynamic-table').forEach(function (table) {
        if (table.__rowTemplate) return;

        const template = table.querySelector('[data-row]');
        if (template) {
            table.__rowTemplate = template.cloneNode(true);
        }
    });
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

    const template = table.__rowTemplate || table.querySelector('[data-row]');
    if (!template) return;

    if (!Array.isArray(storedRows) || storedRows.length === 0) {
        table.innerHTML = '';
        return;
    }

    table.innerHTML = '';

    storedRows.forEach(function (rowValues) {
        const row = template.cloneNode(true);
        row.hidden = false;
        row.querySelectorAll('[data-field]').forEach(function (field) {
            const value = rowValues && rowValues[field.dataset.field] ? rowValues[field.dataset.field] : '';
            field.value = value;
            if (field instanceof HTMLSelectElement && value && field.value !== value) {
                field.dataset.pendingValue = value;
            }
            field.disabled = false;
        });
        table.appendChild(row);
    });
}

function initVoertuigAttestenVisibility() {
    const section = document.querySelector('.voertuigen-section');
    const table = document.getElementById('voertuigen_table');
    if (!section || !table) return;

    const vehicleCheckboxes = Array.from(document.querySelectorAll('input[type="checkbox"][id^="vervoer_"]:not(#vervoer_geen)'));

    function selectedVehicleOptions() {
        return vehicleCheckboxes.filter(function (checkbox) {
            return checkbox instanceof HTMLInputElement && checkbox.checked && !checkbox.disabled;
        }).map(function (checkbox) {
            const label = document.querySelector('label[for="' + checkbox.id.replace(/"/g, '\\"') + '"]');
            const item = checkbox.closest('.checkbox-item');
            return {
                value: checkbox.value,
                label: label ? label.textContent.trim() : checkbox.id.replace(/^vervoer_/, ''),
                attest: !!(item && item.querySelector('.icon-diamond'))
            };
        });
    }

    function hasVehicle() {
        return vehicleCheckboxes.some(function (checkbox) {
            return checkbox instanceof HTMLInputElement && checkbox.checked && !checkbox.disabled;
        });
    }

    function syncVehicleSelects() {
        const options = selectedVehicleOptions();
        table.querySelectorAll('select[data-field="voertuig_type"]').forEach(function (select) {
            const current = select.value || select.dataset.pendingValue || '';
            select.innerHTML = '<option value="" disabled selected hidden>Kies voertuig</option>';
            options.forEach(function (option) {
                const item = document.createElement('option');
                item.value = option.value;
                item.textContent = option.attest ? option.label + ' (attest vereist)' : option.label;
                item.dataset.attest = option.attest ? 'true' : 'false';
                select.appendChild(item);
            });
            select.value = options.some(function (option) { return option.value === current; }) ? current : '';
            delete select.dataset.pendingValue;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function optionForValue(options, value) {
        return options.find(function (option) {
            return option.value === value;
        }) || null;
    }

    function updateRowAttestState(row, options) {
        const select = row ? row.querySelector('select[data-field="voertuig_type"]') : null;
        const attestField = row ? row.querySelector('[data-field="attest_geldig_tot"]') : null;
        const attestGroup = attestField ? attestField.closest('.form-group') : null;
        const selected = select ? optionForValue(options, select.value) : null;
        const needsAttest = !!(selected && selected.attest);

        if (!attestField || !attestGroup) return;

        attestGroup.hidden = !needsAttest;
        attestGroup.classList.toggle('is-required', needsAttest);
        attestField.required = needsAttest;
        attestField.disabled = !needsAttest;

        if (!needsAttest) {
            attestField.value = '';
        }
    }

    function syncVehicleRows() {
        const options = selectedVehicleOptions();
        const allowedValues = options.map(function (option) { return option.value; });
        const rowsByVehicle = {};

        Array.from(table.querySelectorAll('[data-row]')).forEach(function (row) {
            const select = row.querySelector('select[data-field="voertuig_type"]');
            const type = select ? select.value || select.dataset.pendingValue || '' : '';

            if (type && !allowedValues.includes(type)) {
                row.remove();
                return;
            }

            if (type && allowedValues.includes(type) && !rowsByVehicle[type]) {
                rowsByVehicle[type] = row;
            }
        });

        options.forEach(function (option) {
            if (rowsByVehicle[option.value]) return;

            const emptyRow = Array.from(table.querySelectorAll('[data-row]')).find(function (row) {
                const select = row.querySelector('select[data-field="voertuig_type"]');
                const numberPlate = row.querySelector('[data-field="nummerplaat"]')?.value.trim() || '';
                const certificate = row.querySelector('[data-field="attest_geldig_tot"]')?.value.trim() || '';
                return select && !select.value && !select.dataset.pendingValue && !numberPlate && !certificate;
            });

            const row = emptyRow || createDynamicRow(table, {});
            if (!row) return;

            if (!emptyRow) {
                table.appendChild(row);
            }

            const select = row.querySelector('select[data-field="voertuig_type"]');
            if (select) {
                select.dataset.pendingValue = option.value;
            }
        });
    }

    function updateVisibility() {
        const visible = hasVehicle();
        section.hidden = !visible;

        section.querySelectorAll('input, button').forEach(function (field) {
            field.disabled = !visible;
        });
        section.querySelectorAll('select').forEach(function (field) {
            field.disabled = !visible;
        });

        if (!visible) {
            table.innerHTML = '';
            sessionStorage.removeItem('voertuigen_attesten');
        } else {
            if (table.querySelectorAll('[data-row]').length === 0) {
                const row = createDynamicRow(table, {});
                if (row) table.appendChild(row);
            }
            syncVehicleRows();
            syncVehicleSelects();
            table.querySelectorAll('[data-row]').forEach(function (row) {
                updateRowAttestState(row, selectedVehicleOptions());
            });
        }
    }

    vehicleCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateVisibility);
    });

    const geenCheckbox = document.getElementById('vervoer_geen');
    if (geenCheckbox) {
        geenCheckbox.addEventListener('change', updateVisibility);
    }

    updateVisibility();
}

function initVehicleRowAttestToggle() {
    document.addEventListener('change', function (event) {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || select.dataset.field !== 'voertuig_type') {
            return;
        }

        const selected = select.options[select.selectedIndex];
        const row = select.closest('[data-row]');
        const attestField = row ? row.querySelector('[data-field="attest_geldig_tot"]') : null;
        const attestGroup = attestField ? attestField.closest('.form-group') : null;
        const needsAttest = selected && selected.dataset.attest === 'true';

        if (attestField && attestGroup) {
            attestGroup.hidden = !needsAttest;
            attestField.required = needsAttest;
            attestField.disabled = !needsAttest;
            attestGroup.classList.toggle('is-required', needsAttest);

            if (!needsAttest) {
                attestField.value = '';
            }
        }
    });
}

function initVak1PhotoInput() {
    const input = document.getElementById('vak1_foto');
    const hidden = document.getElementById('vak1_foto_data');
    const preview = document.getElementById('vak1_foto_preview');

    if (!input || !hidden) return;

    const stored = sessionStorage.getItem('vak1_foto_data');
    if (stored) {
        hidden.value = stored;
        if (preview) {
            preview.src = stored;
            preview.hidden = false;
        }
    }

    input.addEventListener('change', function () {
        const file = input.files && input.files[0] ? input.files[0] : null;
        if (!file) {
            hidden.value = '';
            sessionStorage.removeItem('vak1_foto_data');
            if (preview) preview.hidden = true;
            return;
        }

        if (!file.type.startsWith('image/')) {
            input.value = '';
            if (typeof window.showAppPopup === 'function') {
                window.showAppPopup({
                    type: 'error',
                    title: 'Geen geldige foto',
                    message: 'Kies een afbeeldingsbestand.',
                    solution: 'Gebruik bijvoorbeeld een JPG, PNG of foto van uw toestel.'
                });
            }
            return;
        }

        if (file.size > 1800 * 1024) {
            input.value = '';
            if (typeof window.showAppPopup === 'function') {
                window.showAppPopup({
                    type: 'error',
                    title: 'Foto te groot',
                    message: 'Kies een foto kleiner dan 1,8 MB.',
                    solution: 'Maak eventueel een nieuwe foto op lagere resolutie of gebruik een kleinere uitsnede.'
                });
            }
            return;
        }

        const reader = new FileReader();
        reader.onload = function () {
            const value = String(reader.result || '');
            hidden.value = value;
            sessionStorage.setItem('vak1_foto_data', value);
            if (preview) {
                preview.src = value;
                preview.hidden = false;
            }
        };
        reader.readAsDataURL(file);
    });
}

function attachDynamicRowHandlers() {
    document.querySelectorAll('[data-add-row]').forEach(function (button) {
        button.addEventListener('click', function () {
            const table = document.getElementById(button.dataset.addRow);
            if (!table) return;

            const row = createDynamicRow(table, {});
            if (row) {
                table.appendChild(row);
                if (button.dataset.addRow === 'voertuigen_table') {
                    initVoertuigAttestenVisibility();
                }
            }
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

function attachSchoolToggle() {
    const schoolRadio = document.getElementById('vak2_doel_school');
    const externeRadio = document.getElementById('vak2_doel_externe');
    const schoolGroup = document.getElementById('vak2_school_group');
    const klasGroup = document.getElementById('vak2_klas_group');
    const firmaGroup = document.getElementById('vak2_firma_group');
    const klasInput = document.getElementById('vak2_klas');
    const firmaInput = document.getElementById('vak2_firma');

    if (!schoolRadio || !externeRadio) {
        return;
    }

    function update() {
        const school = schoolRadio.checked;
        if (schoolGroup) schoolGroup.hidden = !school;
        if (klasGroup) klasGroup.hidden = true;
        if (firmaGroup) firmaGroup.hidden = school;

        if (klasInput) {
            klasInput.required = false;
            klasInput.disabled = true;
        }
        if (firmaInput) {
            firmaInput.required = !school && externeRadio.checked;
            firmaInput.disabled = school;
        }
    }

    schoolRadio.addEventListener('change', update);
    externeRadio.addEventListener('change', update);
    update();
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
        ['werkvergunning_preventie.php', 'Preventie']
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

    const editTestId = params.get('edit_test');

    if (params.get('new') === '1') {
        clearAanvraagDraftData();
        sessionStorage.setItem('aanvraag_session_id', String(Date.now()));
        sessionStorage.setItem('admin_test_aanvraag', startsAdminTest ? 'true' : 'false');
        sessionStorage.removeItem('admin_test_edit_id');
        sessionStorage.removeItem('aanvraag_bewerk_id');
        window.history.replaceState({}, document.title, window.location.pathname);
        return;
    }

    if (editTestId && restoreTestDraftFromLocalStorage(editTestId)) {
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
    const prefixes = ['vak', 'visited_', 'aanvrager_', 'uitvoerder_', 'firma', 'medewerkers', 'voertuigen_attesten', 'werktijd_', 'vermoedelijke_duur', 'geldig_tot', 'werkzaamheden', 'vca', 'afd_', 'admin_test'];
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
    sessionStorage.removeItem('aanvraag_last_submit_payload');
    sessionStorage.removeItem('aanvraag_bewerk_id');
}

const gspClearAanvraagDraftData = clearAanvraagDraftData;

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
function seedProfileDefaults() {
    if (sessionStorage.getItem('gsp_profile_seeded') === 'true') {
        return;
    }

    const body = document.body;
    if (!body) {
        return;
    }

    const voornaam = body.dataset.profileVoornaam || '';
    const achternaam = body.dataset.profileAchternaam || '';
    const telefoon = body.dataset.profileTel || '';
    const email = body.dataset.profileEmail || '';

    if (voornaam && !sessionStorage.getItem('aanvrager_voornaam')) {
        sessionStorage.setItem('aanvrager_voornaam', voornaam);
    }
    if (achternaam && !sessionStorage.getItem('aanvrager_naam')) {
        sessionStorage.setItem('aanvrager_naam', achternaam);
    }
    if (voornaam && !sessionStorage.getItem('uitvoerder_voornaam')) {
        sessionStorage.setItem('uitvoerder_voornaam', voornaam);
    }
    if (achternaam && !sessionStorage.getItem('uitvoerder_naam')) {
        sessionStorage.setItem('uitvoerder_naam', achternaam);
    }
    if (telefoon && !sessionStorage.getItem('vak1_tel')) {
        sessionStorage.setItem('vak1_tel', telefoon);
    }
    if (email && !sessionStorage.getItem('aanvrager_email')) {
        sessionStorage.setItem('aanvrager_email', email);
    }

    sessionStorage.setItem('gsp_profile_seeded', 'true');
}

function restoreTestDraftFromLocalStorage(testId) {
    let list = [];
    try {
        list = JSON.parse(localStorage.getItem('gsp_admin_test_aanvragen') || '[]');
    } catch (error) {
        list = [];
    }

    const test = list.find(function (item) {
        return item && item.id === testId;
    });

    if (!test || !test.data) {
        return false;
    }

    clearAanvraagDraftData();
    sessionStorage.setItem('admin_test_aanvraag', 'true');
    sessionStorage.setItem('admin_test_edit_id', testId);
    sessionStorage.setItem('aanvraag_session_id', String(Date.now()));

    const data = test.data;
    if (data.fields) {
        Object.keys(data.fields).forEach(function (key) {
            sessionStorage.setItem(key, String(data.fields[key] ?? ''));
        });
    }

    if (data.lists) {
        Object.keys(data.lists).forEach(function (key) {
            const value = data.lists[key];
            sessionStorage.setItem(key, typeof value === 'string' ? value : JSON.stringify(value));
        });
    }

    return true;
}

function saveAdminTestAanvraag(data) {
    const storageKey = 'gsp_admin_test_aanvragen';
    let list = [];

    try {
        list = JSON.parse(localStorage.getItem(storageKey) || '[]');
    } catch (error) {
        list = [];
    }

    if (!Array.isArray(list)) {
        list = [];
    }

    const editId = sessionStorage.getItem('admin_test_edit_id');
    const body = document.body;
    const entry = {
        id: editId || ('TEST-' + Date.now()),
        createdAt: editId ? (list.find(function (item) { return item.id === editId; }) || {}).createdAt || new Date().toISOString() : new Date().toISOString(),
        updatedAt: new Date().toISOString(),
        aanvrager: [data.fields?.aanvrager_voornaam, data.fields?.aanvrager_naam].filter(Boolean).join(' ') || (body?.dataset?.profileVoornaam + ' ' + body?.dataset?.profileAchternaam).trim(),
        email: data.fields?.aanvrager_email || body?.dataset?.profileEmail || '',
        data: data
    };

    if (editId) {
        list = list.map(function (item) {
            return item.id === editId ? entry : item;
        });
    } else {
        list.unshift(entry);
    }

    localStorage.setItem(storageKey, JSON.stringify(list.slice(0, 25)));
    sessionStorage.setItem('admin_test_edit_id', entry.id);
}

const gspSaveAdminTestAanvraag = saveAdminTestAanvraag;

function collectAllAanvraagData() {
    const fields = {};
    const lists = {
        vak2_act_koud: sessionStorage.getItem('vak2_act_koud') || '[]',
        vak2_act_warm: sessionStorage.getItem('vak2_act_warm') || '[]',
        vak2_vervoer: sessionStorage.getItem('vak2_vervoer') || '[]',
        vak2_stoffen: sessionStorage.getItem('vak2_stoffen') || '[]',
        vak2_chemicalien: sessionStorage.getItem('vak2_chemicalien') || '[]',
        vak5_vergunningen: sessionStorage.getItem('vak5_vergunningen') || '[]',
        vak5_toelatingen: sessionStorage.getItem('vak5_toelatingen') || '[]',
        vak5_preventie: sessionStorage.getItem('vak5_preventie') || '[]'
    };
    const tables = {};
    const signatures = {};

    for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        const value = sessionStorage.getItem(key);

        if (!key || value === null) {
            continue;
        }

        if (key === 'werkvergunning_nummer' || key === 'admin_test_aanvraag' || key === 'admin_test_edit_id' || key === 'aanvraag_session_id' || key === 'aanvraag_last_submit_payload' || key === 'gsp_profile_seeded') {
            continue;
        }

        if (Object.prototype.hasOwnProperty.call(lists, key)) {
            continue;
        }

        if (key.includes('_tabel')) {
            tables[key] = value;
        } else if (key.includes('handtekening') || key.includes('signature')) {
            signatures[key] = value;
        } else if (value.startsWith('[') || value.startsWith('{')) {
            try {
                lists[key] = JSON.parse(value);
            } catch (error) {
                fields[key] = value;
            }
        } else {
            fields[key] = value;
        }
    }

    return { fields: fields, lists: lists, tables: tables, signatures: signatures };
}

const gspCollectAllAanvraagData = collectAllAanvraagData;

function firstAanvraagValue(fields, keys) {
    for (const key of keys) {
        const value = fields && fields[key] !== undefined && fields[key] !== null
            ? String(fields[key]).trim()
            : '';

        if (value !== '') {
            return value;
        }
    }

    return '';
}

function normalizeAanvraagFields(data) {
    const fields = data && data.fields ? data.fields : {};
    const aliases = {
        vak1_afdeling: ['vak1_afdeling', 'afdeling_tekst', 'afdeling', 'vak4_afdeling'],
        vak1_exzone: ['vak1_exzone', 'ex_zone', 'exzone', 'vak1_exzone_ja', 'vak1_exzone_neen'],
        vak1_werkbeschrijving: ['vak1_werkbeschrijving', 'werkbeschrijving', 'beschrijving'],
        vak2_datumwerken: ['vak2_datumwerken', 'datum_werken', 'datumwerken'],
        vak2_veiligheidstest: ['vak2_veiligheidstest', 'veiligheidstest_status', 'veiligheidstest', 'vak2_veiligheidstest_ok', 'vak2_veiligheidstest_nok'],
        vca: ['vca', 'vca_verplicht', 'vca_ja', 'vca_nee'],
        geldig_tot: ['geldig_tot', 'vca_geldig_tot'],
        vak2_firma: ['vak2_firma', 'firma_naam'],
        firma_naam: ['firma_naam', 'vak2_firma']
    };

    Object.keys(aliases).forEach(function (canonicalKey) {
        if (String(fields[canonicalKey] || '').trim() !== '') {
            return;
        }

        const value = firstAanvraagValue(fields, aliases[canonicalKey]);
        if (value !== '') {
            fields[canonicalKey] = value;
        }
    });

    if (fields.vak2_doel === 'school') {
        fields.aanvrager_is_school = 'ja';
        fields.firma_naam = 'GTI Beveren';
        fields.vak2_school_uitvoerder = 'GTI Beveren';
    } else if (fields.vak2_doel === 'externe') {
        fields.aanvrager_is_school = 'nee';
    }

    return data;
}

function validateCompleteAanvraagData(data) {
    const fields = data && data.fields ? data.fields : {};
    const required = [
        { keys: ['vak1_afdeling', 'afdeling_tekst', 'afdeling', 'vak4_afdeling'], label: 'Afdeling', page: 'werkvergunning_vak1.php' },
        { keys: ['vak1_exzone', 'ex_zone', 'exzone', 'vak1_exzone_ja', 'vak1_exzone_neen'], label: 'EX-zone', page: 'werkvergunning_vak1.php' },
        { keys: ['vak1_werkbeschrijving', 'werkbeschrijving', 'beschrijving'], label: 'Werkbeschrijving', page: 'werkvergunning_vak1.php' },
        { keys: ['uitvoerder_voornaam'], label: 'Voornaam uitvoerder', page: 'werkvergunning_vak2.php' },
        { keys: ['uitvoerder_naam'], label: 'Naam uitvoerder', page: 'werkvergunning_vak2.php' },
        { keys: ['vak2_datumwerken', 'datum_werken', 'datumwerken'], label: 'Datum werken', page: 'werkvergunning_vak2.php' },
        { keys: ['werktijd_van'], label: 'Werktijd van', page: 'werkvergunning_vak2.php' },
        { keys: ['werktijd_tot'], label: 'Werktijd tot', page: 'werkvergunning_vak2.php' },
        { keys: ['vermoedelijke_duur'], label: 'Vermoedelijke duur', page: 'werkvergunning_vak2.php' },
        { keys: ['werkzaamheden'], label: 'Werkzaamheden', page: 'werkvergunning_vak2.php' },
        { keys: ['vak2_veiligheidstest', 'veiligheidstest_status', 'veiligheidstest', 'vak2_veiligheidstest_ok', 'vak2_veiligheidstest_nok'], label: 'Veiligheidstest', page: 'werkvergunning_vak2.php' },
        { keys: ['vca', 'vca_verplicht', 'vca_ja', 'vca_nee'], label: 'VCA', page: 'werkvergunning_vak2.php' }
    ];

    for (const item of required) {
        if (firstAanvraagValue(fields, item.keys) === '') {
            return item;
        }
    }

    const doel = firstAanvraagValue(fields, ['vak2_doel']);
    const schoolFlag = firstAanvraagValue(fields, ['aanvrager_is_school']);
    const isSchool = doel === 'school' || schoolFlag === 'ja' || schoolFlag === '1';

    if (!doel && !schoolFlag) {
        return { label: 'Wie voert de werken uit?', page: 'werkvergunning_vak2.php' };
    }

    if (!isSchool && firstAanvraagValue(fields, ['vak2_firma', 'firma_naam']) === '') {
        return { label: 'Naam externe firma', page: 'werkvergunning_vak2.php' };
    }

    if (firstAanvraagValue(fields, ['vca', 'vca_verplicht', 'vca_ja', 'vca_nee']).toLowerCase() === 'ja'
        && firstAanvraagValue(fields, ['geldig_tot', 'vca_geldig_tot']) === '') {
        return { label: 'Geldig tot (VCA)', page: 'werkvergunning_vak2.php' };
    }

    return null;
}

function showCompleteAanvraagProblem(problem) {
    const message = problem.label + ' is nog niet ingevuld.';
    const goToPage = function () {
        if (problem.page) {
            window.location.href = appendTestParam(problem.page);
        }
    };

    if (typeof window.showAppPopup === 'function') {
        window.showAppPopup({
            type: 'error',
            title: 'Aanvraag nog niet volledig',
            message: message,
            solution: 'Ik breng u naar de juiste stap zodat u dit veld kunt aanvullen.'
        }).then(goToPage);
    } else {
        alert(message);
        goToPage();
    }
}

function bindAanvraagSubmitForm(formId) {
    const form = document.getElementById(formId);
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!validateCurrentPage()) {
            return;
        }

        saveCurrentVak();
        const aanvraagData = normalizeAanvraagFields(gspCollectAllAanvraagData());
        const fullFormProblem = validateCompleteAanvraagData(aanvraagData);

        if (fullFormProblem && !(typeof isAdminTestMode === 'function' && isAdminTestMode())) {
            showCompleteAanvraagProblem(fullFormProblem);
            return;
        }

        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.dataset.originalText = submitButton.textContent || '';
            if (submitButton.textContent) {
                submitButton.textContent = 'Werkvergunning wordt ingediend...';
            }
        }

        const hiddenInput = form.querySelector('input[name="aanvraag_data"]');
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(aanvraagData);
            sessionStorage.setItem('aanvraag_last_submit_payload', hiddenInput.value);
        }

        if (typeof isAdminTestMode === 'function' && isAdminTestMode()) {
            gspSaveAdminTestAanvraag(aanvraagData);
            gspClearAanvraagDraftData();
            sessionStorage.removeItem('admin_test_edit_id');

            if (typeof window.showAppPopup === 'function') {
                window.showAppPopup({
                    type: 'success',
                    title: 'Testaanvraag bewaard',
                    message: 'De testaanvraag werd lokaal bewaard en niet naar de database gestuurd.',
                    solution: 'U vindt de test terug op het admin-overzicht.'
                }).then(function () {
                    window.location.href = '../pages/overzicht_admin.php';
                });
            } else {
                window.location.href = '../pages/overzicht_admin.php';
            }
            return;
        }

        sessionStorage.setItem('aanvraag_submit_pending', 'true');
        form.submit();
    });
}

function loadCurrentVakData() {
    const lotoForm = document.querySelector('.form-card');
    if(!lotoForm) return;

    // Input / select / textarea
    lotoForm.querySelectorAll('input, select, textarea').forEach(el=>{
        const key = inlineInputStorageKey(el);
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

            const parentCheckbox = el.closest('.checkbox-item')?.querySelector('input[type="checkbox"]');
            if (parentCheckbox && el.value.trim() !== '') {
                parentCheckbox.checked = true;
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

    const afdGeen = document.getElementById('afd_geen');
    if (afdGeen) {
        afdGeen.checked = sessionStorage.getItem('afd_geen') === '1';
        afdGeen.dispatchEvent(new Event('change'));
    }

    const vak2Doel = sessionStorage.getItem('vak2_doel');
    if (vak2Doel) {
        const radio = document.querySelector('input[name="vak2_doel"][value="' + vak2Doel + '"]');
        if (radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
        }
    }

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
    seedProfileDefaults();
    attachInlineInputIds();
    rememberDynamicTableTemplates();
    initStorageGroupsForPage();
    loadCurrentVakData();
    initVoertuigAttestenVisibility();
    initVehicleRowAttestToggle();
    initVak1PhotoInput();
    initWerkvergunningNummer();
    attachDynamicRowHandlers();
    attachSchoolToggle();
    attachExclusiveCheckPairs();
    attachOtherTextAutoCheck();
    initVakProgressBar();
    initAdminTestModeUi();
    attachNavigationAutoSave();
    bindAanvraagSubmitForm('aanvraagOpslaanForm');
});
