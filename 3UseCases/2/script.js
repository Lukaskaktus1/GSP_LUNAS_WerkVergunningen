document.addEventListener('DOMContentLoaded', () => {
    const targetDate = new Date('May 15, 2026 09:00:00').getTime();
    if (document.getElementById('countdown')) {
        function updateClock() {
            const now = new Date().getTime();
            const d = targetDate - now;
            document.getElementById('days').innerText = Math.floor(d / 86400000).toString().padStart(2, '0');
            document.getElementById('hours').innerText = Math.floor((d % 86400000) / 3600000).toString().padStart(2, '0');
            document.getElementById('minutes').innerText = Math.floor((d % 3600000) / 60000).toString().padStart(2, '0');
            document.getElementById('seconds').innerText = Math.floor((d % 60000) / 1000).toString().padStart(2, '0');
        }
        setInterval(updateClock, 1000);
        updateClock();
    }

    const form = document.getElementById('registrationForm');
    if (form) {
        const qtySlider = document.getElementById('ticketQuantity');
        const qtyDisplay = document.getElementById('quantityDisplay');
        const totalPriceDisplay = document.getElementById('totalPrice');
        const promoBtn = document.getElementById('btnApplyPromo');
        const promoInput = document.getElementById('promoCode');
        const promoFeedback = document.getElementById('promoFeedback');
        const tshirtSection = document.getElementById('tshirtSection');
        const workshopCbs = document.querySelectorAll('.workshop-cb');
        const ticketRadios = document.getElementsByName('ticketType');

        let isDiscountApplied = false;

        function updateSliderAchtergrond() {
            const min = parseInt(qtySlider.min, 10);
            const max = parseInt(qtySlider.max, 10);
            const val = parseInt(qtySlider.value, 10);
            const perc = ((val - min) / (max - min)) * 100;
            qtySlider.style.background = 'linear-gradient(to right, ' +
                '#0ea5e9 0%, #0ea5e9 ' + perc + '%, ' +
                '#1e293b ' + perc + '%, #1e293b 100%)';
        }

        function markeerGeselecteerdeTicket() {
            ticketRadios.forEach(r => {
                const kaart = r.closest('.ticket-card');
                if (!kaart) return;
                if (r.checked) {
                    kaart.classList.add('ticket-geselecteerd');
                } else {
                    kaart.classList.remove('ticket-geselecteerd');
                }
            });
        }

        function calculateTotal() {
            let selectedPrice = 0;
            let isVIP = false;

            ticketRadios.forEach(r => {
                if (r.checked) {
                    selectedPrice = parseFloat(r.dataset.price);
                    if (r.value === 'VIP') isVIP = true;
                }
            });

            tshirtSection.style.display = isVIP ? 'block' : 'none';

            const qty = parseInt(qtySlider.value, 10);
            const workshopsPrice = Array.from(workshopCbs).filter(cb => cb.checked).length * 50;

            let total = (selectedPrice * qty) + workshopsPrice;
            if (isDiscountApplied) total *= 0.8;

            qtyDisplay.innerText = qty;
            totalPriceDisplay.innerText = '€ ' + total.toLocaleString('nl-BE', { minimumFractionDigits: 2 });
            updateSliderAchtergrond();
        }

        qtySlider.addEventListener('input', calculateTotal);
        ticketRadios.forEach(r => r.addEventListener('change', () => {
            calculateTotal();
            markeerGeselecteerdeTicket();
        }));

        workshopCbs.forEach(cb => cb.addEventListener('change', (e) => {
            const checked = Array.from(workshopCbs).filter(c => c.checked);
            if (checked.length > 3) {
                e.target.checked = false;
                return;
            }
            calculateTotal();
        }));

        promoBtn.addEventListener('click', () => {
            const code = promoInput.value.trim().toUpperCase();

            if (code === 'TECH20') {
                isDiscountApplied = true;
                promoFeedback.textContent = 'Code TECH20 toegepast (-20%)';
                promoFeedback.style.color = '#22c55e';
            } else {
                isDiscountApplied = false;
                promoFeedback.textContent = 'Ongeldige kortingscode';
                promoFeedback.style.color = '#f97373';
            }
            calculateTotal();
        });

        document.querySelectorAll('.faq-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const panel = btn.nextElementSibling;
                const teken = btn.querySelector('.faq-teken');
                const open = panel.classList.contains('open');

                document.querySelectorAll('.faq-panel').forEach(p => p.classList.remove('open'));
                document.querySelectorAll('.faq-teken').forEach(t => t.style.transform = 'rotate(0deg)');

                if (!open) {
                    panel.classList.add('open');
                    teken.style.transform = 'rotate(45deg)';
                }
            });
        });

        function maakTicketNummer(naam) {
            const prefix = (naam.substring(0, 3) || 'TCE').toUpperCase();
            return prefix + '-' + Date.now().toString().slice(-4) + '-' + Math.floor(1000 + Math.random() * 9000);
        }

        form.onsubmit = (e) => {
            e.preventDefault();
            const naam = document.getElementById('userName').value;
            const email = document.getElementById('userEmail').value;
            const ticketID = maakTicketNummer(naam);

            const params = new URLSearchParams({
                naam: naam,
                email: email,
                ticket: ticketID
            });

            window.location.href = 'ticket.html?' + params.toString();
        };

        markeerGeselecteerdeTicket();
        calculateTotal();
    }

    const ticketNummerElement = document.getElementById('ticketNummer');
    if (ticketNummerElement) {
        const params = new URLSearchParams(window.location.search);
        const naam = params.get('naam') || 'Bezoeker';
        const email = params.get('email') || 'onbekend';
        const ticket = params.get('ticket') || 'TCE-0000-0000';

        const groet = document.getElementById('ticketGroet');
        const ticketEmail = document.getElementById('ticketEmail');

        if (groet) {
            groet.textContent = 'Beste ' + naam + ', hieronder vind je een voorbeeld van je digitale ticket.';
        }
        ticketNummerElement.textContent = ticket;
        if (ticketEmail) {
            ticketEmail.textContent = email;
        }

        if (window.QRCode) {
            new QRCode(document.getElementById('qrcode'), {
                text: 'TECHCON2026|' + ticket + '|' + email,
                width: 128,
                height: 128
            });
        }
    }
});