(function () {
    function setCriterionState(item, passed) {
        if (!item) return;
        item.classList.toggle('is-valid', passed);
        item.classList.toggle('is-invalid', !passed);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.login-form');
        const password = document.getElementById('password');
        const confirm = document.getElementById('password_confirm');
        const submit = document.getElementById('register_submit');
        const criteria = document.getElementById('password_criteria');
        const role = document.getElementById('rol');
        const leerlingKlasGroup = document.getElementById('leerling_klas_group');
        const leerlingKlas = document.getElementById('klas');
        const leerkrachtGroup = document.getElementById('leerkracht_klassen_group');
        const leerkrachtTable = document.getElementById('leerkracht_klassen');
        const addTeacherClass = document.getElementById('leerkracht_klas_toevoegen');

        if (!form || !password || !criteria) return;

        const items = {
            length: criteria.querySelector('[data-criterion="length"]'),
            digit: criteria.querySelector('[data-criterion="digit"]'),
            special: criteria.querySelector('[data-criterion="special"]')
        };

        function state() {
            const value = password.value;
            return {
                length: value.length >= 8,
                digit: /[0-9]/.test(value),
                special: /[^A-Za-z0-9]/.test(value)
            };
        }

        function isValid() {
            const current = state();
            return current.length && current.digit && current.special;
        }

        function passwordsMatch() {
            return Boolean(confirm) && confirm.value !== '' && confirm.value === password.value;
        }

        function visibleRequiredFieldsAreValid() {
            const fields = Array.from(form.querySelectorAll('input, select, textarea'));

            return fields.every(function (field) {
                if (field.disabled || field.type === 'hidden') return true;
                if (field.closest('[hidden]')) return true;
                if (!field.required) return true;
                if (field === password || field === confirm) return true;

                return typeof field.checkValidity === 'function'
                    ? field.checkValidity()
                    : String(field.value || '').trim() !== '';
            });
        }

        function formIsReady() {
            return isValid() && passwordsMatch() && visibleRequiredFieldsAreValid();
        }

        function update() {
            const current = state();
            setCriterionState(items.length, current.length);
            setCriterionState(items.digit, current.digit);
            setCriterionState(items.special, current.special);

            password.setCustomValidity(isValid() ? '' : 'Het wachtwoord voldoet nog niet aan alle criteria.');

            if (confirm) {
                if (confirm.value === '') {
                    confirm.setCustomValidity('Herhaal uw wachtwoord.');
                } else {
                    confirm.setCustomValidity(passwordsMatch() ? '' : 'De wachtwoorden komen niet overeen.');
                }
            }

            if (submit) {
                submit.disabled = !formIsReady();
            }
        }

        function updateRoleFields() {
            const isTeacher = role && role.value === 'leerkracht';

            if (leerlingKlasGroup) leerlingKlasGroup.hidden = isTeacher;
            if (leerkrachtGroup) leerkrachtGroup.hidden = !isTeacher;
            if (leerlingKlas) {
                leerlingKlas.required = !isTeacher;
                leerlingKlas.disabled = isTeacher;
            }
            if (leerkrachtTable) {
                leerkrachtTable.querySelectorAll('input, select').forEach(function (field) {
                    field.required = isTeacher;
                    field.disabled = !isTeacher;
                });
            }

            update();
        }

        if (role) {
            role.addEventListener('change', updateRoleFields);
            updateRoleFields();
        }

        if (addTeacherClass && leerkrachtTable) {
            addTeacherClass.addEventListener('click', function () {
                const firstRow = leerkrachtTable.querySelector('[data-register-row]');
                if (!firstRow) return;
                const row = firstRow.cloneNode(true);
                row.querySelectorAll('input, select').forEach(function (field) {
                    field.value = '';
                });
                leerkrachtTable.appendChild(row);
                updateRoleFields();
                update();
            });

            leerkrachtTable.addEventListener('click', function (event) {
                const button = event.target.closest('.register-row-remove');
                if (!button) return;
                const rows = leerkrachtTable.querySelectorAll('[data-register-row]');
                const row = button.closest('[data-register-row]');
                if (rows.length <= 1) {
                    row.querySelectorAll('input, select').forEach(function (field) {
                        field.value = '';
                    });
                    return;
                }
                row.remove();
                update();
            });
        }

        password.addEventListener('input', update);
        if (confirm) confirm.addEventListener('input', update);
        form.addEventListener('input', update);
        form.addEventListener('change', update);

        form.addEventListener('submit', function (event) {
            update();
            if (!formIsReady()) {
                event.preventDefault();
                let message = 'Vul alle verplichte velden correct in.';
                let solution = 'Controleer uw gegevens, wachtwoord en wachtwoordherhaling.';

                if (!isValid()) {
                    message = 'Je wachtwoord moet voldoen aan alle criteria.';
                    solution = 'Gebruik minstens 8 tekens, minstens 1 cijfer en minstens 1 speciaal teken.';
                } else if (!passwordsMatch()) {
                    message = 'De wachtwoordherhaling ontbreekt of komt niet overeen.';
                    solution = 'Typ hetzelfde wachtwoord opnieuw in het veld Wachtwoord herhalen.';
                }

                if (typeof window.showAppPopup === 'function') {
                    window.showAppPopup({
                        type: 'error',
                        title: 'Registratie nog niet volledig',
                        message: message,
                        solution: solution
                    });
                }
                const invalidField = Array.from(form.querySelectorAll('input, select, textarea')).find(function (field) {
                    return !field.disabled
                        && !field.closest('[hidden]')
                        && typeof field.checkValidity === 'function'
                        && !field.checkValidity();
                });

                (invalidField || password).focus();
            }
        });

        update();
    });
})();
