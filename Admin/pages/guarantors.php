<?php
$adminPage = 'guarantors';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">Guarantor information per application.</p>
    <div class="admin-table-wrap">
        <table class="admin-table" id="guarantorsTable">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Guarantor name</th>
                    <th>Phone</th>
                    <th>Relationship</th>
                    <th>Occupation</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="admin-table-empty">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    var Admin = window.TrustLoanAdmin;
    var tbody = document.querySelector('#guarantorsTable tbody');
    Admin.postAction('fetch_guarantors_action', {}).then(function(res) {
        if (!res.ok || !res.data) {
            tbody.innerHTML = '<tr><td colspan="6" class="admin-table-empty">Error loading.</td></tr>';
            return;
        }
        var rows = res.data;
        tbody.innerHTML = '';
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="admin-table-empty">No guarantors yet.</td></tr>';
            return;
        }
        rows.forEach(function(r) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + (r.applicant_name || '—') + '</td><td>' + (r.full_name || '—') + '</td><td>' + (r.phone || '—') + '</td><td>' + (r.relationship || '—') + '</td><td>' + (r.occupation || '—') + '</td><td>' + (r.status || 'pending') + '</td>';
            tbody.appendChild(tr);
        });
    });
})();
</script>
