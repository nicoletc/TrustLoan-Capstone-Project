<?php
$adminPage = 'applicants';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">All applicants and their KYC status. Assign status: New, Approved, or Rejected. Risk is based on requested amount.</p>

    <div class="admin-filters" style="margin-bottom:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <label>Status: <select id="filterStatus" class="admin-filter-select">
            <option value="">All</option>
            <option value="new">New</option>
            <option value="in_progress">In progress</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select></label>
        <label>Search: <input type="text" id="filterSearch" class="admin-filter-input" placeholder="Name or phone"></label>
        <button type="button" class="btn btn-sm btn-primary" id="btnLoadApplications">Load</button>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table" id="applicantsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Requested amount</th>
                    <th>Status</th>
                    <th>Risk</th>
                    <th>Submitted</th>
                    <th>Officer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr class="admin-table-empty-row">
                    <td colspan="9" class="admin-table-empty">Loading…</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Application detail drawer -->
<div class="admin-drawer" id="applicationDrawer" aria-hidden="true">
    <div class="admin-drawer-backdrop" id="appDrawerBackdrop"></div>
    <div class="admin-drawer-panel" style="max-width:520px;">
        <div class="admin-drawer-header">
            <h3 id="appDrawerTitle">Application details</h3>
            <button type="button" class="admin-drawer-close" id="appDrawerClose" aria-label="Close">&times;</button>
        </div>
        <div class="admin-drawer-body" id="appDrawerBody">
            <p class="admin-table-empty">Select an application.</p>
        </div>
        <div class="admin-drawer-footer" id="appDrawerFooter" style="padding:1rem;border-top:1px solid var(--border);">
            <p class="admin-drawer-status-note" id="appDrawerStatusNote" style="margin:0 0 0.5rem;font-size:0.8125rem;color:var(--text-muted);display:none;"></p>
            <div id="appDrawerFooterActions">
                <button type="button" class="btn btn-sm" id="btnMarkInProgress">Mark in progress</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnApprove">Approve</button>
                <button type="button" class="btn btn-sm" style="color:#dc2626;" id="btnReject">Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var tbody = document.querySelector('#applicantsTable tbody');
    var drawer = document.getElementById('applicationDrawer');
    var drawerBody = document.getElementById('appDrawerBody');
    var drawerFooter = document.getElementById('appDrawerFooter');
    var currentAppId = 0;
    var Admin = window.TrustLoanAdmin;

    function loadApplications() {
        var status = document.getElementById('filterStatus').value;
        var search = document.getElementById('filterSearch').value.trim();
        var emptyRow = tbody.querySelector('.admin-table-empty-row');
        if (emptyRow) emptyRow.innerHTML = '<td colspan="9" class="admin-table-empty">Loading…</td>';
        Admin.postAction('fetch_applications_action', { status: status || '', search: search }).then(function(res) {
            if (!res.ok || !res.data) {
                if (emptyRow) emptyRow.innerHTML = '<td colspan="9" class="admin-table-empty">Error loading data.</td>';
                return;
            }
            var rows = res.data;
            tbody.innerHTML = '';
            if (rows.length === 0) {
                tbody.innerHTML = '<tr class="admin-table-empty-row"><td colspan="9" class="admin-table-empty">No applicants yet.</td></tr>';
                return;
            }
            rows.forEach(function(r) {
                var risk = Admin.getRisk(r.requested_amount);
                var statusClass = Admin.getStatusClass(r.status);
                var statusLabel = Admin.getStatusLabel(r.status);
                var tr = document.createElement('tr');
                tr.setAttribute('data-id', r.id);
                tr.setAttribute('data-amount', r.requested_amount);
                tr.setAttribute('data-status', r.status);
                tr.innerHTML = '<td>' + r.id + '</td><td>' + (r.borrower_name || '—') + '</td><td>' + (r.borrower_phone || '—') + '</td><td>' + Admin.formatMoney(r.requested_amount) + '</td><td><span class="applicant-status-badge ' + statusClass + '">' + statusLabel + '</span></td><td><span class="risk-badge ' + risk.class + '">' + risk.label + '</span></td><td>' + Admin.formatDate(r.submitted_at) + '</td><td>' + (r.officer_name || '—') + '</td><td><button type="button" class="btn btn-sm btn-view-app">View</button></td>';
                tbody.appendChild(tr);
            });
            tbody.querySelectorAll('.btn-view-app').forEach(function(btn) {
                btn.addEventListener('click', function() { openAppDrawer(parseInt(this.closest('tr').getAttribute('data-id'), 10)); });
            });
        });
    }

    function openAppDrawer(id) {
        currentAppId = id;
        drawerFooter.style.display = 'none';
        drawerBody.innerHTML = '<p class="admin-table-empty">Loading…</p>';
        if (drawer) { drawer.classList.add('open'); drawer.setAttribute('aria-hidden', 'false'); }
        Admin.postAction('fetch_application_action', { id: id }).then(function(res) {
            if (!res.ok || !res.data) {
                drawerBody.innerHTML = '<p class="admin-table-empty">Error loading application.</p>';
                return;
            }
            var a = res.data;
            var b = a.borrower || {};
            var g = a.guarantor || {};
            var m = a.mfi || {};
            var status = (a.status || '').toString().toLowerCase();
            var canAct = status === 'new' || status === 'in_progress';
            var creditScoreStr = (a.credit_score != null && a.credit_score !== '') ? (a.credit_score + ' / 100') : 'Not yet calculated';
            var html = '<p><strong>Borrower:</strong> ' + (b.full_name || '—') + ', ' + (b.phone || '—') + '</p>' +
                '<p><strong>Credit score:</strong> ' + creditScoreStr + '</p>' +
                '<p><strong>Requested amount:</strong> ' + Admin.formatMoney(a.requested_amount) + ', ' + (a.repayment_weeks || 12) + ' weeks</p>' +
                '<p><strong>Business:</strong> ' + (a.business_type || '—') + ', ' + (a.business_duration || '—') + ', ' + (a.business_location || '—') + '</p>' +
                '<p><strong>Guarantor:</strong> ' + (g.full_name || '—') + ', ' + (g.phone || '—') + ', ' + (g.relationship || '—') + ', status: ' + (g.status || '—') + '</p>' +
                '<p><strong>MFI:</strong> ' + (m.mfi_name || '—') + '</p>' +
                '<p><strong>Status:</strong> <span class="applicant-status-badge ' + Admin.getStatusClass(status) + '">' + Admin.getStatusLabel(status) + '</span></p>';
            if (status === 'rejected' && (a.notes || '').trim() !== '') {
                var reason = (a.notes || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                html += '<p><strong>Rejection reason</strong></p><p style="white-space:pre-wrap;margin-bottom:1rem;padding:0.5rem;background:var(--bg-muted, #f3f4f6);border-radius:4px;">' + reason + '</p>';
            }
            html += '<p><strong>Documents</strong></p>';
            var imgStyle = 'max-width:140px;max-height:100px;object-fit:contain;border:1px solid var(--border);border-radius:4px;';
            var imgUrl = function(type) { return Admin.baseUrl + 'index.php?page=view_application_image&id=' + a.id + '&type=' + encodeURIComponent(type); };
            if (a.ghana_card_front_path || a.ghana_card_back_path) {
                html += '<div class="admin-doc-photos" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">';
                if (a.ghana_card_front_path) html += '<div><span style="display:block;font-size:0.75rem;color:var(--text-muted);">Ghana Card – Front</span><img src="' + imgUrl('ghana_front') + '" alt="Ghana Card front" class="admin-doc-img" style="' + imgStyle + '" onerror="this.style.display=\'none\';this.nextElementSibling&&(this.nextElementSibling.style.display=\'block\');"><span style="display:none;font-size:0.75rem;color:var(--text-muted);">Not available</span></div>';
                if (a.ghana_card_back_path) html += '<div><span style="display:block;font-size:0.75rem;color:var(--text-muted);">Ghana Card – Back</span><img src="' + imgUrl('ghana_back') + '" alt="Ghana Card back" class="admin-doc-img" style="' + imgStyle + '" onerror="this.style.display=\'none\';this.nextElementSibling&&(this.nextElementSibling.style.display=\'block\');"><span style="display:none;font-size:0.75rem;color:var(--text-muted);">Not available</span></div>';
                html += '</div>';
            } else {
                html += '<p style="font-size:0.875rem;color:var(--text-muted);margin-bottom:1rem;">Ghana Card: Not uploaded.</p>';
            }
            if (a.photos && a.photos.length > 0) {
                html += '<p style="font-size:0.875rem;margin-bottom:0.25rem;">Photos</p><div class="admin-doc-photos" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">';
                a.photos.forEach(function(ph, i) {
                    html += '<div><span style="display:block;font-size:0.75rem;color:var(--text-muted);">Photo ' + (i + 1) + '</span><img src="' + imgUrl('photo_' + i) + '" alt="Photo ' + (i + 1) + '" class="admin-doc-img" style="' + imgStyle + '" onerror="this.style.display=\'none\';this.nextElementSibling&&(this.nextElementSibling.style.display=\'block\');"><span style="display:none;font-size:0.75rem;color:var(--text-muted);">Not available</span></div>';
                });
                html += '</div>';
            } else {
                html += '<p style="font-size:0.875rem;color:var(--text-muted);margin-bottom:1rem;">Photos: None.</p>';
            }
            drawerBody.innerHTML = html;
            drawerFooter.style.display = 'block';
            var footerActions = document.getElementById('appDrawerFooterActions');
            var statusNote = document.getElementById('appDrawerStatusNote');
            if (canAct) {
                if (footerActions) footerActions.style.display = 'block';
                if (statusNote) { statusNote.textContent = ''; statusNote.style.display = 'none'; }
            } else {
                if (footerActions) footerActions.style.display = 'none';
                if (statusNote) {
                    statusNote.textContent = status === 'approved' ? 'This application has already been approved.' : status === 'rejected' ? 'This application has been rejected.' : '';
                    statusNote.style.display = 'block';
                }
            }
        });
    }

    function closeAppDrawer() {
        currentAppId = 0;
        if (drawer) { drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); }
    }

    function doStatus(status, notes) {
        if (currentAppId <= 0) return;
        var payload = { application_id: currentAppId, status: status };
        if (notes !== undefined && notes !== null) payload.notes = notes;
        Admin.postAction('update_application_status_action', payload).then(function(res) {
            if (res.ok) { closeAppDrawer(); loadApplications(); Admin.swalSuccess('Done', status === 'rejected' ? 'Application rejected.' : 'Marked in progress.'); }
            else Admin.swalError('Error', res.error || 'Failed');
        });
    }

    document.getElementById('btnLoadApplications').addEventListener('click', loadApplications);
    document.getElementById('appDrawerBackdrop').addEventListener('click', closeAppDrawer);
    document.getElementById('appDrawerClose').addEventListener('click', closeAppDrawer);
    document.getElementById('btnMarkInProgress').addEventListener('click', function() { doStatus('in_progress'); });
    document.getElementById('btnReject').addEventListener('click', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Reject application',
                html: '<p class="swal-reject-hint">Provide a reason for the borrower (they will see this on the rejection page).</p>',
                input: 'textarea',
                inputLabel: 'Rejection reason',
                inputPlaceholder: 'e.g. Insufficient documentation, credit score below threshold…',
                inputAttributes: { rows: 4 },
                showCancelButton: true,
                confirmButtonText: 'Reject application',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                inputValidator: function(value) { return !value || value.trim() === '' ? 'Reason is required.' : null; }
            }).then(function(r) {
                if (r.isConfirmed && r.value) doStatus('rejected', r.value.trim());
            });
        } else {
            var reason = prompt('Rejection reason (shown to the borrower):');
            if (reason != null && reason.trim() !== '') doStatus('rejected', reason.trim());
            else if (reason !== null) Admin.swalError('Required', 'Please enter a rejection reason.');
        }
    });

    document.getElementById('btnApprove').addEventListener('click', function() {
        if (currentAppId <= 0) return;
        var groupId = prompt('Enter existing group ID (or leave empty to create new):');
        var groupName = '', mfiId = '';
        if (groupId === null) return;
        if (!groupId || groupId.trim() === '') {
            groupName = prompt('New group name:');
            mfiId = prompt('MFI ID for new group:');
            if (!groupName || !mfiId) return;
        }
        var meetingName = prompt('Meeting location name (optional):', '');
        Admin.postAction('approve_application_action', {
            application_id: currentAppId,
            group_id: (groupId && groupId.trim() !== '') ? groupId.trim() : 0,
            group_name: groupName,
            mfi_id: mfiId,
            meeting_name: meetingName,
            meeting_day: 3,
            meeting_time: '09:00'
        }).then(function(res) {
            if (res.ok) { closeAppDrawer(); loadApplications(); Admin.swalSuccess('Approved', 'Loan #' + (res.loan_id || '') + ' created.'); }
            else Admin.swalError('Error', res.error || 'Failed');
        });
    });

    loadApplications();
})();
</script>
