const openBtn = document.getElementById("openEntryBtn");
const modal = document.getElementById("entryModal");
const closeBtn = document.getElementById("closeEntryBtn");
const saveBtn = document.getElementById("saveEntryBtn");
const container = document.getElementById("logbookEntries");

const STORAGE_KEY = "logboek_lukas_entries";

let editIndex = null; // Houd bij of we aan het bewerken zijn

/* ---------- MODAL ---------- */
openBtn.onclick = () => openModal();
closeBtn.onclick = () => closeModal();

function openModal(entry = null, index = null) {
    modal.classList.remove("hidden");
    if (entry) {
        entryDate.value = entry.date;
        entryTitle.value = entry.title;
        entryCategory.value = entry.category;
        entryHours.value = entry.hours;
        entryDescription.value = entry.description;
        entryTags.value = entry.tags;
        editIndex = index;
        saveBtn.textContent = "Bewerken";
    } else {
        clearForm();
        editIndex = null;
        saveBtn.textContent = "Opslaan";
    }
}

function closeModal() {
    modal.classList.add("hidden");
    clearForm();
    editIndex = null;
    saveBtn.textContent = "Opslaan";
}

/* ---------- LOAD STORED ENTRIES ---------- */
document.addEventListener("DOMContentLoaded", () => {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    stored.forEach((entry, index) => addEntryToDOM(entry, index));
});

/* ---------- SAVE OR EDIT ENTRY ---------- */
saveBtn.onclick = () => {
    const entry = {
        date: entryDate.value,
        title: entryTitle.value,
        category: entryCategory.value,
        hours: entryHours.value,
        description: entryDescription.value,
        tags: entryTags.value
    };

    if (!entry.date || !entry.title || !entry.hours) {
        alert("Vul minstens datum, titel en uren in");
        return;
    }

    if (editIndex !== null) {
        updateEntry(entry, editIndex);
    } else {
        addEntry(entry);
    }

    closeModal();
};

/* ---------- FUNCTIONS ---------- */
function addEntry(entry) {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    stored.push(entry);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
    addEntryToDOM(entry, stored.length - 1);
}

function updateEntry(entry, index) {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    stored[index] = entry;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
    refreshDOM();
}

function deleteEntry(index) {
    if (!confirm("Weet je zeker dat je deze entry wilt verwijderen?")) return;
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    stored.splice(index, 1);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
    refreshDOM();
}

/* ---------- DOM UPDATES ---------- */
function addEntryToDOM(entry, index) {
    const d = new Date(entry.date);
    const day = d.getDate();
    const month = d.toLocaleString("nl-BE", { month: "short" });

    const tagsHTML = entry.tags
        ? entry.tags.split(",").map(t => `<span>${t.trim()}</span>`).join("")
        : "";

    const html = document.createElement("div");
    html.classList.add("entry-card");
    html.innerHTML = `
        <div class="entry-header">
            <div class="entry-date">
                <span class="day">${day}</span>
                <span class="month">${month}</span>
            </div>
            <div class="entry-info">
                <h3>${entry.title}<span class="tag">${entry.category}</span></h3>
                <span class="hours">${parseFloat(entry.hours).toFixed(2)} uren</span>
                <span class="date">${d.toLocaleDateString("nl-BE")}</span>
                <p>${entry.description}</p>
                <div class="tags">${tagsHTML}</div>
            </div>
            <div class="entry-meta">
                <div class="entry-actions">
                    <i class="fa-solid fa-pen-to-square edit-btn"></i>
                    <i class="fa-solid fa-trash delete-btn"></i>
                </div>
            </div>
        </div>
    `;

    // Voeg eventlisteners toe voor edit & delete
    html.querySelector(".edit-btn").onclick = () => openModal(entry, index);
    html.querySelector(".delete-btn").onclick = () => deleteEntry(index);

    container.appendChild(html);
}

function refreshDOM() {
    container.innerHTML = "";
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    stored.forEach((entry, index) => addEntryToDOM(entry, index));
}

function clearForm() {
    entryDate.value = "";
    entryTitle.value = "";
    entryCategory.value = "";
    entryHours.value = "";
    entryDescription.value = "";
    entryTags.value = "";
}
