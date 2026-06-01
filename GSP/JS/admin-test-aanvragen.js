(function () {
    function formatDate(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleString('nl-BE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function loadTestAanvragen() {
        try {
            const parsed = JSON.parse(localStorage.getItem('gsp_admin_test_aanvragen') || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    function saveTestAanvragen(aanvragen) {
        localStorage.setItem('gsp_admin_test_aanvragen', JSON.stringify(aanvragen));
    }

    function field(data, key) {
        return data && data.fields && data.fields[key] ? data.fields[key] : '';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('admin_test_aanvragen');
        if (!container) return;

        const aanvragen = loadTestAanvragen();
        if (aanvragen.length === 0) return;

        const rows = aanvragen.map(function (aanvraag, index) {
            const werk = field(aanvraag.data, 'vak1_werkbeschrijving') || field(aanvraag.data, 'werkzaamheden') || 'Geen beschrijving ingevuld';
            const afdeling = field(aanvraag.data, 'vak1_afdeling') || 'Niet ingevuld';

            return '<tr>' +
                '<td>' + escapeHtml(aanvraag.id) + '</td>' +
                '<td>' + escapeHtml(aanvraag.aanvrager) + '</td>' +
                '<td>' + escapeHtml(afdeling) + '</td>' +
                '<td>' + escapeHtml(werk) + '</td>' +
                '<td>' + escapeHtml(formatDate(aanvraag.createdAt)) + '</td>' +
                '<td><button type="button" class="small-btn delete-btn" data-delete-test="' + index + '">Verwijderen</button></td>' +
                '</tr>';
        }).join('');

        container.innerHTML =
            '<div class="test-aanvragen-intro">Deze testaanvragen staan alleen lokaal in deze browser en zijn niet gekoppeld aan de database of keuringen.</div>' +
            '<div style="overflow-x:auto;">' +
            '<table class="admin-test-table">' +
            '<thead><tr><th>Testnummer</th><th>Admin</th><th>Afdeling</th><th>Beschrijving</th><th>Aangemaakt</th><th>Acties</th></tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>' +
            '</div>';

        container.addEventListener('click', function (event) {
            const button = event.target.closest('[data-delete-test]');
            if (!button) return;

            const index = Number(button.dataset.deleteTest);
            const current = loadTestAanvragen();
            current.splice(index, 1);
            saveTestAanvragen(current);
            window.location.reload();
        });
    });
})();
