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

var resultaatDiv = document.getElementById('resultaat');

if (gewonnen) {
    resultaatDiv.innerHTML = "Gefeliciteerd! Je won!";
    resultaatDiv.style.color = "green";
} else {
    resultaatDiv.innerHTML = "Jammer, volgende keer!";
    resultaatDiv.style.color = "red";
}

// animatie resetten
resultaatDiv.classList.remove("active");
resultaatDiv.classList.add("slide-in");

setTimeout(function() {
    resultaatDiv.classList.add("active");
}, 10);



}