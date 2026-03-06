
document.addEventListener('DOMContentLoaded', function()
{
    const form = document.getElementById('foto-formulier');
    const formSection = document.getElementById('form-container');
    const loadingSection = document.getElementById('laden');
    const resultSection = document.getElementById('resultaat');
    const flyerImg = document.getElementById('flyerAfbeelding');
    const downloadBtn = document.getElementById('downloadBtn');
    const debugInfo = document.getElementById('debugInfo');
    const imageError = document.getElementById('imageError');
    const retryBtn = document.getElementById('retryBtn');

    form.addEventListener('submit', function(e)
    {
        e.preventDefault();

        formSection.classList.add('hidden');
        loadingSection.classList.remove('hidden');

        const formData = new FormData(form);

        const webhookUrl = 'https://hook.eu1.make.com/ovpqna7vldy6ssxtvx3hrswk95uv40zh';

        fetch(webhookUrl,
        {
            method: 'POST',
            body: formData
        })
        .then(response =>
        {
            if (!response.ok)
            {
                throw new Error('Er was een probleem met de serververbinding.');
            }
            return response.text();
        })
        .then(url =>
        {
            console.log('Ontvangen antwoord:', url);
            console.log('Antwoord type:', typeof url);
            console.log('Antwoord lengte:', url ? url.length : 0);


            const cleanUrl = url ? url.trim() : '';

            if (cleanUrl && cleanUrl.startsWith('http'))
            {
                flyerImg.src = cleanUrl;
                flyerImg.crossOrigin = 'anonymous';

                // Debug info tonen
                debugInfo.textContent = 'Afbeelding URL: ' + cleanUrl;

                // Verberg error bericht eerst
                imageError.classList.add('hidden');

                // Afbeelding load event
                flyerImg.onload = function() {
                    console.log('Afbeelding succesvol geladen');
                    debugInfo.textContent += ' (geladen)';
                    imageError.classList.add('hidden');
                };

                flyerImg.onerror = function() {
                    console.error('Afbeelding kon niet worden geladen');
                    debugInfo.textContent += ' (FOUT: kon niet laden)';
                    imageError.classList.remove('hidden');
                };

                // Retry button functionaliteit
                retryBtn.onclick = function() {
                    console.log('Opnieuw proberen afbeelding te laden...');
                    flyerImg.src = cleanUrl + '?t=' + Date.now(); // Cache busting
                    imageError.classList.add('hidden');
                    debugInfo.textContent = 'Afbeelding URL: ' + cleanUrl + ' (opnieuw proberen...)';
                };

                // Download functie instellen
                downloadBtn.onclick = function() {
                    downloadImage(cleanUrl, 'flyer-redesign.png');
                };

                loadingSection.classList.add('hidden');
                resultSection.classList.remove('hidden');
            }
            else
            {
                console.error('Fout: Ongeldig antwoord ontvangen. Volledige antwoord: "' + url + '"');
                throw new Error('Geen geldige flyer-URL ontvangen. Controleer je Make.com scenario. Ontvangen: ' + (url ? '"' + url + '"' : 'leeg'));
            }
        })
        .catch(error =>
        {
            console.error('Error:', error);
            alert('Er ging iets mis: ' + error.message);

            loadingSection.classList.add('hidden');
            formSection.classList.remove('hidden');
        });
    });

    // Download functie
    function downloadImage(imageUrl, filename) {
        try {
            console.log('Download starten voor:', imageUrl);

            // Probeer eerst de standaard download
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = filename;
            link.target = '_blank';

            // Voor CORS problemen, probeer de afbeelding eerst te laden
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                console.log('Download succesvol gestart');
            };
            img.onerror = function() {
                console.warn('CORS probleem gedetecteerd, open in nieuw tabblad');
                window.open(imageUrl, '_blank');
                alert('Download geopend in nieuw tabblad vanwege beveiligingsbeperkingen. Gebruik Ctrl+S om op te slaan.');
            };
            img.src = imageUrl;

        } catch (error) {
            console.error('Download fout:', error);
            // Fallback: open in nieuw tabblad
            window.open(imageUrl, '_blank');
            alert('Download geopend in nieuw tabblad. Gebruik Ctrl+S om op te slaan.');
        }
    }
});