function Geluksspel() {

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

var geluksGetal = Math.round(resultaat * 10) % 11; // Getal tussen 0-10

// 5. Winnaar als geluksGetal >= 8
var gewonnen = (geluksGetal >= 8);
// 6. Toon resultaat
if (gewonnen) {
 document.getElementById('resultaat').innerHTML = 'Gefeliciteerd! Je won!';
} else {
 document.getElementById('resultaat').innerHTML = 'Jammer, volgende keer!';
}

document.getElementById("geluksgetal_input").value = geluksGetal;
document.getElementById("resultaat_input").value = gewonnen ? "Gefeliciteerd! Je won!" : "Jammer, volgende keer!";


}

function speelGelukspel() {


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
var geluksGetal = Math.round(resultaat * 10) % 11; // Getal tussen 0-10
// 5. Winnaar als geluksGetal >= 8
var gewonnen = (geluksGetal >= 8);
// 6. Toon resultaat
if (gewonnen) {
 document.getElementById('resultaat').innerHTML = 'Gefeliciteerd! Je won!';
} else {
 document.getElementById('resultaat').innerHTML = 'Jammer, volgende keer!';
}

document.getElementById('geluksgetal').textContent = geluksGetal;

document.getElementById("geluksgetal_input").value = geluksGetal;
document.getElementById("resultaat_input").value = gewonnen ? "Gefeliciteerd! Je won!" : "Jammer, volgende keer!";

}