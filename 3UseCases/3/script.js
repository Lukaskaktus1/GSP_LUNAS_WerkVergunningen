
document.addEventListener('DOMContentLoaded', function() 
{
    const form = document.getElementById('foto-formulier');
    const formSection = document.getElementById('form-container');
    const loadingSection = document.getElementById('laden');
    const resultSection = document.getElementById('resultaat');
    const flyerImg = document.getElementById('flyerAfbeelding');

    form.addEventListener('submit', function(e)
    {
        e.preventDefault();

        formSection.classList.add('hidden');
        loadingSection.classList.remove('hidden');

        const formData = new FormData(form);

        const webhookUrl = 'https://hook.eu2.make.com/JOUW-WEBHOOK-ID';

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
            if (url && url.startsWith('http'))
            {
                flyerImg.src = url;
                loadingSection.classList.add('hidden');
                resultSection.classList.remove('hidden');
            }
            else 
            {
                throw new Error('Geen geldige flyer-URL ontvangen. Controleer je Make.com scenario.');
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
});