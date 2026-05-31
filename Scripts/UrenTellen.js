const hourEntries = [
    {
        title: "Planning start",
        type: "Intern",
        member: "Lukas Vandenweyer",
        project: "Planning",
        date: "2025-09-02",
        place: "School",
        work: "APP",
        hours: 1.67,
        description: "Start opstelling planning met tijdlijntool, individuele voorbereiding en klassikale evaluatie."
    },
    {
        title: "Planning verderwerken",
        type: "Intern",
        member: "Lukas Vandenweyer",
        project: "Planning",
        date: "2025-09-09",
        place: "School",
        work: "PROD",
        hours: 1.67,
        description: "Deadlines, vakantieperiodes, taken en subtaken toevoegen aan de tijdlijn."
    },
    {
        title: "Tijdlijn aanpassen",
        type: "Intern",
        member: "Lukas Vandenweyer",
        project: "Tijdlijn",
        date: "2025-09-30",
        place: "School",
        work: "PROD",
        hours: 1.67,
        description: "Site bijwerken en planning visueel afstemmen op de projectdeadlines."
    },
    {
        title: "Website layout",
        type: "Declarabel",
        member: "Lukas Vandenweyer",
        project: "Website",
        date: "2025-10-01",
        place: "Thuis",
        work: "HTML/CSS",
        hours: 2,
        description: "Basislayout van de portfolio-website verder uitwerken."
    },
    {
        title: "Website componenten",
        type: "Declarabel",
        member: "Lukas Vandenweyer",
        project: "Website",
        date: "2025-10-02",
        place: "Thuis",
        work: "HTML/CSS",
        hours: 2,
        description: "Kaarten en navigatiecomponenten verfijnen voor de projectpagina's."
    },
    {
        title: "Website styling",
        type: "Declarabel",
        member: "Lukas Vandenweyer",
        project: "Website",
        date: "2025-10-04",
        place: "Thuis",
        work: "HTML/CSS",
        hours: 3,
        description: "Responsieve styling en visuele consistentie bijwerken."
    },
    {
        title: "Website afwerking",
        type: "Declarabel",
        member: "Lukas Vandenweyer",
        project: "Website",
        date: "2025-10-05",
        place: "Thuis",
        work: "HTML/CSS",
        hours: 4,
        description: "Extra secties afwerken en inhoud klaarmaken voor review."
    },
    {
        title: "Project definitie",
        type: "Intern",
        member: "Lukas Vandenweyer",
        project: "Documentatie",
        date: "2025-10-23",
        place: "Thuis",
        work: "Project definitie",
        hours: 1.75,
        description: "Projectdefinitie aanvullen met scope, doelen en eerste afspraken."
    },
    {
        title: "Database analyse",
        type: "Intern",
        member: "Jonas De Meersman",
        project: "Database",
        date: "2025-10-24",
        place: "School",
        work: "ERD",
        hours: 2.5,
        description: "Datamodel controleren en tabellen afstemmen op de werkvergunningen-flow."
    },
    {
        title: "Werkvergunning prototype",
        type: "Declarabel",
        member: "Jonas De Meersman",
        project: "GSP",
        date: "2025-10-28",
        place: "School",
        work: "PHP",
        hours: 3.5,
        description: "Prototypepagina's voor het aanvragen van werkvergunningen uitbreiden."
    },
    {
        title: "Login en registratie",
        type: "Declarabel",
        member: "Jonas De Meersman",
        project: "GSP",
        date: "2025-10-31",
        place: "Thuis",
        work: "PHP/MySQL",
        hours: 4,
        description: "Authenticatie testen en foutmeldingen duidelijker maken."
    },
    {
        title: "Website content",
        type: "Declarabel",
        member: "Lukas Vandenweyer",
        project: "Website",
        date: "2025-11-01",
        place: "Thuis",
        work: "HTML/CSS",
        hours: 4,
        description: "Content en visuele details op de website aanvullen."
    },
    {
        title: "Aanvraagflow testen",
        type: "Intern",
        member: "Jonas De Meersman",
        project: "GSP",
        date: "2025-11-04",
        place: "School",
        work: "Testen",
        hours: 2.75,
        description: "Werkvergunningen doorlopen en fouten in de aanvraagflow noteren."
    }
];

const state = {
    activeTab: "entries",
    query: "",
    member: "all",
    type: "all"
};

const formatHours = (hours) => `${Number(hours.toFixed(2))}h`;

const formatDate = (dateString) => new Intl.DateTimeFormat("nl-BE", {
    day: "numeric",
    month: "short",
    year: "numeric"
}).format(new Date(dateString));

const getWeekKey = (dateString) => {
    const date = new Date(dateString);
    const firstDay = new Date(date.getFullYear(), 0, 1);
    const days = Math.floor((date - firstDay) / 86400000);
    return `Week ${Math.ceil((days + firstDay.getDay() + 1) / 7)}`;
};

