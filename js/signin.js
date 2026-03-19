/**
 * TrustLoan – Sign-in: intl-tel-input for phone; set full number before submit. Vanilla JS only.
 */
(function () {
    'use strict';

    var form = document.getElementById('signinForm');
    var phoneInput = document.getElementById('phone');
    var iti = null;

    function initPhoneInput() {
        if (!phoneInput || typeof intlTelInput === 'undefined') return;
        iti = intlTelInput(phoneInput, {
            initialCountry: 'gh',
            preferredCountries: ['gh', 'ng', 'ke'],
            separateDialCode: true,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19/build/js/utils.js'
        });
    }

    function getFullNumber() {
        if (!iti) return (phoneInput && phoneInput.value) ? phoneInput.value.replace(/\D/g, '') : '';
        var n = iti.getNumber();
        return n ? n.replace(/\s/g, '').replace(/\D/g, '') : '';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPhoneInput);
    } else {
        initPhoneInput();
    }

    if (!form) return;

    form.addEventListener('submit', function (e) {
        var full = getFullNumber();
        if (!full || full.length < 9) {
            e.preventDefault();
            if (iti && iti.isValidNumber && !iti.isValidNumber()) {
                phoneInput.setCustomValidity('Please enter a valid phone number.');
                if (phoneInput.reportValidity) phoneInput.reportValidity();
            }
            return;
        }
        if (iti && iti.isValidNumber && !iti.isValidNumber()) {
            e.preventDefault();
            phoneInput.setCustomValidity('Please enter a valid phone number.');
            if (phoneInput.reportValidity) phoneInput.reportValidity();
            return;
        }
        phoneInput.setCustomValidity('');
        phoneInput.value = full;
        var phoneFullEl = document.getElementById('phone_full');
        if (phoneFullEl) phoneFullEl.value = full;
    });
})();
