(function () {
    const app = window.PortfolioApp;

    if (!app) {
        return;
    }

    function pageName() {
        return (location.pathname.split("/").pop() || "index.html").toLowerCase();
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function setText(id, value) {
        const element = byId(id);

        if (element) {
            element.textContent = value;
        }
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function matchesSearch(element, query) {
        return element.textContent.toLowerCase().includes(query);
    }

    function filterElements(elements, query) {
        const normalized = query.trim().toLowerCase();

        elements.forEach((element) => {
            element.style.display = !normalized || matchesSearch(element, normalized) ? "" : "none";
        });
    }

    function setupHeaderSearch() {
        const input = document.querySelector(".header-bar .search-input");

        if (!input) {
            return;
        }

        input.addEventListener("input", () => {
            const selectors = [
                ".project-card",
                ".progress-row",
                ".entry-card",
                ".page-container",
                ".pomi-section",
                "main > section"
            ];
            const elements = selectors.flatMap((selector) => [...document.querySelectorAll(selector)]);
            filterElements([...new Set(elements)], input.value.toLowerCase());
        });
    }

    function projectBadge(project) {
        return `<span class="badge ${app.statusClass(project.status)}">${app.statusLabel(project.status)}</span>`;
    }

    function renderDashboard(entries, projects) {
        const stats = app.totals(entries, projects);

        setText("dashboard-active-projects", stats.activeProjects.length);
        setText("dashboard-active-projects-subtitle", `${stats.doneProjects.length} projecten afgerond`);
        setText("dashboard-total-hours", app.formatHours(stats.totalHours));
        setText("dashboard-total-hours-subtitle", "Automatisch uit logboek");
        setText("dashboard-total-entries", stats.totalEntries);
        setText("dashboard-total-entries-subtitle", `${stats.byPerson.lukas.entries} Lukas / ${stats.byPerson.jonas.entries} Jonas`);
        setText("dashboard-average-progress", `${stats.averageProgress}%`);
        setText("dashboard-average-progress-subtitle", `${stats.activeProjects.length} actief`);
        setText("lukas-hours", app.formatHours(stats.byPerson.lukas.hours));
        setText("jonas-hours", app.formatHours(stats.byPerson.jonas.hours));
        setText("lukas-entries", `${stats.byPerson.lukas.entries} entries`);
        setText("jonas-entries", `${stats.byPerson.jonas.entries} entries`);
        setText("team-total-hours", app.formatHours(stats.totalHours));

        const deadlines = document.querySelector(".deadlines-list");

        if (deadlines) {
            const active = stats.activeProjects.slice(0, 3);
            deadlines.innerHTML = active.length
                ? active.map((project, index) => `
                    <li class="deadline-item">
                        <span class="deadline-dot ${index === 0 ? "red" : ""}"></span>
                        <span class="deadline-text">${escapeHtml(project.title)} afronden</span>
                        <span class="deadline-time">${app.formatDate(project.dueDate)}</span>
                    </li>
                `).join("")
                : `
                    <li class="deadline-item">
                        <span class="deadline-dot"></span>
                        <span class="deadline-text">Alle projecten staan op afgerond</span>
                        <span class="deadline-time">Geen open deadline</span>
                    </li>
                `;
        }
    }

    function renderProjects(projects) {
        const list = document.querySelector(".projects-list");

        if (!list) {
            return;
        }

        list.innerHTML = projects.map((project) => `
            <article class="project-card" data-status="${project.status}" data-project="${project.id}">
                <header class="project-card__header">
                    <div class="project-card__title">
                        <h3><i class="fa-solid ${project.icon}"></i> ${escapeHtml(project.title)}</h3>
                        ${projectBadge(project)}
                    </div>
                    <button class="icon-btn" type="button" aria-label="Project opties"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                </header>

                <p class="project-card__subtitle">${escapeHtml(project.description)}</p>

                <ul class="meta-list">
                    <li><i class="fa-solid fa-list-check"></i> ${escapeHtml(project.category)}</li>
                    <li><i class="fa-solid fa-calendar-day"></i> Start ${app.formatDate(project.startDate)}</li>
                    <li><i class="fa-solid fa-clock"></i> ${app.formatHours(project.hours)}</li>
                    <li><i class="fa-solid fa-file-lines"></i> ${project.entries.length} entries</li>
                </ul>

                <div class="progress">
                    <div class="progress__bar" style="width: ${project.progress}%"></div>
                    <span class="progress__label">${project.progress}%</span>
                </div>

                <div class="project-section">
                    <h4>Belangrijke onderdelen</h4>
                    <div class="chip-row">
                        ${project.tags.map((tag) => `<span class="chip">${escapeHtml(tag)}</span>`).join("")}
                    </div>
                </div>

                <footer class="project-card__footer">
                    <span class="muted">Laatst gewijzigd: ${app.formatDate(project.latestDate)}</span>
                    <a class="secondary-btn" href="Logboek.html?project=${project.id}">Logboek bekijken</a>
                </footer>
            </article>
        `).join("");

        const button = document.querySelector(".primary-btn");

        if (button) {
            button.addEventListener("click", () => {
                location.href = "Logboek.html";
            });
        }
    }

    function renderProgress(projects) {
        const list = document.querySelector(".progress-list");

        if (!list) {
            return;
        }

        list.innerHTML = projects.map((project) => `
            <article class="progress-row ${project.status !== "not" ? "row-active" : ""}">
                <div class="row-left">
                    <h3 class="row-title">${escapeHtml(project.title)}</h3>
                    <p class="row-sub">${escapeHtml(project.description)}</p>
                    <p class="row-meta"><i class="fa-solid fa-calendar"></i> ${app.formatDate(project.startDate)} - ${app.formatDate(project.dueDate)}</p>
                </div>
                <div class="row-right">
                    <span class="status-badge ${app.progressStatusClass(project.status)}">${app.statusLabel(project.status)}</span>
                    <div class="mini-progress">
                        <div class="mini-progress__bar" style="width: ${project.progress}%"></div>
                    </div>
                    <span class="mini-percentage">${project.progress}%</span>
                </div>
            </article>
        `).join("");

        setText("progress-done-count", projects.filter((project) => project.status === "done").length);
        setText("progress-active-count", projects.filter((project) => project.status === "inprogress" || project.status === "almost").length);
        setText("progress-not-count", projects.filter((project) => project.status === "not").length);

        const button = document.querySelector(".primary-btn");

        if (button) {
            button.addEventListener("click", () => {
                location.href = "Logboek.html";
            });
        }
    }

    function renderTrackingCards(entries, projects) {
        const stats = app.totals(entries, projects);
        const cards = [...document.querySelectorAll(".content-section .card")];
        const values = [
            [app.formatHours(stats.totalHours), "Alle logboek entries"],
            [app.formatHours(stats.byPerson.lukas.hours), "Lukas Vandenweyer"],
            [app.formatHours(stats.byPerson.jonas.hours), "Jonas De Meersman"],
            [stats.activeProjects.length, "Actieve projecten"],
            [Object.keys(app.PEOPLE).length, "Teamleden actief"]
        ];

        cards.slice(0, values.length).forEach((card, index) => {
            const valueElement = card.querySelector(".hours, .aantal");
            const subtitleElement = card.querySelector(".content");

            if (valueElement) {
                valueElement.textContent = values[index][0];
            }

            if (subtitleElement) {
                subtitleElement.textContent = values[index][1];
            }
        });
    }

    function entryProjectLabel(entry) {
        return app.PROJECT_DEFINITIONS.find((project) => project.id === entry.projectId)?.title || "Project";
    }

    function renderTrackingEntries(entries, mode = "entries") {
        const list = document.querySelector(".entries-list");

        if (!list) {
            return;
        }

        if (mode === "projects") {
            const projects = app.buildProjects(entries);
            list.innerHTML = projects.map((project) => `
                <div class="entry-blue">
                    <div class="entry-left">
                        <div class="entry-titel">
                            <span class="entry-details">
                                <h3>${escapeHtml(project.title)}</h3>
                                <p class="soort_entry_intern">${app.statusLabel(project.status)}</p>
                                <p class="naam-green">${project.entries.length} entries</p>
                            </span>
                        </div>
                        <div class="entry-info">
                            <p>${escapeHtml(project.description)}</p>
                        </div>
                        <div class="entry-time">
                            <span class="entry-details">
                                <i class="fa-solid fa-calendar-week"></i>
                                <p>${app.formatDate(project.latestDate)}</p>
                                <p>${project.progress}% klaar</p>
                            </span>
                        </div>
                    </div>
                    <div class="entry-right">
                        <p class="entry-right-blue">${app.formatHoursPlain(project.hours)}</p>
                    </div>
                </div>
            `).join("");
            return;
        }

        if (mode === "weekly") {
            const grouped = entries.reduce((groups, entry) => {
                const date = app.parseDate(entry.date);
                const key = date ? `${date.getFullYear()}-week-${Math.ceil((((date - new Date(date.getFullYear(), 0, 1)) / 86400000) + 1) / 7)}` : "zonder datum";
                groups[key] ||= [];
                groups[key].push(entry);
                return groups;
            }, {});

            list.innerHTML = Object.entries(grouped).map(([week, weekEntries]) => {
                const hours = weekEntries.reduce((sum, entry) => sum + entry.hours, 0);
                return `
                    <div class="entry-blue">
                        <div class="entry-left">
                            <div class="entry-titel">
                                <span class="entry-details">
                                    <h3>${escapeHtml(week)}</h3>
                                    <p class="soort_entry_intern">Weekoverzicht</p>
                                    <p class="naam-green">${weekEntries.length} entries</p>
                                </span>
                            </div>
                            <div class="entry-info">
                                <p>${weekEntries.map((entry) => escapeHtml(entry.title)).slice(0, 4).join(", ")}</p>
                            </div>
                        </div>
                        <div class="entry-right">
                            <p class="entry-right-blue">${app.formatHoursPlain(hours)}</p>
                        </div>
                    </div>
                `;
            }).join("");
            return;
        }

        list.innerHTML = entries.slice().reverse().map((entry) => `
            <div class="${entry.personId === "jonas" ? "entry-blue" : "entry-green"}">
                <div class="entry-left">
                    <div class="entry-titel">
                        <span class="entry-details">
                            <h3>${escapeHtml(entry.title)}</h3>
                            <p class="${entry.hours >= 2 ? "soort_entry_declarabel" : "soort_entry_intern"}">${escapeHtml(entry.category)}</p>
                            <p class="naam-green">${escapeHtml(entry.person)}</p>
                        </span>
                    </div>
                    <div class="entry-info">
                        <p>${escapeHtml(entry.description)}</p>
                    </div>
                    <div class="entry-time">
                        <span class="entry-details">
                            <i class="fa-solid fa-calendar-week"></i>
                            <p>${app.formatDate(entry.date)}</p>
                            <p>${escapeHtml(entryProjectLabel(entry))}</p>
                        </span>
                    </div>
                </div>
                <div class="entry-right">
                    <p class="${entry.personId === "jonas" ? "entry-right-blue" : "entry-right-green"}">${app.formatHoursPlain(entry.hours)}</p>
                </div>
            </div>
        `).join("");
    }

    function setupTracking(entries, projects) {
        renderTrackingCards(entries, projects);
        renderTrackingEntries(entries);

        const buttons = {
            entries: document.querySelector(".entries-buttons .uren"),
            weekly: document.querySelector(".entries-buttons .overzicht"),
            projects: document.querySelector(".entries-buttons .projecten")
        };

        Object.entries(buttons).forEach(([mode, button]) => {
            if (!button) {
                return;
            }

            button.addEventListener("click", () => {
                Object.values(buttons).forEach((item) => item?.classList.remove("active"));
                button.classList.add("active");
                renderTrackingEntries(entries, mode);
            });
        });
    }

    function createEntryCard(entry) {
        const date = app.parseDate(entry.date);
        const day = date ? date.getDate() : "";
        const month = date ? app.monthNames[date.getMonth()] : "";
        const tags = (entry.tags || []).map((tag) => `<span>${escapeHtml(tag)}</span>`).join("");
        const actions = entry.editable
            ? `
                <div class="entry-actions">
                    <button class="entry-action-btn" type="button" data-edit-entry="${entry.id}" aria-label="Entry bewerken"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button class="entry-action-btn" type="button" data-delete-entry="${entry.id}" aria-label="Entry verwijderen"><i class="fa-solid fa-trash"></i></button>
                </div>
            `
            : "";

        return `
            <div class="entry-card" data-entry-id="${entry.id}" data-project="${entry.projectId}" data-category="${escapeHtml(entry.category)}">
                <div class="entry-header">
                    <div class="entry-date">
                        <span class="day">${day}</span>
                        <span class="month">${month}</span>
                    </div>
                    <div class="entry-info">
                        <h3>${escapeHtml(entry.title)}<span class="tag development">${escapeHtml(entry.category)}</span></h3>
                        <span class="hours">${Number(entry.hours).toFixed(2)} uren</span>
                        <span class="date">${app.formatDate(entry.date)}</span>
                        <p>${escapeHtml(entry.description)}</p>
                        <div class="tags">${tags}</div>
                    </div>
                    <div class="entry-meta">${actions}</div>
                </div>
            </div>
        `;
    }

    function fillSelect(select, options, placeholder) {
        if (!select) {
            return;
        }

        select.innerHTML = `<option value="">${placeholder}</option>${options.map((option) => {
            if (typeof option === "string") {
                return `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`;
            }

            return `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`;
        }).join("")}`;
    }

    function setupLogbook(entries) {
        const container = document.querySelector(".logbook-entries");

        if (!container) {
            return;
        }

        const personId = app.currentPersonId();
        const personEntries = entries.filter((entry) => entry.personId === personId);
        const search = document.querySelector(".search-logbook");
        const projectSelect = document.querySelector(".filter-project");
        const categorySelect = document.querySelector(".filter-category");
        const modal = byId("entryModal");
        const form = byId("entryForm");
        const title = byId("entryTitle");
        const date = byId("entryDate");
        const category = byId("entryCategory");
        const project = byId("entryProject");
        const hours = byId("entryHours");
        const description = byId("entryDescription");
        const tags = byId("entryTags");
        const editId = byId("entryEditId");

        fillSelect(projectSelect, app.projectOptions(), "Alle projecten");
        fillSelect(categorySelect, app.categoryOptions(personEntries), "Alle categorieen");
        fillSelect(project, app.projectOptions(), "Kies project");

        const initialProject = new URLSearchParams(location.search).get("project");
        if (initialProject && projectSelect) {
            projectSelect.value = initialProject;
        }

        function render() {
            const query = (search?.value || "").toLowerCase();
            const projectFilter = projectSelect?.value || "";
            const categoryFilter = categorySelect?.value || "";
            const filtered = personEntries.filter((entry) => {
                const haystack = `${entry.title} ${entry.category} ${entry.description} ${(entry.tags || []).join(" ")}`.toLowerCase();
                return (!query || haystack.includes(query)) &&
                    (!projectFilter || entry.projectId === projectFilter) &&
                    (!categoryFilter || entry.category === categoryFilter);
            });

            container.innerHTML = filtered.map(createEntryCard).join("") || `<p class="empty-state">Geen entries gevonden.</p>`;
        }

        render();

        [search, projectSelect, categorySelect].forEach((control) => {
            control?.addEventListener("input", render);
            control?.addEventListener("change", render);
        });

        document.querySelector(".filter-btn")?.addEventListener("click", () => {
            if (search) search.value = "";
            if (projectSelect) projectSelect.value = "";
            if (categorySelect) categorySelect.value = "";
            render();
        });

        document.querySelector(".new-entry-btn")?.addEventListener("click", () => {
            form?.reset();
            if (editId) editId.value = "";
            if (modal) modal.classList.remove("hidden");
        });

        byId("closeEntryBtn")?.addEventListener("click", () => {
            modal?.classList.add("hidden");
        });

        form?.addEventListener("submit", (event) => {
            event.preventDefault();

            const payload = {
                personId,
                title: title.value,
                date: date.value,
                category: category.value,
                projectId: project.value,
                hours: hours.value,
                description: description.value,
                tags: tags.value
            };

            if (editId?.value) {
                app.updateEntry(editId.value, payload);
            } else {
                app.addEntry(payload);
            }

            modal?.classList.add("hidden");
            location.reload();
        });

        container.addEventListener("click", (event) => {
            const editButton = event.target.closest("[data-edit-entry]");
            const deleteButton = event.target.closest("[data-delete-entry]");

            if (editButton) {
                const entry = entries.find((item) => item.id === editButton.dataset.editEntry);

                if (!entry || !entry.editable) {
                    return;
                }

                if (editId) editId.value = entry.id;
                title.value = entry.title;
                date.value = entry.date;
                category.value = entry.category;
                project.value = entry.projectId;
                hours.value = entry.hours;
                description.value = entry.description;
                tags.value = (entry.tags || []).join(", ");
                modal?.classList.remove("hidden");
            }

            if (deleteButton) {
                app.deleteEntry(deleteButton.dataset.deleteEntry);
                location.reload();
            }
        });
    }

    async function refresh() {
        const entries = await app.getEntries();
        const projects = app.buildProjects(entries);
        const page = pageName();

        setupHeaderSearch();

        if (page === "index.html" || page === "") {
            renderDashboard(entries, projects);
        }

        if (page === "projecten.html") {
            renderProjects(projects);
        }

        if (page === "voortgang.html") {
            renderProgress(projects);
        }

        if (page === "uren_tracking.html") {
            setupTracking(entries, projects);
        }

        if (page === "logboek.html" || page === "logboek2.html") {
            setupLogbook(entries);
        }
    }

    document.addEventListener("DOMContentLoaded", refresh);
})();
