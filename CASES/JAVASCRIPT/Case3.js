document.addEventListener("DOMContentLoaded", function () {

    const formulier = document.getElementById("foto-formulier");
    const formSection = document.querySelector(".form-section");
    const laden = document.getElementById("laden");
    const resultaat = document.getElementById("resultaat");
    const flyerAfbeelding = document.getElementById("flyerAfbeelding");

    const webhookUrl = "https://hook.eu1.make.com/du5haqcvk8a6qgopbzoqs2ovyf6y1hk3";

    formulier.addEventListener("submit", function(e) {

        e.preventDefault();

        // Formulier verbergen
        formSection.style.display = "none";

        // Loading tonen
        laden.style.display = "block";

        const formData = new FormData(formulier);

        fetch(webhookUrl, {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {

            console.log("Webhook response:", data);

            // eventuele spaties of linebreaks verwijderen
            const flyerUrl = data.trim();

            // loading verbergen
            laden.style.display = "none";

            // resultaat tonen
            resultaat.style.display = "block";

            // afbeelding tonen
            flyerAfbeelding.src = flyerUrl;

        })
        .catch(error => {

            console.error("Fout:", error);

            laden.style.display = "none";
            formSection.style.display = "block";

            alert("Er ging iets mis bij het genereren van de flyer.");
        });

    });

});