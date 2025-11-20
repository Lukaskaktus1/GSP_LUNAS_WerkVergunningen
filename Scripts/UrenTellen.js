const Tijden = document.querySelectorAll('.tijd');
let totaalUren = 0;

console.log('Aantal tijd elementen gevonden:', Tijden.length);

Tijden.forEach(tijdElement => {
    const tijdTekst = tijdElement.textContent.trim();
    console.log('Verwerken van tijd:', tijdTekst);
    const tijdDelen = tijdTekst.split(':');

    if (tijdDelen.length === 2) {
        const uren = parseInt(tijdDelen[0], 10);
        const minuten = parseInt(tijdDelen[1], 10);
        if (!isNaN(uren) && !isNaN(minuten)) {
            const totaalInUren = uren + (minuten / 60);
            totaalUren += totaalInUren;
            console.log(`Toegevoegd: ${totaalInUren} uren (Uren: ${uren}, Minuten: ${minuten})`);
        } else {
            console.warn('Ongeldige tijdswaarde, niet toegevoegd:', tijdTekst);
        }
    } else {
        console.warn('Ongeldig formaat, verwacht HH:MM:', tijdTekst);
    }
});

totaalUren = Math.round(totaalUren * 100) / 100;
console.log('Totaal uren berekend:', totaalUren);
const totaalElement = document.getElementById('totaal-uren');
if (totaalElement) {
    totaalElement.textContent = `Totaal Uren: ${totaalUren}`;
} else {
    console.error('Element met ID "totaal-uren" niet gevonden.');
}
