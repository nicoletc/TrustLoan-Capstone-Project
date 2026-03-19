/**
 * TrustLoan – minimal JS for landing and forms. Vanilla JS only.
 */
(function () {
    'use strict';
    document.querySelectorAll('.nav a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var id = this.getAttribute('href');
            if (id && id.length > 1) {
                var el = document.querySelector(id);
                if (el) {
                    e.preventDefault();
                    el.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
})();
