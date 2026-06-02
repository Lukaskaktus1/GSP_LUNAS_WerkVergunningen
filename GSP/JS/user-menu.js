(function () {
    function gspBasePath() {
        var path = window.location.pathname.replace(/\\/g, '/');
        var index = path.toLowerCase().indexOf('/gsp/');

        if (index !== -1) {
            return path.substring(0, index + 4);
        }

        return '';
    }

    function logoutUrl() {
        var basePath = gspBasePath();

        if (basePath !== '') {
            return basePath + '/logout.php';
        }

        var path = window.location.pathname.replace(/\\/g, '/');

        if (path.indexOf('/pages/') !== -1 || path.indexOf('/PHP/') !== -1) {
            return '../logout.php';
        }

        return 'logout.php';
    }

    function menuUrls() {
        var path = window.location.pathname.replace(/\\/g, '/');
        var basePath = gspBasePath();

        if (path.indexOf('/pages/') !== -1) {
            return {
                profiel: 'profiel.php',
                profielDelete: 'profiel.php#account-verwijderen',
                logout: logoutUrl()
            };
        }

        if (path.indexOf('/PHP/') !== -1) {
            return {
                profiel: '../pages/profiel.php',
                profielDelete: '../pages/profiel.php#account-verwijderen',
                logout: logoutUrl()
            };
        }

        if (basePath !== '') {
            return {
                profiel: basePath + '/pages/profiel.php',
                profielDelete: basePath + '/pages/profiel.php#account-verwijderen',
                logout: logoutUrl()
            };
        }

        return {
            profiel: 'pages/profiel.php',
            profielDelete: 'pages/profiel.php#account-verwijderen',
            logout: logoutUrl()
        };
    }

    function removeStandaloneLogoutButtons(headerRight) {
        headerRight.querySelectorAll('button').forEach(function (button) {
            var target = button.getAttribute('onclick') || '';
            if (target.indexOf('logout.php') !== -1) {
                button.remove();
            }
        });
    }

    function closeOtherMenus(currentMenu) {
        document.querySelectorAll('.user-menu.open').forEach(function (menu) {
            if (menu !== currentMenu) {
                menu.classList.remove('open');
            }
        });
    }

    function createUserMenu() {
        var headerRight = document.querySelector('.header-right');
        if (!headerRight || headerRight.querySelector('.user-menu')) {
            return;
        }

        removeStandaloneLogoutButtons(headerRight);

        var urls = menuUrls();
        var menu = document.createElement('div');
        menu.className = 'user-menu';
        menu.innerHTML = [
            '<button class="user-menu-toggle" type="button" aria-expanded="false" aria-label="Accountmenu openen">',
            '  <i class="fas fa-bars" aria-hidden="true"></i>',
            '  <span>Menu</span>',
            '</button>',
            '<div class="user-menu-panel" role="menu">',
            '  <a href="' + urls.profiel + '" role="menuitem">Gegevens aanpassen</a>',
            '  <a class="danger" href="' + urls.profielDelete + '" role="menuitem">Account verwijderen</a>',
            '  <a href="' + urls.logout + '" role="menuitem" id="user-menu-logout">Uitloggen</a>',
            '</div>'
        ].join('');

        headerRight.appendChild(menu);

        var toggle = menu.querySelector('.user-menu-toggle');
        toggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('open');
            closeOtherMenus(menu);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target)) {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', createUserMenu);
})();
