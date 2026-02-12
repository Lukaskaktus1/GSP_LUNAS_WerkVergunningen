
(function () {
  'use strict';

  // ✅ JOUW MAKE WEBHOOK
  var WEBHOOK_URL = 'https://hook.eu1.make.com/gx5f41byssv6f7fqdejtd122488f8avd';

  var form = document.getElementById('formulier');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    // 🔹 Velden ophalen
    var naam = document.getElementById('voornaam_achternaam').value.trim();
    var email = document.getElementById('email').value.trim();
    var geboortedatum = document.getElementById('geboortedatum').value;
    var afdeling = document.getElementById('afdeling').value;
    var ervaring = document.getElementById('ervaring').value;
    var motivatie = document.getElementById('motivatie').value;
    var startdatum = document.getElementById('startdatum').value;

    var beschikbaarheidEl = document.querySelector('input[name="Beschikbaarheid"]:checked');
    var beschikbaarheid = beschikbaarheidEl ? beschikbaarheidEl.value : '';

    var vaardigheden = Array.from(
      document.querySelectorAll('input[name="vaardigheden"]:checked')
    ).map(function (cb) {
      return cb.value;
    });

    // 🔹 Basis validatie
    if (!naam || !email || !geboortedatum) {
      alert('Vul alle verplichte velden in.');
      return;
    }

    // 🔹 Geluksgetal berekenen
    var aantalLetters = naam.replace(/\s/g, '').length;
    var geboorteMaand = new Date(geboortedatum).getMonth() + 1;
    var randomGetal = Math.floor(Math.random() * 100) + 1;
    var berekening = (aantalLetters * geboorteMaand) / randomGetal;
    var geluksGetal = Math.round(berekening * 10) % 11;

    // 🔹 Alles in één object
    var data = {
      naam: naam,
      email: email,
      geboortedatum: geboortedatum,
      afdeling: afdeling,
      ervaring: ervaring,
      beschikbaarheid: beschikbaarheid,
      vaardigheden: vaardigheden,
      motivatie: motivatie,
      startdatum: startdatum,
      geluksGetal: geluksGetal
    };

    // 🔹 Opslaan voor mailtemplate
    localStorage.setItem('sollicitatie', JSON.stringify(data));

    // 🔹 Verzenden naar Make
    fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
    .then(function (response) {
      if (response.ok) {
        // ✅ Bij succes doorsturen
        window.location.href = 'mailtemplate.html';
      } else {
        alert('Versturen mislukt. Probeer later opnieuw.');
      }
    })
    .catch(function (error) {
      console.error('Fout:', error);
      alert('Fout bij versturen. Controleer je internetverbinding.');
    });

  });

})();
