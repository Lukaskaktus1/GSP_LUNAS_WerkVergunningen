(function () {
    const consentKey = "adb-site-cookie-consent";
    const script = document.currentScript;
    const scriptSrc = script && script.src ? script.src : "";
    const siteRoot = scriptSrc.includes("/Scripts/")
        ? scriptSrc.slice(0, scriptSrc.indexOf("/Scripts/"))
        : window.location.origin;

    function siteUrl(path) {
        return siteRoot.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
    }

    function storageGet() {
        try {
            return localStorage.getItem(consentKey);
        } catch (error) {
            return null;
        }
    }

    function storageSet(value) {
        try {
            localStorage.setItem(consentKey, value);
        } catch (error) {
            return;
        }
    }

    function updateAnalyticsConsent(value) {
        if (typeof window.gtag !== "function") {
            return;
        }

        window.gtag("consent", "update", {
            analytics_storage: value === "granted" ? "granted" : "denied",
            ad_storage: "denied",
            ad_user_data: "denied",
            ad_personalization: "denied"
        });
    }

    function ensureFooter() {
        if (document.querySelector("[data-site-legal-footer]")) {
            return;
        }

        const footer = document.createElement("footer");
        footer.className = "legal-footer";
        footer.setAttribute("data-site-legal-footer", "");
        footer.innerHTML = `
            <div class="legal-footer__inner">
                <p>&copy; GTI Beveren 2025-2026 - GSP 6ADB</p>
                <nav aria-label="Juridische links">
                    <a href="${siteUrl("cookiebeleid.html")}">Cookiebeleid</a>
                    <a href="${siteUrl("privacybeleid.html")}">Privacybeleid</a>
                    <a href="${siteUrl("algemene-voorwaarden.html")}">Algemene voorwaarden</a>
                </nav>
            </div>
        `;

        document.body.appendChild(footer);
    }

    function ensureCookieBanner() {
        if (document.querySelector("[data-site-cookie-banner]") || document.querySelector(".cookie-banner")) {
            return;
        }

        const banner = document.createElement("section");
        banner.className = "cookie-banner is-hidden";
        banner.id = "siteCookieBanner";
        banner.setAttribute("data-site-cookie-banner", "");
        banner.setAttribute("aria-label", "Cookie melding");
        banner.innerHTML = `
            <div>
                <strong>Cookies op deze website</strong>
                <p>We gebruiken noodzakelijke cookies voor de werking van de site. Analytics gebruiken we alleen na jouw toestemming. Lees meer in ons <a href="${siteUrl("cookiebeleid.html")}">cookiebeleid</a> en <a href="${siteUrl("privacybeleid.html")}">privacybeleid</a>.</p>
            </div>
            <div class="cookie-actions">
                <button type="button" data-cookie-decline>Weigeren</button>
                <button class="primary-action" type="button" data-cookie-accept>Accepteren</button>
            </div>
        `;

        document.body.appendChild(banner);
    }

    function setupCookieBanner() {
        const banner = document.querySelector("[data-site-cookie-banner]");

        if (!banner) {
            return;
        }

        const consent = storageGet();

        if (consent === "granted" || consent === "denied") {
            updateAnalyticsConsent(consent);
            banner.classList.add("is-hidden");
        } else {
            updateAnalyticsConsent("denied");
            banner.classList.remove("is-hidden");
        }

        banner.querySelector("[data-cookie-accept]")?.addEventListener("click", function () {
            storageSet("granted");
            updateAnalyticsConsent("granted");
            banner.classList.add("is-hidden");
        });

        banner.querySelector("[data-cookie-decline]")?.addEventListener("click", function () {
            storageSet("denied");
            updateAnalyticsConsent("denied");
            banner.classList.add("is-hidden");
        });
    }

    function init() {
        ensureFooter();
        ensureCookieBanner();
        setupCookieBanner();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
