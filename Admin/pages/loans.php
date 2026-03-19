<?php
$adminPage = 'loans';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">Active and overdue loans.</p>

    <div class="admin-tabs">
        <button type="button" class="admin-tab active" data-tab="active">Active loans</button>
        <button type="button" class="admin-tab" data-tab="overdue">Overdue</button>
    </div>

    <div class="admin-tab-panel active" id="panel-active">
        <div class="admin-table-wrap">
            <table class="admin-table loans-risk-table" id="loansActiveTable">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Amount borrowed</th>
                        <th>Balance</th>
                        <th>Next due</th>
                        <th>Status</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="admin-table-empty">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-tab-panel" id="panel-overdue">
        <div class="admin-table-wrap">
            <table class="admin-table loans-risk-table" id="loansOverdueTable">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Amount borrowed</th>
                        <th>Balance</th>
                        <th>Next due</th>
                        <th>Status</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="admin-table-empty">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    var Admin = window.TrustLoanAdmin;
    function fillTable(tbodyId, status) {
        var tbody = document.querySelector('#' + tbodyId + ' tbody');
        if (!tbody) return;
        Admin.postAction('fetch_loans_action', { status: status }).then(function(res) {
            if (!res.ok || !res.data) {
                tbody.innerHTML = '<tr><td colspan="6" class="admin-table-empty">Error loading.</td></tr>';
                return;
            }
            var rows = res.data;
            tbody.innerHTML = '';
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="admin-table-empty">No loans.</td></tr>';
                return;
            }
            rows.forEach(function(r) {
                var risk = Admin.getRisk(r.amount_borrowed);
                var tr = document.createElement('tr');
                tr.setAttribute('data-amount', r.amount_borrowed);
                tr.innerHTML = '<td>' + (r.borrower_name || '—') + '</td><td>' + Admin.formatMoney(r.amount_borrowed) + '</td><td>' + Admin.formatMoney(r.balance_remaining) + '</td><td>' + Admin.formatDate(r.next_due_date) + '</td><td>' + (r.status || '') + '</td><td><span class="risk-badge ' + risk.class + '">' + risk.label + '</span></td>';
                tbody.appendChild(tr);
            });
        });
    }
    fillTable('loansActiveTable', 'active');
    fillTable('loansOverdueTable', 'overdue');

    var tabs = document.querySelectorAll('.admin-tab');
    var panels = document.querySelectorAll('.admin-tab-panel');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var id = this.getAttribute('data-tab');
            tabs.forEach(function(t) { t.classList.remove('active'); });
            panels.forEach(function(p) {
                p.classList.remove('active');
                if (p.id === 'panel-' + id) p.classList.add('active');
            });
            this.classList.add('active');
        });
    });
})();
</script>
