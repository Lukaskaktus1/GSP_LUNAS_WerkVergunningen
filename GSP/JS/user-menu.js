(function () {
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

        var menu = document.createElement('div');
        menu.className = 'user-menu';
        menu.innerHTML = [
            '<button class="user-menu-toggle" type="button" aria-expanded="false" aria-label="Accountmenu openen">',
            '  <i class="fas fa-bars" aria-hidden="true"></i>',
            '  <span>Menu</span>',
            '</button>',
            '<div class="user-menu-panel" role="menu">',
            '  <a href="/GSP/pages/profiel.php" role="menuitem">Gegevens aanpassen</a>',
            '  <a class="danger" href="/GSP/pages/profiel.php#account-verwijderen" role="menuitem">Account verwijderen</a>',
            '  <a href="/GSP/logout.php" role="menuitem">Uitloggen</a>',
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
