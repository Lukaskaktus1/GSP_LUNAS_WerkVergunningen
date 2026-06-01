(function () {
    function ensurePopupRoot() {
        let root = document.getElementById('app_popup_root');

        if (!root) {
            root = document.createElement('div');
            root.id = 'app_popup_root';
            root.className = 'app-popup-root';
            document.body.appendChild(root);
        }

        return root;
    }

    function closePopup() {
        const root = document.getElementById('app_popup_root');
        if (root) root.innerHTML = '';
    }

    function showAppPopup(options) {
        const settings = Object.assign({
            type: 'info',
            title: 'Melding',
            message: '',
            solution: '',
            actions: [{ label: 'Ok', value: true, primary: true }]
        }, options || {});

        return new Promise(function (resolve) {
            const root = ensurePopupRoot();
            const overlay = document.createElement('div');
            overlay.className = 'app-popup-overlay';

            const dialog = document.createElement('section');
            dialog.className = 'app-popup app-popup-' + settings.type;
            dialog.setAttribute('role', 'dialog');
            dialog.setAttribute('aria-modal', 'true');

            const icon = document.createElement('div');
            icon.className = 'app-popup-icon';
            icon.textContent = settings.type === 'success' ? '✓' : settings.type === 'error' ? '!' : '?';

            const title = document.createElement('h2');
            title.textContent = settings.title;

            const message = document.createElement('p');
            message.className = 'app-popup-message';
            message.textContent = settings.message;

            dialog.appendChild(icon);
            dialog.appendChild(title);
            dialog.appendChild(message);

            if (settings.solution) {
                const solution = document.createElement('p');
                solution.className = 'app-popup-solution';
                solution.textContent = settings.solution;
                dialog.appendChild(solution);
            }

            const actions = document.createElement('div');
            actions.className = 'app-popup-actions';

            settings.actions.forEach(function (action) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = action.primary ? 'app-popup-button primary' : 'app-popup-button';
                button.textContent = action.label;
                button.addEventListener('click', function () {
                    closePopup();
                    resolve(action.value);
                });
                actions.appendChild(button);
            });

            dialog.appendChild(actions);
            overlay.appendChild(dialog);
            root.innerHTML = '';
            root.appendChild(overlay);

            const firstButton = dialog.querySelector('button');
            if (firstButton) firstButton.focus();
        });
    }

    window.showAppPopup = showAppPopup;
    window.alert = function (message) {
        showAppPopup({
            type: 'error',
            title: 'Let op',
            message: String(message || ''),
            solution: 'Controleer de gegevens op deze pagina en probeer daarna opnieuw.'
        });
    };

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.dataset.confirmMessage || form.dataset.confirmed === 'true') return;

        event.preventDefault();

        showAppPopup({
            type: 'info',
            title: form.dataset.confirmTitle || 'Bevestigen',
            message: form.dataset.confirmMessage,
            solution: form.dataset.confirmSolution || 'Kies bevestigen om verder te gaan, of annuleren om niets te wijzigen.',
            actions: [
                { label: 'Annuleren', value: false },
                { label: 'Bevestigen', value: true, primary: true }
            ]
        }).then(function (confirmed) {
            if (!confirmed) return;
            form.dataset.confirmed = 'true';
            form.submit();
        });
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-app-flash]').forEach(function (flash) {
            showAppPopup({
                type: flash.dataset.type || 'info',
                title: flash.dataset.title || 'Melding',
                message: flash.dataset.message || '',
                solution: flash.dataset.solution || ''
            });
        });

        document.querySelectorAll('[data-review-notification]').forEach(function (notification) {
            const storageKey = 'review_notification_seen_' + notification.dataset.id;
            if (sessionStorage.getItem(storageKey) === 'true') return;

            sessionStorage.setItem(storageKey, 'true');
            const werk = notification.dataset.werk ? ' Werk: ' + notification.dataset.werk : '';

            showAppPopup({
                type: 'success',
                title: 'Nieuwe werkvergunning klaar voor keuring',
                message: notification.dataset.nummer + ' werd aangevraagd door ' + notification.dataset.aanvrager + '.' + werk,
                solution: 'U kunt de aanvraag nu bekijken of voorlopig laten staan in de keuringenlijst.',
                actions: [
                    { label: 'Voorlopig laten', value: false },
                    { label: 'Bekijken', value: true, primary: true }
                ]
            }).then(function (open) {
                if (open && notification.dataset.url) {
                    window.location.href = notification.dataset.url;
                }
            });
        });
    });
})();
