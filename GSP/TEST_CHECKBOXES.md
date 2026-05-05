# TEST: Checkbox Value Attributes - Validatie

## Checkboxgroepen gecontroleerd en gefixed

### VAK2 - Activiteiten

#### II.1 ACTIVITEITEN KOUD (group: koud_*)
- Storage key: `vak2_act_koud`
- Checkboxes: 18 items
- Value range: "2"-"18", "99" (andere)
- Status: ✅ FIXED - Alle hebben value attributes

#### II.2 ACTIVITEITEN WARM (group: warm_*)
- Storage key: `vak2_act_warm`
- Checkboxes: 8 items
- Value range: "2"-"8", "99" (andere)
- Status: ✅ FIXED - Alle hebben value attributes

#### II.3 VERVOER EN MACHINES (group: vervoer_*)
- Storage key: `vak2_vervoer`
- Checkboxes: 13 items
- Value range: "1"-"12", "99" (andere)
- Status: ✅ FIXED - Alle hebben value attributes

#### II.4 SCHADELIJKE STOFFEN (group: stoffen_*)
- Storage key: `vak2_stoffen`
- Checkboxes: 8 items
- Value range: "2"-"8", "99" (andere)
- Status: ✅ FIXED - Alle hebben value attributes

#### II.5 CHEMICALIEN (group: chem_*)
- Storage key: `vak2_chemicalien`
- Checkboxes: 10 items
- Value range: "1"-"10"
- Status: ✅ FIXED - Alle hebben value attributes

### VAK5 - Vergunningen & Toelatingen

#### ANDERE VERGUNNINGEN (group: verg_*)
- Storage key: `vak5_vergunningen`
- Checkboxes: 9 items
- Value range: "2"-"10"
- Status: ✅ FIXED - Alle hebben value attributes

#### BIJKOMENDE TOELATINGEN (group: toel_*)
- Storage key: `vak5_toelatingen`
- Checkboxes: 7 items
- Value range: "2"-"7", "99" (andere)
- Status: ✅ FIXED - Alle hebben value attributes

### PREVENTIE - Preventiemaatregelen

#### HUID (group: huid_*)
- Storage key: `vak5_preventie`
- Checkboxes: 4 items
- Value range: "1"-"3", "99"
- Status: ✅ FIXED - Alle hebben value attributes

#### OGEN/OREN (group: ogen_*, oren_*)
- Checkboxes: 5 items
- Value range: "1"-"4", "99"
- Status: ✅ FIXED - Alle hebben value attributes

#### HAND/VOETEN (group: hand_*)
- Checkboxes: 5 items
- Value range: "1"-"4", "99"
- Status: ✅ FIXED - Alle hebben value attributes

#### ADEMHALING (group: adem_*)
- Checkboxes: 6 items
- Value range: "1"-"6"
- Status: ✅ FIXED - Alle hebben value attributes

#### VALLEN (group: vallen_*)
- Checkboxes: 6 items
- Value range: "1"-"5", "99"
- Status: ✅ FIXED - Alle hebben value attributes

#### COMMUNICATIE (group: comm_*)
- Checkboxes: 7 items
- Value range: "1"-"6", "99"
- Status: ✅ FIXED - Alle hebben value attributes

#### ANDERE (group: andere_*)
- Checkboxes: 5 items
- Value range: "1"-"4", "99"
- Status: ✅ FIXED - Alle hebben value attributes

#### MILIEU (group: milieu_*)
- Checkboxes: 7 items
- Value range: "1"-"6", "99"
- Status: ✅ FIXED - Alle hebben value attributes

## JavaScript-aanpassing

### saveCurrentVak.js
**Functie: saveCheckboxGroup()**
- Oude logica: `cb.value || cb.nextElementSibling?.textContent?.trim() || 'Ja'`
- Nieuwe logica: `cb.value` (enkel value, geen fallback naar "on" of label text)
- Status: ✅ FIXED - Zal nu ALTIJD de database ID opslaan, niet "on"

## Resultaat

### Wat nu correct opgeslagen wordt in sessionStorage:
```javascript
// Voorbeeld van de nu correcte waarden:
vak2_act_koud = ["2", "3"]              // Database IDs i.p.v. "on"
vak2_act_warm = ["4"]
vak2_vervoer = ["8"]
vak2_stoffen = ["2"]
vak2_chemicalien = ["1", "4"]
vak5_vergunningen = ["2", "7"]          // IDs voor betreding, loto
vak5_toelatingen = ["3"]                // ID voor hijsen
vak5_preventie = ["1","5","7"]          // IDs voor HUID, ADEMHALING, ANDERE
```

### Groepen die nu correct IDs opslaan:
- ✅ vak2_act_koud
- ✅ vak2_act_warm
- ✅ vak2_vervoer
- ✅ vak2_stoffen
- ✅ vak2_chemicalien
- ✅ vak5_vergunningen (voor alle verg_* groepen)
- ✅ vak5_toelatingen (voor alle toel_* groepen)
- ✅ vak5_preventie (voor ALLE preventie categorieën)

### Groepen die nog handmatig gecontroleerd moeten worden:
- GEEN - Alle checkboxgroepen zijn nu gefixed!

## Volgende stappen (NOT DONE - alleen als requested):
1. Koppeltabel-inserts in aanvraag_opslaan.php (nog niet gestart)
2. Database opslag van sessionStorage values (nog niet gestart)
3. Validatie van relationeel model (nog niet gestart)
