var formulier = document.getElementById('foto-formulier');

formulier.addEventListener('submit', function(e) {

    e.preventDefault();

    // toon laden
    document.querySelector('.form-section').style.display = 'none';
    document.getElementById('laden').style.display = 'block';

    // formdata maken
    var formData = new FormData(formulier);

    // 👉 VERVANG MET JOUW MAKE WEBHOOK
    var webhookUrl = "https://hook.eu1.make.com/du5haqcvk8a6qgopbzoqs2ovyf6y1hk3";

    fetch(webhookUrl, {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        return response.text();
    })
    .then(function(flyerUrl) {

        document.getElementById('laden').style.display = 'none';
        document.getElementById('resultaat').style.display = 'block';

        document.getElementById('flyerAfbeelding').src = flyerUrl;
    })
    .catch(function(error) {

        document.getElementById('laden').style.display = 'none';
        document.querySelector('.form-section').style.display = 'block';

        alert("Er ging iets mis: " + error.message);
    });

});