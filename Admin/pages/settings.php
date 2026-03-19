<?php
$adminPage = 'settings';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">Configure loan products, repayment schedules, groups, penalties, and user roles. All financial logic and decisions remain admin-controlled. Actions like cash collection and meetings happen offline.</p>

    <div class="admin-settings-grid">
        <section class="admin-card admin-card-settings">
            <h2>Loan products</h2>
            <p>Amount ranges, duration, and interest rates. Borrowers cannot edit these.</p>
            <ul class="admin-settings-list">
                <li>Min / max loan amount</li>
                <li>Loan duration (weeks)</li>
                <li>Interest rate</li>
            </ul>
            <a href="#" class="admin-card-link">Configure loan products</a>
        </section>
        <section class="admin-card admin-card-settings">
            <h2>Repayment schedule</h2>
            <p>Define weekly collection day and frequency.</p>
            <ul class="admin-settings-list">
                <li>Collection day (e.g. weekly)</li>
                <li>Frequency</li>
            </ul>
            <a href="#" class="admin-card-link">Define schedule</a>
        </section>
        <section class="admin-card admin-card-settings">
            <h2>Groups</h2>
            <p>Create and manage borrower groups and meeting locations.</p>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=groups" class="admin-card-link">Manage groups</a>
        </section>
        <section class="admin-card admin-card-settings">
            <h2>Assign borrowers to groups</h2>
            <p>Assign borrowers to groups from the Groups or Applicants area.</p>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=groups" class="admin-card-link">Go to groups</a>
        </section>
        <section class="admin-card admin-card-settings">
            <h2>Guarantor information</h2>
            <p>Record and view guarantor details per application.</p>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=guarantors" class="admin-card-link">View guarantors</a>
        </section>
        <section class="admin-card admin-card-settings">
            <h2>Penalties</h2>
            <p>Set basic penalties for lateness and missed meetings.</p>
            <ul class="admin-settings-list">
                <li>Late payment penalty</li>
                <li>Missed meeting penalty</li>
            </ul>
            <a href="#" class="admin-card-link">Set penalties</a>
        </section>
        <section class="admin-card admin-card-settings">
            <h2>User roles</h2>
            <p>Manage admin access only. Borrowers do not have access to admin features.</p>
            <a href="#" class="admin-card-link">Manage roles</a>
        </section>
    </div>

    <section class="admin-settings-audit">
        <h2 class="admin-settings-audit-title">Audit logs</h2>
        <p class="admin-settings-audit-desc">View changes and actions by officers. Supports monitoring and record-keeping.</p>
        <div class="admin-table-wrap">
            <table class="admin-table" id="auditLogsTable">
                <thead>
                    <tr>
                        <th>Date & time</th>
                        <th>Officer</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="admin-table-empty">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
(function() {
    var Admin = window.TrustLoanAdmin;
    var tbody = document.querySelector('#auditLogsTable tbody');
    Admin.postAction('fetch_audit_logs_action', { limit: 100 }).then(function(res) {
        if (!res.ok || !res.data) {
            var msg = (res.error || 'Error loading.') + (res.debug ? ' (' + String(res.debug).replace(/</g, '&lt;').replace(/>/g, '&gt;') + ')' : '');
            tbody.innerHTML = '<tr><td colspan="4" class="admin-table-empty">' + msg + '</td></tr>';
            return;
        }
        var rows = res.data;
        tbody.innerHTML = '';
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="admin-table-empty">No audit entries yet.</td></tr>';
            return;
        }
        rows.forEach(function(r) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + Admin.formatDateTime(r.created_at) + '</td><td>' + (r.admin_name || '—') + '</td><td>' + (r.action || '—') + '</td><td>' + (r.details || '—') + '</td>';
            tbody.appendChild(tr);
        });
    });
})();
</script>
