(function () {
    const origin = window.location.origin;
    const path = window.location.pathname;
    const pageUrl = origin + path;
    const logoUrl = origin + '/afbeeldingen/LogoADB_1.png';

    document.querySelectorAll('link[rel="canonical"]').forEach(function (link) {
        link.href = pageUrl;
    });

    document.querySelectorAll('meta[property="og:url"]').forEach(function (meta) {
        meta.content = pageUrl;
    });

    document.querySelectorAll('meta[property="og:image"], meta[name="twitter:image"]').forEach(function (meta) {
        meta.content = logoUrl;
    });
})();
