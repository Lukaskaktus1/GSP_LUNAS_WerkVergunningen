// ==== ELEMENTEN SELECTEREN ====
const formulier = document.getElementById('foto-formulier');
const formSection = document.querySelector('.form-section');
const laadSection = document.getElementById('laden');
const resultaatSection = document.getElementById('resultaat');
const flyerImg = document.getElementById('flyerAfbeelding');

const fileInput = document.querySelector('input[name="foto"]');
const fileLabel = document.querySelector('label[for="foto"]');

const downloadBtn = document.getElementById('downloadKnop'); // voeg in HTML toe als <a id="downloadKnop">Download</a>
const resetBtn = document.getElementById('resetBtn'); // voeg in HTML toe als <button id="resetBtn">Reset</button>

// Privacy modal elementen (optioneel)
const openPrivacyBtn = document.getElementById('open-privacy');
const closePrivacyBtn = document.getElementById('close-privacy');
const closePrivacyFooterBtn = document.getElementById('close-privacy-btn');
const privacyModal = document.getElementById('privacy-modal');
const privacyCheckbox = document.getElementById('privacy');

// ==== PRIVACY MODAL ====
if (openPrivacyBtn && privacyModal) {
    openPrivacyBtn.addEventListener('click', () => {
        privacyModal.classList.add('active');
    });
}

const closeModal = () => {
    if (privacyModal) privacyModal.classList.remove('active');
};

if (closePrivacyBtn) closePrivacyBtn.addEventListener('click', closeModal);
if (closePrivacyFooterBtn) {
    closePrivacyFooterBtn.addEventListener('click', () => {
        if (privacyCheckbox) privacyCheckbox.checked = true;
        closeModal();
    });
}
if (privacyModal) {
    privacyModal.addEventListener('click', (e) => {
        if (e.target === privacyModal) closeModal();
    });
}

// ==== FILE INPUT LABEL UPDATE ====
if (fileInput) {
    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            fileLabel.innerHTML = `<span class="file-label-primary">Geselecteerd:</span> ${this.files[0].name}`;
        }
    });
}

// ==== FORM SUBMIT ====
formulier.addEventListener('submit', function (e) {
    e.preventDefault();

    // UI aanpassen
    formSection.style.display = 'none';
    laadSection.style.display = 'block';

    const formData = new FormData(formulier);
    const webhookUrl = formulier.action; // of je Make webhook direct invoeren

    fetch(webhookUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) throw new Error('Netwerkfout');
            return response.blob();
        })
        .then(blob => {
            const flyerUrl = URL.createObjectURL(blob);
            showResult(flyerUrl);
        })
        .catch(error => {
            laadSection.style.display = 'none';
            formSection.style.display = 'block';
            alert('Er ging iets fout: ' + error.message);
        });
});

// ==== SHOW RESULT FUNCTION ====
function showResult(url) {
    laadSection.style.display = 'none';
    resultaatSection.style.display = 'block';
    flyerImg.src = url;
}

// ==== DOWNLOAD BUTTON ====
if (downloadBtn) {
    downloadBtn.addEventListener('click', () => {
        if (!flyerImg.src) return;
        const link = document.createElement('a');
        link.href = flyerImg.src;
        link.download = 'AI_Flyer.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}

// ==== RESET BUTTON ====
if (resetBtn) {
    resetBtn.addEventListener('click', () => {
        resultaatSection.style.display = 'none';
        formSection.style.display = 'block';
        formulier.reset();

        // Reset file label
        fileLabel.innerHTML = `
            <svg class="file-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span class="file-label-primary">Klik om te uploaden</span> of sleep je bestand hierheen
        `;
    });
}