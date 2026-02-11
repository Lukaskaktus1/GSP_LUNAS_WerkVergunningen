/**
 * Verstuurt sollicitatieformulier naar Make.com webhook (geen PHP).
 */
(function () {
  'use strict';

  var WEBHOOK_URL = 'https://hook.eu2.make.com/vobhaj6rph4xzm4p8vp5ljasbwekfvua';

  var form = document.getElementById('sollicitatieformulier');
  var naamEl = document.getElementById('naam');
  var geboortedatumEl = document.getElementById('geboortedatum');
  var naamError = document.getElementById('naam-error');
  var geboortedatumError = document.getElementById('geboortedatum-error');
  var resultaatEl = document.getElementById('geluksspel-resultaat');

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
    var geluksgetalEl = document.getElementById('geluksgetal');
    return {
      naam: (naamEl.value || '').trim(),
      geboortedatum: geboortedatumEl.value || '',
      ervaring: document.getElementById('ervaring').value || '',
      vaardigheden: vaardigheden,
      startdatum: document.getElementById('startdatum').value || '',
      mail_naar: document.getElementById('mail-naar').value || '',
      geluksgetal: geluksgetalEl ? geluksgetalEl.value : ''
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
          window.location.href = 'bedankt.html';
        } else {
          alert('Versturen mislukt. Probeer later opnieuw.');
        }
      })
      .catch(function () {
        alert('Fout bij versturen. Controleer je internetverbinding.');
      });
  });
})();
