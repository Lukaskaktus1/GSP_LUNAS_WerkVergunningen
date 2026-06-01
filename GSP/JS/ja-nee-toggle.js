(function () {
    let initialized = false;

    function disableContainer(container, clearValues) {
        if (!container) return;
        container.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (clearValues) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                } else {
                    field.value = '';
                }
            }
            field.disabled = true;
        });
        container.classList.add('is-disabled-group');
    }

    function enableContainer(container) {
        if (!container) return;
        container.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = false;
        });
        container.classList.remove('is-disabled-group');
    }

    function showContainer(container) {
        if (!container) return;
        container.hidden = false;
        container.removeAttribute('hidden');
        container.style.display = '';
    }

    function hideContainer(container) {
        if (!container) return;
        container.hidden = true;
        container.setAttribute('hidden', 'hidden');
        container.style.display = 'none';
    }

    function applyJaNeeGroup(group) {
        const targetSelector = group.getAttribute('data-ja-nee-target');
        const enableValue = (group.getAttribute('data-ja-nee-enable-value') || 'ja').toLowerCase();
        if (!targetSelector) return;

        const target = document.querySelector(targetSelector);
        const checked = group.querySelector('input[type="radio"]:checked');

        if (checked && checked.value.toLowerCase() === enableValue) {
            enableContainer(target);
            showContainer(target);
        } else {
            disableContainer(target, true);
            hideContainer(target);
        }
    }

    function applyRadioChoice() {
        const schoolGroup = document.getElementById('vak2_school_group') || document.getElementById('vak2_klas_group');
        const firmaGroup = document.getElementById('vak2_firma_group');
        const schoolInput = document.getElementById('vak2_school_uitvoerder') || document.getElementById('vak2_klas');
        const firmaInput = document.getElementById('vak2_firma');
        const selected = document.querySelector('input[name="vak2_doel"]:checked');

        if (!schoolGroup || !firmaGroup) return;

        if (!selected) {
            hideContainer(schoolGroup);
            hideContainer(firmaGroup);
            disableContainer(schoolGroup, true);
            disableContainer(firmaGroup, true);
            return;
        }

        if (selected.value === 'school') {
            showContainer(schoolGroup);
            hideContainer(firmaGroup);
            enableContainer(schoolGroup);
            if (schoolInput) {
                schoolInput.value = 'GTI Beveren';
                schoolInput.required = false;
            }
            disableContainer(firmaGroup, true);
            if (firmaInput) firmaInput.required = false;
            sessionStorage.setItem('vak2_doel', 'school');
            sessionStorage.setItem('aanvrager_is_school', 'ja');
            sessionStorage.setItem('vak2_school_uitvoerder', 'GTI Beveren');
            sessionStorage.setItem('firma_naam', 'GTI Beveren');
        } else {
            showContainer(firmaGroup);
            hideContainer(schoolGroup);
            enableContainer(firmaGroup);
            if (firmaInput) firmaInput.required = true;
            disableContainer(schoolGroup, true);
            if (schoolInput) schoolInput.required = false;
            sessionStorage.setItem('vak2_doel', 'externe');
            sessionStorage.setItem('aanvrager_is_school', 'nee');
            sessionStorage.removeItem('vak2_school_uitvoerder');
            if (!firmaInput || firmaInput.value.trim() === '') {
                sessionStorage.removeItem('firma_naam');
            }
        }
    }

    function applyVak4Geen() {
        const geen = document.getElementById('afd_geen');
        const group = document.getElementById('vak4_aandachtspunten_group');
        const input = document.getElementById('vak4_aandachtspunten');
        if (!geen || !group) return;

        if (geen.checked) {
            disableContainer(group, true);
            if (input) input.value = '';
            sessionStorage.setItem('afd_geen', '1');
            sessionStorage.removeItem('vak4_aandachtspunten');
        } else {
            enableContainer(group);
            sessionStorage.removeItem('afd_geen');
        }
    }

    function initJaNeeToggles() {
        if (initialized) return;
        initialized = true;

        document.querySelectorAll('[data-ja-nee-target]').forEach(function (group) {
            group.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    applyJaNeeGroup(group);
                });
            });
            applyJaNeeGroup(group);
        });

        document.querySelectorAll('input[name="vak2_doel"]').forEach(function (radio) {
            radio.addEventListener('change', applyRadioChoice);
        });
        document.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'vak2_doel') {
                applyRadioChoice();
            }
        });
        applyRadioChoice();

        const geen = document.getElementById('afd_geen');
        if (geen) {
            geen.addEventListener('change', applyVak4Geen);
            applyVak4Geen();
        }
    }

    document.addEventListener('DOMContentLoaded', initJaNeeToggles);
    if (document.readyState !== 'loading') {
        initJaNeeToggles();
    }
})();
