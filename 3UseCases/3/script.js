document.addEventListener("DOMContentLoaded", function () {
  // De webhook URL van Make.com
  const webhookUrl = "https://hook.eu1.make.com/ovpqna7vldy6ssxtvx3hrswk95uv40zh";

  // Selecteer de elementen
  var formulier = document.getElementById("foto-formulier");
  var formSection = document.getElementById("form-container");
  var laadSectie = document.getElementById("laden");
  var resultaatSectie = document.getElementById("resultaat");
  var resultaatAfbeelding = document.getElementById("flyerAfbeelding");
  var downloadKnop = document.getElementById("downloadKnop");
  var backBtn = document.getElementById("backBtn");

  if (!formulier) {
    console.error("Formulier #foto-formulier niet gevonden.");
    return;
  }

  // Luister naar het verzenden van het formulier
  formulier.addEventListener("submit", function (e) {
    // Voorkom standaard formulier verzending
    e.preventDefault();

    // Toon de laad-sectie en verberg het formulier
    formSection.classList.add("hidden");
    resultaatSectie.classList.add("hidden");
    laadSectie.classList.remove("hidden");

    // Verzamel de data uit het formulier
    var formData = new FormData(formulier);

    // Verstuur de data naar de webhook
    fetch(webhookUrl, {
      method: "POST",
      body: formData,
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("Netwerk response was niet ok");
        }
        // Make.com stuurt een raw image (PNG) terug
        return response.blob();
      })
      .then(function (blob) {
        // Zet de ontvangen binary blob om in een lokale object URL
        const flyerUrl = URL.createObjectURL(blob);

        // Zet de ontvangen URL in het src attribuut van de afbeelding
        resultaatAfbeelding.src = flyerUrl;

        // Stel de link in voor de downloadknop
        downloadKnop.href = flyerUrl;
        downloadKnop.download = "flyer-redesign.png";

        // Verberg de laad-sectie en toon het resultaat
        laadSectie.classList.add("hidden");
        resultaatSectie.classList.remove("hidden");
      })
      .catch(function (error) {
        // Foutafhandeling
        console.error("Er is een probleem opgetreden:", error);
        laadSectie.classList.add("hidden");
        formSection.classList.remove("hidden");

        // Simpele melding aan de gebruiker
        var errorMsg = document.createElement("p");
        errorMsg.style.color = "red";
        errorMsg.innerText =
          "Oeps! Er ging iets mis bij het genereren. Probeer het later opnieuw.";
        formSection.appendChild(errorMsg);
      });
  });

  // Terug naar start: formulier opnieuw tonen, resultaat verbergen, afbeelding resetten
  if (backBtn) {
    backBtn.addEventListener("click", function () {
      resultaatSectie.classList.add("hidden");
      laadSectie.classList.add("hidden");
      formSection.classList.remove("hidden");
      resultaatAfbeelding.src = "";
      // optioneel: formulier resetten
      if (formulier) {
        formulier.reset();
      }
    });
  }
});