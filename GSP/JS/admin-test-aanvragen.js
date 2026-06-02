(function () {
    const STORAGE_KEY = 'gsp_admin_test_aanvragen';

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
            const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    function saveTestAanvragen(aanvragen) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(aanvragen));
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

    function fullName(data, voornaamKey, naamKey) {
        return [field(data, voornaamKey), field(data, naamKey)].filter(Boolean).join(' ').trim();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('admin_test_aanvragen');
        if (!container) return;

        const aanvragen = loadTestAanvragen();
        if (aanvragen.length === 0) return;

        const rows = aanvragen.map(function (aanvraag) {
            const werk = field(aanvraag.data, 'vak1_werkbeschrijving') || field(aanvraag.data, 'werkzaamheden') || 'Geen beschrijving ingevuld';
            const afdeling = field(aanvraag.data, 'vak1_afdeling') || 'Niet ingevuld';
            const aanvrager = aanvraag.aanvrager || fullName(aanvraag.data, 'aanvrager_voornaam', 'aanvrager_naam') || 'Onbekend';

            return '<tr>' +
                '<td>' + escapeHtml(aanvraag.id) + '</td>' +
                '<td>' + escapeHtml(aanvrager) + '</td>' +
                '<td>' + escapeHtml(afdeling) + '</td>' +
                '<td>' + escapeHtml(werk) + '</td>' +
                '<td>' + escapeHtml(formatDate(aanvraag.updatedAt || aanvraag.createdAt)) + '</td>' +
                '<td class="table-actions">' +
                '<button type="button" class="small-btn open-btn" data-view-test="' + escapeHtml(aanvraag.id) + '">Bekijken</button>' +
                '<button type="button" class="small-btn open-btn" data-edit-test="' + escapeHtml(aanvraag.id) + '">Bewerken</button>' +
                '<button type="button" class="small-btn delete-btn" data-delete-test="' + escapeHtml(aanvraag.id) + '">Verwijderen</button>' +
                '</td>' +
                '</tr>';
        }).join('');

        container.innerHTML =
            '<div class="test-aanvragen-intro">Deze testaanvragen staan alleen lokaal in deze browser en zijn niet gekoppeld aan de database of keuringen.</div>' +
            '<div style="overflow-x:auto;">' +
            '<table class="admin-test-table">' +
            '<thead><tr><th>Testnummer</th><th>Aanvrager</th><th>Afdeling</th><th>Beschrijving</th><th>Laatst gewijzigd</th><th>Acties</th></tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>' +
            '</div>';

        container.addEventListener('click', function (event) {
            const viewBtn = event.target.closest('[data-view-test]');
            if (viewBtn) {
                window.location.href = 'testaanvraag_bekijken.php?id=' + encodeURIComponent(viewBtn.dataset.viewTest);
                return;
            }

            const editBtn = event.target.closest('[data-edit-test]');
            if (editBtn) {
                window.location.href = '../PHP/werkvergunning_vak1.php?edit_test=' + encodeURIComponent(editBtn.dataset.editTest);
                return;
            }

            const deleteBtn = event.target.closest('[data-delete-test]');
            if (!deleteBtn) return;

            const testId = deleteBtn.dataset.deleteTest;
            const current = loadTestAanvragen().filter(function (item) {
                return item && item.id !== testId;
            });
            saveTestAanvragen(current);
            window.location.reload();
        });
    });
})();
