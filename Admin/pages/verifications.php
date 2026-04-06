<?php
$adminPage = 'verifications';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">Applications that still need attention: <strong>New</strong> (not yet reviewed) and <strong>In progress</strong> (being worked on). <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=applicants">Applicants</a> has full filters.</p>
    <div class="admin-table-wrap">
        <table class="admin-table" id="verificationsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Applicant</th>
                    <th>Phone</th>
                    <th>Requested amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" class="admin-table-empty">Loading…</td></tr>
            </tbody>
        </table>
    </div>
    <p class="form-hint"><a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=applicants">View all applicants</a> to assign status.</p>
</div>

<script>
(function() {
    var Admin = window.TrustLoanAdmin;
    var tbody = document.querySelector('#verificationsTable tbody');
    Admin.postAction('fetch_applications_action', { status: 'new,in_progress' }).then(function(res) {
        if (!res.ok || !res.data) {
            tbody.innerHTML = '<tr><td colspan="7" class="admin-table-empty">Error loading.</td></tr>';
            return;
        }
        var rows = res.data;
        tbody.innerHTML = '';
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="admin-table-empty">No items yet.</td></tr>';
            return;
        }
        rows.forEach(function(r) {
            var st = (r.status || 'new').toString().toLowerCase();
            var badgeClass = Admin.getStatusClass(st);
            var badgeLabel = Admin.getStatusLabel(st);
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + r.id + '</td><td>' + (r.borrower_name || '—') + '</td><td>' + (r.borrower_phone || '—') + '</td><td>' + Admin.formatMoney(r.requested_amount) + '</td><td><span class="applicant-status-badge ' + badgeClass + '">' + badgeLabel + '</span></td><td>' + Admin.formatDate(r.submitted_at) + '</td><td><a href="' + Admin.baseUrl + 'Admin/?page=applicants" class="btn btn-sm">Review</a></td>';
            tbody.appendChild(tr);
        });
    });
})();
</script>