const sumBy = (entries, key) => entries.reduce((result, entry) => {
    const group = entry[key];
    result[group] = (result[group] || 0) + entry.hours;
    return result;
}, {});

const uniqueValues = (key) => [...new Set(hourEntries.map((entry) => entry[key]))];

const getFilteredEntries = () => {
    const query = state.query.trim().toLowerCase();

    return hourEntries.filter((entry) => {
        const matchesQuery = !query || [
            entry.title,
            entry.type,
            entry.member,
            entry.project,
            entry.place,
            entry.work,
            entry.description
        ].some((value) => value.toLowerCase().includes(query));

        const matchesMember = state.member === "all" || entry.member === state.member;
        const matchesType = state.type === "all" || entry.type === state.type;

        return matchesQuery && matchesMember && matchesType;
    });
};

const renderStats = (entries) => {
    const totalHours = entries.reduce((total, entry) => total + entry.hours, 0);
    const declarableHours = entries
        .filter((entry) => entry.type === "Declarabel")
        .reduce((total, entry) => total + entry.hours, 0);
    const internalHours = totalHours - declarableHours;
    const projectCount = new Set(entries.map((entry) => entry.project)).size;

    const stats = [
        {
            label: "Totaal uren",
            value: formatHours(totalHours),
            helper: `${entries.length} geregistreerde entries`,
            icon: "fa-regular fa-clock"
        },
        {
            label: "Declarabel",
            value: formatHours(declarableHours),
            helper: `${Math.round((declarableHours / Math.max(totalHours, 1)) * 100)}% van de selectie`,
            icon: "fa-solid fa-file-invoice-dollar"
        },
        {
            label: "Intern",
            value: formatHours(internalHours),
            helper: "Planning, overleg en testen",
            icon: "fa-solid fa-clipboard-check"
        },
        {
            label: "Projecten",
            value: projectCount,
            helper: "Actieve onderdelen",
            icon: "fa-solid fa-chart-simple"
        }
    ];

    document.getElementById("statsGrid").innerHTML = stats.map((stat) => `
        <article class="stat-card glass-panel">
            <i class="${stat.icon}"></i>
            <h3>${stat.label}</h3>
            <strong>${stat.value}</strong>
            <p>${stat.helper}</p>
        </article>
    `).join("");
};

