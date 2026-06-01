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

        function update() {
            const current = state();
            setCriterionState(items.length, current.length);
            setCriterionState(items.digit, current.digit);
            setCriterionState(items.special, current.special);

            password.setCustomValidity(isValid() ? '' : 'Het wachtwoord voldoet nog niet aan alle criteria.');

            if (confirm && confirm.value !== '') {
                confirm.setCustomValidity(confirm.value === password.value ? '' : 'De wachtwoorden komen niet overeen.');
            }

            if (submit) {
                submit.disabled = !isValid();
            }
        }

        password.addEventListener('input', update);
        if (confirm) confirm.addEventListener('input', update);

        form.addEventListener('submit', function (event) {
            update();
            if (!isValid()) {
                event.preventDefault();
                if (typeof window.showAppPopup === 'function') {
                    window.showAppPopup({
                        type: 'error',
                        title: 'Wachtwoord nog niet juist',
                        message: 'Je wachtwoord moet voldoen aan alle criteria.',
                        solution: 'Gebruik minstens 8 tekens, minstens 1 cijfer en minstens 1 speciaal teken.'
                    });
                }
                password.focus();
            }
        });

        update();
    });
})();
