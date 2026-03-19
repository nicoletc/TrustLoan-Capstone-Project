/**
 * TrustLoan – Password validation and SHA-256 hashing (js folder). Vanilla JS only.
 */
var TrustLoanPassword = (function () {
    'use strict';
    var MIN_LENGTH = 6;

    function validatePassword(plain) {
        var p = (plain || '').trim();
        if (p.length < MIN_LENGTH) {
            return { valid: false, message: 'Password must be at least ' + MIN_LENGTH + ' characters.' };
        }
        return { valid: true, message: '' };
    }

    function passwordsMatch(pwd, confirm) {
        return (pwd || '') === (confirm || '');
    }

    function hashPassword(plain) {
        var str = (plain || '');
        if (typeof crypto !== 'undefined' && crypto.subtle) {
            var encoder = new TextEncoder();
            var data = encoder.encode(str);
            return crypto.subtle.digest('SHA-256', data).then(function (buffer) {
                var arr = new Uint8Array(buffer);
                var hex = '';
                for (var i = 0; i < arr.length; i++) {
                    var h = arr[i].toString(16);
                    hex += h.length === 1 ? '0' + h : h;
                }
                return hex;
            });
        }
        return Promise.reject(new Error('crypto.subtle not available'));
    }

    return {
        MIN_LENGTH: MIN_LENGTH,
        validatePassword: validatePassword,
        passwordsMatch: passwordsMatch,
        hashPassword: hashPassword
    };
})();
