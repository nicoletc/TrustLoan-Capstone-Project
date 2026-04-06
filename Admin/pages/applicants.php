<?php
$adminPage = 'applicants';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">All applicants and their KYC status. Assign status: New, Approved, or Rejected. The <strong>Risk</strong> column is a quick amount-based hint; open an application for full <strong>ML default risk</strong> (PD, decision, model, routing).</p>

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

    function escHtml(s) {
        if (s == null || s === '') return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

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
            if (a.ml_service_unreachable && a.credit_score != null && a.credit_score !== '') {
                creditScoreStr += ' <span style="font-weight:normal;color:var(--text-muted);">(last saved; ML unreachable)</span>';
            }
            var html = '<p><strong>Borrower:</strong> ' + (b.full_name || '—') + ', ' + (b.phone || '—') + '</p>' +
                '<p><strong>Credit score:</strong> ' + creditScoreStr + '</p>' +
                '<p><strong>Requested amount:</strong> ' + Admin.formatMoney(a.requested_amount) + ', ' + (a.repayment_weeks || 12) + ' weeks</p>' +
                '<p><strong>Business:</strong> ' + (a.business_type || '—') + ', ' + (a.business_duration || '—') + ', ' + (a.business_location || '—') + '</p>' +
                '<p><strong>Guarantor:</strong> ' + (g.full_name || '—') + ', ' + (g.phone || '—') + ', ' + (g.relationship || '—') + ', status: ' + (g.status || '—') + '</p>' +
                '<p><strong>MFI:</strong> ' + (m.mfi_name || '—') + '</p>' +
                '<p><strong>Status:</strong> <span class="applicant-status-badge ' + Admin.getStatusClass(status) + '">' + Admin.getStatusLabel(status) + '</span></p>';
            var ml = a.ml_scoring;
            if (ml && typeof ml === 'object') {
                var pd = typeof ml.pd === 'number' ? ml.pd : null;
                var pdStr = (pd != null && pd >= 0 && pd <= 1) ? (pd * 100).toFixed(2) + '%' : escHtml(ml.pd);
                html += '<div class="admin-ml-panel" style="margin:1rem 0;padding:0.75rem;background:var(--bg-muted, #f3f4f6);border-radius:6px;border:1px solid var(--border);font-size:0.875rem;">';
                html += '<p style="margin:0 0 0.5rem;font-weight:600;">ML default risk (internal)</p><ul style="margin:0;padding-left:1.25rem;line-height:1.5;">';
                html += '<li><strong>Estimated PD:</strong> ' + pdStr + '</li>';
                html += '<li><strong>Decision:</strong> ' + escHtml(ml.decision) + '</li>';
                html += '<li><strong>Model used:</strong> ' + escHtml(ml.model_used) + '</li>';
                if (ml.routing && typeof ml.routing === 'object') {
                    html += '<li><strong>Routing</strong> — n_labeled: ' + escHtml(ml.routing.n_labeled) + ', preferred: ' + escHtml(ml.routing.preferred_supervised) + '</li>';
                }
                html += '</ul>';
                if (ml.top_reasons && ml.top_reasons.length) {
                    html += '<p style="margin:0.5rem 0 0.25rem;font-weight:500;">Top reasons</p><ul style="margin:0;padding-left:1.25rem;line-height:1.5;">';
                    ml.top_reasons.forEach(function(r) {
                        if (!r || typeof r !== 'object') return;
                        html += '<li>' + escHtml(r.feature) + ' ' + escHtml(r.direction) + ' · strength ' + escHtml(r.strength) + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';
            } else {
                html += '<p style="margin:1rem 0 0;font-size:0.8125rem;color:var(--text-muted);">ML details: no live score (service off, misconfigured URL, or borrower not scored yet).</p>';
            }
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
        if (typeof Swal === 'undefined') {
            Admin.swalError('Error', 'Could not open approval form. Refresh the page and try again.');
            return;
        }
        Admin.postAction('fetch_approve_form_options_action', {}).then(function(optRes) {
            if (!optRes.ok || !optRes.data) {
                Admin.swalError('Could not load form', optRes.error || 'Failed to load groups and MFIs.');
                return;
            }
            var d = optRes.data;
            var groupOpts = '<option value="">— Create a new group —</option>';
            (d.groups || []).forEach(function(g) {
                var label = '#' + g.id + ' — ' + (g.name || '') + ' (' + (g.mfi_name || '') + ')';
                groupOpts += '<option value="' + String(parseInt(g.id, 10) || '') + '">' + escHtml(label) + '</option>';
            });
            var mfiOpts = '<option value="">— Select MFI —</option>';
            (d.mfis || []).forEach(function(m) {
                var label = (m.name || '') + (m.area_slug ? ' · ' + m.area_slug : '');
                mfiOpts += '<option value="' + String(parseInt(m.id, 10) || '') + '">' + escHtml(label) + '</option>';
            });
            var formHtml = '<div class="swal-approve-form">' +
                '<p class="swal-approve-intro">Pick an <strong>existing group</strong> to use its current meeting setup, or <strong>create a new group</strong> and fill in name, MFI, and optionally where they meet.</p>' +
                '<div class="swal-approve-field">' +
                '<label class="swal-approve-label" for="ap-gid">Group</label>' +
                '<select id="ap-gid" class="swal2-input swal-approve-select">' + groupOpts + '</select></div>' +
                '<p id="ap-existing-group-note" class="swal-approve-existing-note" style="display:none;">This group already has a meeting location on file — it will be used as-is (no new meeting field needed).</p>' +
                '<div id="ap-new-group-wrap" class="swal-approve-new-wrap">' +
                '<div class="swal-approve-field"><label class="swal-approve-label" for="ap-gname">New group name <span class="swal-approve-hint">(when creating)</span></label>' +
                '<input id="ap-gname" class="swal2-input swal-approve-input" type="text" placeholder="e.g. Adenta Women Traders" autocomplete="off"></div>' +
                '<div class="swal-approve-field"><label class="swal-approve-label" for="ap-mfi">MFI <span class="swal-approve-hint">(when creating)</span></label>' +
                '<select id="ap-mfi" class="swal2-input swal-approve-select">' + mfiOpts + '</select></div>' +
                '<div class="swal-approve-field"><label class="swal-approve-label" for="ap-meeting">Meeting location <span class="swal-approve-hint">(optional, new groups only)</span></label>' +
                '<input id="ap-meeting" class="swal2-input swal-approve-input" type="text" placeholder="e.g. Community centre — Main Hall" autocomplete="off"></div></div></div>';
            Swal.fire({
                title: 'Approve application',
                html: formHtml,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Approve & create loan',
                cancelButtonText: 'Cancel',
                customClass: { popup: 'swal-approve-popup' },
                didOpen: function() {
                    var gsel = document.getElementById('ap-gid');
                    var wrap = document.getElementById('ap-new-group-wrap');
                    var note = document.getElementById('ap-existing-group-note');
                    function syncNewGroupFields() {
                        var existing = gsel && gsel.value;
                        if (wrap) wrap.style.display = existing ? 'none' : 'block';
                        if (note) note.style.display = existing ? 'block' : 'none';
                        if (existing) {
                            var meet = document.getElementById('ap-meeting');
                            if (meet) meet.value = '';
                        }
                    }
                    if (gsel) gsel.addEventListener('change', syncNewGroupFields);
                    syncNewGroupFields();
                },
                preConfirm: function() {
                    var gid = (document.getElementById('ap-gid') && document.getElementById('ap-gid').value || '').trim();
                    var gname = (document.getElementById('ap-gname') && document.getElementById('ap-gname').value || '').trim();
                    var mfiRaw = (document.getElementById('ap-mfi') && document.getElementById('ap-mfi').value || '').trim();
                    var meetingRaw = (document.getElementById('ap-meeting') && document.getElementById('ap-meeting').value || '').trim();
                    // Existing group: keep using the group's saved meeting — never send a new meeting from this form.
                    var meeting = gid ? '' : meetingRaw;
                    if (!gid) {
                        if (!gname) {
                            Swal.showValidationMessage('Enter a new group name, or select an existing group.');
                            return false;
                        }
                        if (!mfiRaw) {
                            Swal.showValidationMessage('Select an MFI when creating a new group.');
                            return false;
                        }
                    }
                    return { gid: gid, gname: gname, mfiId: mfiRaw, meeting: meeting };
                }
            }).then(function(r) {
                if (!r.isConfirmed || !r.value) return;
                var v = r.value;
                Admin.postAction('approve_application_action', {
                    application_id: currentAppId,
                    group_id: v.gid ? parseInt(v.gid, 10) : 0,
                    group_name: v.gname || '',
                    mfi_id: v.mfiId ? parseInt(v.mfiId, 10) : 0,
                    meeting_name: v.gid ? '' : (v.meeting || ''),
                    meeting_day: 3,
                    meeting_time: '09:00'
                }).then(function(res) {
                    if (res.ok) {
                        closeAppDrawer();
                        loadApplications();
                        if (res.already_created) {
                            Admin.swalSuccess('Already approved', 'Loan #' + (res.loan_id || '') + ' is already linked to this application.');
                        } else {
                            Admin.swalSuccess('Approved', 'Loan #' + (res.loan_id || '') + ' created.');
                        }
                    } else {
                        Admin.swalError('Could not approve', res.error || 'Something went wrong.');
                    }
                });
            });
        });
    });

    loadApplications();
})();
</script>
