/**
 * Verstuurt sollicitatieformulier naar Make.com webhook (geen PHP).
 */
(function () {


  // 1. Tel het aantal letters in de volledige naam (zonder spaties)
var naam = document.getElementById('voornaam_achternaam').value;
var aantalLetters = naam.replace(/\s/g, '').length;

// 2. Haal de geboortemaand op (1-12)
var geboortedatum = document.getElementById('geboortedatum').value;
var geboorteMaand = new Date(geboortedatum).getMonth() + 1;

// 3. Genereer een random getal tussen 1 en 100
var randomGetal = Math.floor(Math.random() * 100) + 1;

// 4. Bereken het geluksgetal
var resultaat = (aantalLetters * geboorteMaand) / randomGetal;

var geluksGetalEl = Math.round(resultaat * 10) % 11; // Getal tussen 0-10




  'use strict';

  var WEBHOOK_URL = 'https://hook.eu1.make.com/gzecp6tfd7ddnimtu9xpc63ps67d2x8k';

  var form = document.getElementById('formulier');
  var naamEl = document.getElementById('voornaam_achternaam');
  var geboortedatumEl = document.getElementById('geboortedatum');
  var naamError = document.getElementById('naam-error');
  var geboortedatumError = document.getElementById('geboortedatum-error');
  var resultaatEl = document.getElementById('resultaat');

  function toonNaamError(msg) {
    if (naamError) {
      naamError.textContent = msg || '';
      naamEl.classList.toggle('error-field', !!msg);
    }
  }

  function toonGeboortedatumError(msg) {
    if (geboortedatumError) {
      geboortedatumError.textContent = msg || '';
      geboortedatumEl.classList.toggle('error-field', !!msg);
    }
  }

  function valideerNaam() {
    var val = (naamEl.value || '').trim();
    if (val.length === 0) {
      toonNaamError('Vul je voor- en achternaam in.');
      return false;
    }
    if (val.length < 2) {
      toonNaamError('Minimaal 2 karakters.');
      return false;
    }
    toonNaamError('');
    return true;
  }

  function valideerGeboortedatum() {
    var val = geboortedatumEl.value || '';
    if (!val) {
      toonGeboortedatumError('Vul je geboortedatum in.');
      return false;
    }
    toonGeboortedatumError('');
    return true;
  }

  function verzamelFormData() {
    var vaardighedenEls = document.querySelectorAll('input[name="vaardigheden"]:checked');
    var vaardigheden = [];
    for (var i = 0; i < vaardighedenEls.length; i++) {
      vaardigheden.push(vaardighedenEls[i].value);
    }
    return {
      naam: (naamEl.value || '').trim(),
      geboortedatum: geboortedatumEl.value || '',
      ervaring: document.getElementById('ervaring').value || '',
      vaardigheden: vaardigheden,
      startdatum: document.getElementById('startdatum').value || '',
      mail_naar: document.getElementById('mail-naar').value || '',
      geluksgetal: geluksGetalEl || ''
    };
  }

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!valideerNaam() || !valideerGeboortedatum()) return;

    var data = verzamelFormData();

    fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
      .then(function (res) {
        if (res.ok) {
          window.location.href = '/CASES/HTML/Mail_template.html';
        } else {
          alert('Versturen mislukt. Probeer later opnieuw.');
        }
      })
      .catch(function () {
        alert('Fout bij versturen. Controleer je internetverbinding.');
      });
  });
})();