const renderEntries = (entries) => {
    if (entries.length === 0) {
        return `<div class="empty-state">Geen uren gevonden voor deze filters.</div>`;
    }

    return `
        <div class="hours-table-wrap">
            <table class="hours-table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Details</th>
                        <th>Type</th>
                        <th class="hours-column">Uren</th>
                    </tr>
                </thead>
                <tbody>
                    ${entries.map((entry) => `
                        <tr>
                            <td class="date-cell">${formatDate(entry.date)}</td>
                            <td class="details-cell">
                                <strong>${entry.title}</strong>
                                <span class="entry-table-description">${entry.description}</span>
                                <span class="entry-table-meta">
                                    ${entry.member} &bull; ${entry.project} &bull; ${entry.place} - ${entry.work}
                                </span>
                            </td>
                            <td>
                                <span class="badge ${entry.type === "Intern" ? "internal" : ""}">${entry.type}</span>
                            </td>
                            <td class="hours-column">${formatHours(entry.hours)}</td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
        <div class="entries-list compact-cards">
            ${entries.map((entry) => `
                <article class="entry-card">
                    <div class="entry-main">
                        <div class="entry-top">
                            <h4>${entry.title}</h4>
                            <span class="badge ${entry.type === "Intern" ? "internal" : ""}">${entry.type}</span>
                            <span class="badge">${entry.member}</span>
                        </div>
                        <p class="entry-description">${entry.description}</p>
                        <div class="entry-meta">
                            <span><i class="fa-solid fa-calendar-week"></i> ${formatDate(entry.date)}</span>
                            <span><i class="fa-solid fa-location-dot"></i> ${entry.place}</span>
                            <span><i class="fa-solid fa-diagram-project"></i> ${entry.project}</span>
                            <span><i class="fa-solid fa-screwdriver-wrench"></i> ${entry.work}</span>
                        </div>
                    </div>
                    <div class="entry-hours">${formatHours(entry.hours)}</div>
                </article>
            `).join("")}
        </div>
    `;
};

const renderOverview = (entries, groupKey) => {
    const groups = sumBy(entries, groupKey);
    const maxHours = Math.max(...Object.values(groups), 1);
    const sortedGroups = Object.entries(groups).sort((a, b) => b[1] - a[1]);

    if (sortedGroups.length === 0) {
        return `<div class="empty-state">Geen overzicht beschikbaar voor deze selectie.</div>`;
    }

    return `
        <div class="overview-list">
            ${sortedGroups.map(([name, hours]) => {
                const relatedEntries = entries.filter((entry) => entry[groupKey] === name);
                const projects = new Set(relatedEntries.map((entry) => entry.project)).size;
                const width = Math.round((hours / maxHours) * 100);

                return `
                    <article class="overview-card">
                        <header>
                            <span>${name}</span>
                            <strong>${formatHours(hours)}</strong>
                        </header>
                        <div class="progress-track">
                            <div class="progress-bar" style="width: ${width}%"></div>
                        </div>
                        <div class="overview-meta">
                            <span>${relatedEntries.length} entries</span>
                            <span>${projects} projecten</span>
                        </div>
                    </article>
                `;
            }).join("")}
        </div>
    `;
};

const renderWeeklyOverview = (entries) => {
    const entriesWithWeek = entries.map((entry) => ({
        ...entry,
        week: getWeekKey(entry.date)
    }));

    return renderOverview(entriesWithWeek, "week");
};

const renderSidePanel = (entries) => {
    const memberTotals = sumBy(entries, "member");
    const totalHours = entries.reduce((total, entry) => total + entry.hours, 0);
    const maxHours = Math.max(...Object.values(memberTotals), 1);
    const topProject = Object.entries(sumBy(entries, "project")).sort((a, b) => b[1] - a[1])[0];
    const topWeek = entries.length
        ? Object.entries(sumBy(entries.map((entry) => ({ ...entry, week: getWeekKey(entry.date) })), "week")).sort((a, b) => b[1] - a[1])[0]
        : null;

    document.getElementById("sidePanel").innerHTML = `
        <div>
            <p class="eyebrow">Team focus</p>
            <h3>Verdeling</h3>
        </div>
        <div class="overview-list">
            ${Object.entries(memberTotals).map(([member, hours]) => `
                <div class="member-row">
                    <div class="member-line">
                        <strong>${member.split(" ")[0]}</strong>
                        <span>${formatHours(hours)}</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-bar" style="width: ${Math.round((hours / maxHours) * 100)}%"></div>
                    </div>
                </div>
            `).join("") || `<div class="empty-state">Geen teamdata.</div>`}
        </div>
        <div class="mini-stat-grid">
            <div class="mini-stat">
                <span>Gemiddeld</span>
                <strong>${formatHours(totalHours / Math.max(entries.length, 1))}</strong>
            </div>
            <div class="mini-stat">
                <span>Beste project</span>
                <strong>${topProject ? topProject[0] : "-"}</strong>
            </div>
            <div class="mini-stat">
                <span>Drukste week</span>
                <strong>${topWeek ? topWeek[0] : "-"}</strong>
            </div>
            <div class="mini-stat">
                <span>Teamleden</span>
                <strong>${Object.keys(memberTotals).length}</strong>
            </div>
        </div>
    `;
};

const render = () => {
    const entries = getFilteredEntries();
    const content = document.getElementById("tabContent");
    const title = document.getElementById("viewTitle");

    renderStats(entries);
    renderSidePanel(entries);
    document.getElementById("resultCount").textContent = `${entries.length} entries`;

    if (state.activeTab === "weekly") {
        title.textContent = "Wekelijks overzicht";
        content.innerHTML = renderWeeklyOverview(entries);
    } else if (state.activeTab === "projects") {
        title.textContent = "Per project";
        content.innerHTML = renderOverview(entries, "project");
    } else {
        title.textContent = "Uren entries";
        content.innerHTML = renderEntries(entries);
    }
};

const initFilters = () => {
    const memberFilter = document.getElementById("memberFilter");
    memberFilter.insertAdjacentHTML("beforeend", uniqueValues("member").map((member) => (
        `<option value="${member}">${member}</option>`
    )).join(""));

    document.querySelectorAll(".tab-button").forEach((button) => {
        button.addEventListener("click", () => {
            state.activeTab = button.dataset.tab;
            document.querySelectorAll(".tab-button").forEach((tab) => {
                tab.classList.toggle("active", tab === button);
            });
            render();
        });
    });

    const entrySearch = document.getElementById("entrySearch");
    const globalSearch = document.getElementById("globalSearch");

    [entrySearch, globalSearch].forEach((input) => {
        input.addEventListener("input", () => {
            state.query = input.value;
            entrySearch.value = input.value;
            globalSearch.value = input.value;
            render();
        });
    });

    memberFilter.addEventListener("change", (event) => {
        state.member = event.target.value;
        render();
    });

    document.getElementById("typeFilter").addEventListener("change", (event) => {
        state.type = event.target.value;
        render();
    });
};

document.addEventListener("DOMContentLoaded", () => {
    initFilters();
    render();
});
