/**
 * TrustLoan – Admin: shared helpers for fetch actions and UI.
 */
(function () {
    'use strict';
    var baseUrl = typeof window.adminBaseUrl !== 'undefined' ? window.adminBaseUrl : '';

    function postAction(action, params) {
        params = params || {};
        params.action = action;
        var body = new URLSearchParams(params);
        return fetch(baseUrl + 'index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin'
        }).then(function (r) {
            var ct = r.headers.get('Content-Type') || '';
            if (ct.indexOf('application/json') !== -1) return r.json();
            return r.text().then(function(t) {
                try { return JSON.parse(t); } catch (e) { return { ok: false, error: 'Invalid response' }; }
            });
        }).then(function (data) {
            if (data && data.ok === false && (data.error === 'unauthorized' || data.error === 'session_expired')) {
                window.location.href = baseUrl + 'Admin/login.php?timeout=1';
            }
            return data;
        }).catch(function (err) {
            return { ok: false, error: err.message || 'Request failed' };
        });
    }

    function swalSuccess(title, text) {
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: title || 'Success', text: text || '' });
        else if (typeof alert !== 'undefined') alert(title + (text ? '\n' + text : ''));
    }
    function swalError(title, text) {
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: title || 'Error', text: text || '' });
        else if (typeof alert !== 'undefined') alert(title + (text ? '\n' + text : ''));
    }
    function swalConfirm(title, text) {
        if (typeof Swal !== 'undefined') return Swal.fire({ icon: 'question', title: title || 'Confirm', text: text || '', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel' }).then(function(r) { return r.isConfirmed; });
        return confirm(title + (text ? '\n' + text : ''));
    }

    window.TrustLoanAdmin = {
        baseUrl: baseUrl,
        postAction: postAction,
        swalSuccess: swalSuccess,
        swalError: swalError,
        swalConfirm: swalConfirm,
        formatMoney: function (n) { return 'GH¢ ' + (Number(n).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })); },
        formatDate: function (d) {
            if (!d) return '—';
            var dt = new Date(d);
            return isNaN(dt.getTime()) ? d : dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        formatDateTime: function (d) {
            if (!d) return '—';
            var dt = new Date(d);
            return isNaN(dt.getTime()) ? d : dt.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
        getRisk: function (amount) {
            var n = Number(amount) || 0;
            if (n >= 5001) return { label: 'High', class: 'risk-high' };
            if (n >= 3000) return { label: 'Medium', class: 'risk-medium' };
            return { label: 'Low', class: 'risk-low' };
        },
        getStatusClass: function (status) {
            var s = (status || '').toLowerCase();
            if (s === 'approved') return 'status-approved';
            if (s === 'rejected') return 'status-rejected';
            if (s === 'in_progress') return 'status-in-progress';
            return 'status-new';
        },
        getStatusLabel: function (status) {
            var s = (status || '').toLowerCase();
            if (s === 'approved') return 'Approved';
            if (s === 'rejected') return 'Rejected';
            if (s === 'in_progress') return 'In progress';
            return 'New';
        },
        /** Build full URL for an image path (Ghana Card, application photos). */
        imageUrl: function (path) {
            if (!path || typeof path !== 'string') return '';
            var p = path.trim();
            if (p.indexOf('http') === 0 || p.indexOf('//') === 0) return p;
            var base = (baseUrl || '').replace(/\/+$/, '');
            if (p.indexOf('/') === 0) return base ? base + p : p;
            return base ? base + '/' + p.replace(/^\/+/, '') : p;
        }
    };
})();
