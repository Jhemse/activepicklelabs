/**
 * main.js - shared front-end behaviour for Active Picklelabs.
 * No framework: plain DOM APIs so it runs straight off <script src="assets/js/main.js">.
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mobile nav toggle ---------- */
    var navToggle = document.getElementById('navToggle');
    var mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('open');
        });
    }

    /* ---------- Booking form: auto-suggest end time 1hr after start ---------- */
    var startTime = document.querySelector('[data-role="start-time"]');
    var endTime = document.querySelector('[data-role="end-time"]');
    if (startTime && endTime) {
        startTime.addEventListener('change', function () {
            if (!startTime.value) return;
            var parts = startTime.value.split(':').map(Number);
            var mins = parts[0] * 60 + parts[1] + 60; // default 1-hour block
            mins = Math.min(mins, 23 * 60 + 59);
            var h = String(Math.floor(mins / 60)).padStart(2, '0');
            var m = String(mins % 60).padStart(2, '0');
            if (!endTime.value) {
                endTime.value = h + ':' + m;
            }
        });
    }

    /* ---------- Booking date: block past dates ---------- */
    var dateInput = document.querySelector('[data-role="booking-date"]');
    if (dateInput) {
        var today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }

    /* ---------- Simple client-side password confirmation check (register.php) ---------- */
    var regForm = document.querySelector('[data-role="register-form"]');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            var pass = regForm.querySelector('[name="password"]');
            var confirm = regForm.querySelector('[name="confirm_password"]');
            if (pass && confirm && pass.value !== confirm.value) {
                e.preventDefault();
                showInlineError(regForm, 'Passwords do not match.');
            }
        });
    }

    /* ---------- Confirm before destructive admin/client actions ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    function showInlineError(form, message) {
        var existing = form.querySelector('.js-inline-error');
        if (existing) existing.remove();
        var div = document.createElement('div');
        div.className = 'alert alert-error js-inline-error';
        div.textContent = message;
        form.prepend(div);
    }
});
