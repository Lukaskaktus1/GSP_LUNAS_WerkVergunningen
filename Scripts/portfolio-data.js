(function () {
    const PEOPLE = {
        lukas: {
            name: "Lukas Vandenweyer",
            file: "Logboek.html"
        },
        jonas: {
            name: "Jonas De Meersman",
            file: "Logboek2.html"
        }
    };

    const STORAGE_KEY = "gsp_portfolio_logbook_entries_v1";

    const PROJECT_DEFINITIONS = [
        {
            id: "planning",
            title: "Planning & tijdlijn",
            icon: "fa-calendar-check",
            description: "Planning, deadlines, projectdefinitie en tijdlijn voor het GSP-project.",
            category: "Planning",
            tags: ["planning", "tijdlijn", "deadlines"],
            targetHours: 8,
            statusOverride: "done",
            startDate: "2025-09-02",
            dueDate: "2025-10-23"
        },
        {
            id: "database",
            title: "Database & normalisatie",
            icon: "fa-database",
            description: "ERD, normalisatie, SQL-tabellen en databankstructuur voor werkvergunningen.",
            category: "Database",
            tags: ["database", "sql", "erd"],
            targetHours: 18,
            statusOverride: "done",
            startDate: "2026-03-10",
            dueDate: "2026-04-28"
        },
        {
            id: "frontend",
            title: "Portfolio & frontend",
            icon: "fa-desktop",
            description: "HTML/CSS-pagina's, dashboard, responsive opmaak en overzichtsschermen.",
            category: "Frontend",
            tags: ["html", "css", "frontend", "portfolio"],
            targetHours: 34,
            statusOverride: "inprogress",
            startDate: "2025-09-29",
            dueDate: "2026-06-10"
        },
        {
            id: "backend",
            title: "Backend & accounts",
            icon: "fa-server",
            description: "Login, registratie, API-koppelingen en verbinding met de database.",
            category: "Backend",
            tags: ["backend", "auth", "api", "login"],
            targetHours: 16,
            statusOverride: "inprogress",
            startDate: "2026-05-05",
            dueDate: "2026-06-03"
        },
        {
            id: "gsp",
            title: "GSP werkvergunningen",
            icon: "fa-file-signature",
            description: "Digitale werkvergunningen, rollen, aanvragen, keuringen en statusopvolging.",
            category: "GSP",
            tags: ["gsp", "werkvergunning", "php", "testen"],
            targetHours: 42,
            statusOverride: "inprogress",
            startDate: "2026-05-06",
            dueDate: "2026-06-15"
        },
        {
            id: "documentatie",
            title: "Documentatie & SEO",
            icon: "fa-pen-nib",
            description: "Projectdocumentatie, SEO, toegankelijkheid en opleverbestanden.",
            category: "Documentatie",
            tags: ["documentatie", "seo", "optimalisatie"],
            targetHours: 8,
            statusOverride: "done",
            startDate: "2026-05-07",
            dueDate: "2026-05-18"
        },
        {
            id: "beatforge",
            title: "BeatForge Studio",
            icon: "fa-music",
            description: "Interactieve beatmaker met step sequencer, samples, mixer en piano roll.",
            category: "Audio",
            tags: ["beatforge", "studio", "muziek", "javascript"],
            targetHours: 10,
            statusOverride: "done",
            startDate: "2026-05-20",
            dueDate: "2026-06-08",
            url: "BeatForge-Studio/index.html"
        }
    ];

    const monthNames = ["jan", "feb", "mrt", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec"];
    let baseEntriesCache = null;

    function formatHours(hours) {
        return `${Number(hours || 0).toFixed(2).replace(".", ",")}u`;
    }

    function formatHoursPlain(hours) {
        return `${Number(hours || 0).toFixed(2)}h`;
    }

    function formatDate(dateInput) {
        const date = dateInput instanceof Date ? dateInput : parseDate(dateInput);

        if (!date) {
            return "Geen datum";
        }

        return date.toLocaleDateString("nl-BE");
    }

    function parseDate(value) {
        if (!value) {
            return null;
        }

        if (value instanceof Date && !Number.isNaN(value.getTime())) {
            return value;
        }

        const text = String(value).trim();
        const iso = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);

        if (iso) {
            return new Date(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3]));
        }

        const european = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

        if (european) {
            return new Date(Number(european[3]), Number(european[2]) - 1, Number(european[1]));
        }

        const parsed = new Date(text);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function toIsoDate(value) {
        const date = parseDate(value);

        if (!date) {
            return "";
        }

        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${date.getFullYear()}-${month}-${day}`;
    }

    function normalize(text) {
        return String(text || "").toLowerCase();
    }

    function cleanText(text) {
        return String(text || "").replace(/\s+/g, " ").trim();
    }

    function textFromTitle(titleElement) {
        if (!titleElement) {
            return "Logboek entry";
        }

        const clone = titleElement.cloneNode(true);
        clone.querySelectorAll(".tag").forEach((tag) => tag.remove());
        return cleanText(clone.textContent) || "Logboek entry";
    }

    function classifyProject(entry) {
        const haystack = normalize([
            entry.title,
            entry.category,
            entry.description,
            ...(entry.tags || [])
        ].join(" "));

        if (haystack.includes("planning") || haystack.includes("tijdlijn") || haystack.includes("deadline") || haystack.includes("project definitie")) {
            return "planning";
        }

        if (haystack.includes("database") || haystack.includes("sql") || haystack.includes("erd") || haystack.includes("normalisatie")) {
            return "database";
        }

        if (haystack.includes("backend") || haystack.includes("login") || haystack.includes("register") || haystack.includes("auth") || haystack.includes("api")) {
            return "backend";
        }

        if (haystack.includes("werkvergunning") || haystack.includes("vergunning") || haystack.includes("gsp") || haystack.includes("php") || haystack.includes("testen")) {
            return "gsp";
        }

        if (haystack.includes("documentatie") || haystack.includes("seo") || haystack.includes("optimalisatie")) {
            return "documentatie";
        }

        if (haystack.includes("beatforge") || haystack.includes("studio") || haystack.includes("muziek") || haystack.includes("audio") || haystack.includes("beatmaker")) {
            return "beatforge";
        }

        if (haystack.includes("website") || haystack.includes("frontend") || haystack.includes("html") || haystack.includes("css") || haystack.includes("portfolio") || haystack.includes("contact") || haystack.includes("responsive") || haystack.includes("overzicht")) {
            return "frontend";
        }

        return "gsp";
    }

    function parseLogbookDocument(doc, personId, source) {
        const person = PEOPLE[personId];
        const cards = [...doc.querySelectorAll(".logbook-entries .entry-card")];

        return cards.map((card, index) => {
            const titleElement = card.querySelector(".entry-info h3");
            const tagElement = card.querySelector(".entry-info .tag");
            const dateText = cleanText(card.querySelector(".entry-info .date")?.textContent);
            const hoursText = cleanText(card.querySelector(".entry-info .hours")?.textContent);
            const description = cleanText(card.querySelector(".entry-info p")?.textContent);
            const tags = [...card.querySelectorAll(".entry-info .tags span")]
                .map((tag) => cleanText(tag.textContent))
                .filter(Boolean);
            const hours = parseFloat(hoursText.replace(",", "."));
            const entry = {
                id: `${source}-${personId}-${index}`,
                source,
                personId,
                person: person.name,
                title: textFromTitle(titleElement),
                category: cleanText(tagElement?.textContent) || "Algemeen",
                hours: Number.isNaN(hours) ? 0 : hours,
                date: toIsoDate(dateText),
                dateLabel: dateText,
                description,
                tags,
                editable: source === "local"
            };

            entry.projectId = classifyProject(entry);
            return entry;
        });
    }

    async function fetchDocument(url) {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Kon ${url} niet openen`);
        }

        const html = await response.text();
        return new DOMParser().parseFromString(html, "text/html");
    }

    function currentPersonId() {
        const file = location.pathname.split("/").pop().toLowerCase();
        return file === "logboek2.html" ? "jonas" : "lukas";
    }

    function localEntries() {
        try {
            const entries = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
            return entries.map((entry, index) => ({
                ...entry,
                id: entry.id || `local-${index}`,
                editable: true,
                source: "local",
                person: PEOPLE[entry.personId]?.name || entry.person || "Onbekend",
                projectId: entry.projectId || classifyProject(entry),
                tags: Array.isArray(entry.tags) ? entry.tags : String(entry.tags || "").split(",").map((tag) => tag.trim()).filter(Boolean),
                hours: Number(entry.hours) || 0,
                date: toIsoDate(entry.date)
            }));
        } catch (error) {
            return [];
        }
    }

    function saveLocalEntries(entries) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(entries));
        window.dispatchEvent(new CustomEvent("portfolio-data-updated"));
    }

    async function getEntries() {
        if (baseEntriesCache) {
            return [...baseEntriesCache, ...localEntries()].sort((a, b) => {
                const dateA = parseDate(a.date)?.getTime() || 0;
                const dateB = parseDate(b.date)?.getTime() || 0;
                return dateA - dateB;
            });
        }

        const entries = [];
        const currentLogbook = document.querySelector(".logbook-entries");
        const file = location.pathname.split("/").pop().toLowerCase();

        for (const personId of Object.keys(PEOPLE)) {
            const person = PEOPLE[personId];

            if (
                currentLogbook &&
                ((personId === "lukas" && file !== "logboek2.html") || (personId === "jonas" && file === "logboek2.html"))
            ) {
                entries.push(...parseLogbookDocument(document, personId, "base"));
                continue;
            }

            try {
                const doc = await fetchDocument(person.file);
                entries.push(...parseLogbookDocument(doc, personId, "base"));
            } catch (error) {
                const fallback = personId === "lukas"
                    ? { hours: 77.17, count: 36 }
                    : { hours: 64.67, count: 31 };

                entries.push({
                    id: `fallback-${personId}`,
                    source: "fallback",
                    personId,
                    person: person.name,
                    title: "Bestaande logboekdata",
                    category: "Logboek",
                    hours: fallback.hours,
                    date: "",
                    dateLabel: "",
                    description: `${fallback.count} bestaande entries`,
                    tags: ["fallback"],
                    projectId: "gsp",
                    editable: false
                });
            }
        }

        baseEntriesCache = entries;

        return [...baseEntriesCache, ...localEntries()].sort((a, b) => {
            const dateA = parseDate(a.date)?.getTime() || 0;
            const dateB = parseDate(b.date)?.getTime() || 0;
            return dateA - dateB;
        });
    }

    function addEntry(entry) {
        const stored = localEntries();
        const normalized = {
            id: `local-${Date.now()}`,
            source: "local",
            personId: entry.personId,
            person: PEOPLE[entry.personId]?.name || entry.person,
            title: cleanText(entry.title),
            category: cleanText(entry.category) || "Algemeen",
            hours: Number(entry.hours) || 0,
            date: toIsoDate(entry.date),
            description: cleanText(entry.description),
            tags: Array.isArray(entry.tags) ? entry.tags : String(entry.tags || "").split(",").map((tag) => tag.trim()).filter(Boolean)
        };

        normalized.projectId = entry.projectId || classifyProject(normalized);
        stored.push(normalized);
        saveLocalEntries(stored);
        return normalized;
    }

    function deleteEntry(id) {
        saveLocalEntries(localEntries().filter((entry) => entry.id !== id));
    }

    function updateEntry(id, updatedEntry) {
        const stored = localEntries().map((entry) => {
            if (entry.id !== id) {
                return entry;
            }

            const nextEntry = {
                ...entry,
                ...updatedEntry,
                hours: Number(updatedEntry.hours) || 0,
                date: toIsoDate(updatedEntry.date),
                tags: Array.isArray(updatedEntry.tags) ? updatedEntry.tags : String(updatedEntry.tags || "").split(",").map((tag) => tag.trim()).filter(Boolean)
            };

            nextEntry.projectId = updatedEntry.projectId || classifyProject(nextEntry);
            return nextEntry;
        });

        saveLocalEntries(stored);
    }

    function statusForProgress(progress) {
        if (progress >= 100) {
            return "done";
        }

        if (progress >= 85) {
            return "almost";
        }

        if (progress > 0) {
            return "inprogress";
        }

        return "not";
    }

    function buildProjects(entries) {
        return PROJECT_DEFINITIONS.map((project) => {
            const projectEntries = entries.filter((entry) => entry.projectId === project.id);
            const hours = projectEntries.reduce((sum, entry) => sum + entry.hours, 0);
            const latestEntry = projectEntries.reduce((latest, entry) => {
                const date = parseDate(entry.date);
                const latestDate = parseDate(latest?.date);
                return !latest || (date && (!latestDate || date > latestDate)) ? entry : latest;
            }, null);
            const calculatedProgress = Math.min(100, Math.round((hours / project.targetHours) * 100));
            const progress = project.statusOverride === "done" ? 100 : calculatedProgress;
            const status = project.statusOverride === "done" ? "done" : statusForProgress(progress);

            return {
                ...project,
                entries: projectEntries,
                hours,
                progress,
                status,
                latestEntry,
                latestDate: latestEntry?.date || project.startDate
            };
        });
    }

    function statusLabel(status) {
        return {
            done: "Afgerond",
            almost: "Bijna klaar",
            inprogress: "In uitvoering",
            not: "Nog te starten"
        }[status] || "In uitvoering";
    }

    function statusClass(status) {
        return {
            done: "badge-success",
            almost: "badge-warning",
            inprogress: "badge-info",
            not: "badge-muted"
        }[status] || "badge-info";
    }

    function progressStatusClass(status) {
        return {
            done: "status-done",
            almost: "status-almost",
            inprogress: "status-inprogress",
            not: "status-not"
        }[status] || "status-inprogress";
    }

    function totals(entries, projects) {
        const totalHours = entries.reduce((sum, entry) => sum + entry.hours, 0);
        const byPerson = Object.fromEntries(Object.keys(PEOPLE).map((personId) => {
            const personEntries = entries.filter((entry) => entry.personId === personId);
            return [personId, {
                entries: personEntries.length,
                hours: personEntries.reduce((sum, entry) => sum + entry.hours, 0)
            }];
        }));
        const activeProjects = projects.filter((project) => project.status !== "done");
        const doneProjects = projects.filter((project) => project.status === "done");
        const averageProgress = projects.length
            ? Math.round(projects.reduce((sum, project) => sum + project.progress, 0) / projects.length)
            : 0;

        return {
            totalHours,
            totalEntries: entries.length,
            byPerson,
            activeProjects,
            doneProjects,
            averageProgress
        };
    }

    function projectOptions() {
        return PROJECT_DEFINITIONS.map((project) => ({
            value: project.id,
            label: project.title
        }));
    }

    function categoryOptions(entries) {
        return [...new Set(entries.map((entry) => entry.category).filter(Boolean))].sort();
    }

    window.PortfolioApp = {
        PEOPLE,
        PROJECT_DEFINITIONS,
        getEntries,
        addEntry,
        updateEntry,
        deleteEntry,
        buildProjects,
        totals,
        formatHours,
        formatHoursPlain,
        formatDate,
        parseDate,
        currentPersonId,
        projectOptions,
        categoryOptions,
        statusLabel,
        statusClass,
        progressStatusClass,
        monthNames,
        cleanText
    };
})();
