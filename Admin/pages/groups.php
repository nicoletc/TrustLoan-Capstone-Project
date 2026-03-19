<?php
$adminPage = 'groups';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<div class="admin-page">
    <p class="admin-desc">Groups and repayment status.</p>

    <div class="admin-filters" style="margin-bottom:1rem;">
        <button type="button" class="btn btn-sm btn-primary" id="btnCreateGroup">Create group</button>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table" id="groupsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Group name</th>
                    <th>MFI</th>
                    <th>Members</th>
                    <th>Repayment status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="admin-table-empty">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Group detail drawer: members and contact info -->
<div class="admin-drawer" id="groupDrawer" aria-hidden="true">
    <div class="admin-drawer-backdrop" id="groupDrawerBackdrop"></div>
    <div class="admin-drawer-panel" style="max-width:480px;">
        <div class="admin-drawer-header">
            <h3 id="groupDrawerTitle">Group members</h3>
            <button type="button" class="admin-drawer-close" id="groupDrawerClose" aria-label="Close">&times;</button>
        </div>
        <div class="admin-drawer-body" id="groupDrawerBody">
            <p class="admin-table-empty">Select a group.</p>
        </div>
    </div>
</div>

<script>
(function() {
    var Admin = window.TrustLoanAdmin;
    var drawer = document.getElementById('groupDrawer');
    var drawerBody = document.getElementById('groupDrawerBody');
    var drawerTitle = document.getElementById('groupDrawerTitle');

    function loadGroups() {
        var tbody = document.querySelector('#groupsTable tbody');
        if (!tbody) return;
        Admin.postAction('fetch_groups_action', {}).then(function(res) {
            if (!res.ok || !res.data) {
                tbody.innerHTML = '<tr><td colspan="6" class="admin-table-empty">Error loading.</td></tr>';
                return;
            }
            var rows = res.data;
            tbody.innerHTML = '';
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="admin-table-empty">No groups yet.</td></tr>';
                return;
            }
            rows.forEach(function(r) {
                var status = (r.repayment_status || 'good').toLowerCase();
                var label = status === 'bad' ? 'Bad' : (status === 'watch' ? 'Watch' : 'Good');
                var statusClass = 'repayment-status-badge repayment-status-' + status;
                if (status !== 'good' && status !== 'bad' && status !== 'watch') statusClass = 'repayment-status-badge repayment-status-good';
                var tr = document.createElement('tr');
                tr.setAttribute('data-id', r.id);
                tr.innerHTML = '<td>' + r.id + '</td><td>' + (r.name || '—') + '</td><td>' + (r.mfi_name || '—') + '</td><td>' + (r.member_count || 0) + '</td><td><span class="' + statusClass + '">' + label + '</span></td><td><button type="button" class="btn btn-sm btn-view-group">View</button></td>';
                tbody.appendChild(tr);
            });
            tbody.querySelectorAll('.btn-view-group').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openGroupDrawer(parseInt(this.closest('tr').getAttribute('data-id'), 10));
                });
            });
        });
    }

    function openGroupDrawer(groupId) {
        drawerBody.innerHTML = '<p class="admin-table-empty">Loading…</p>';
        if (drawer) { drawer.classList.add('open'); drawer.setAttribute('aria-hidden', 'false'); }
        Admin.postAction('fetch_group_action', { id: groupId }).then(function(res) {
            if (!res.ok || !res.data) {
                drawerBody.innerHTML = '<p class="admin-table-empty">Error loading group.</p>';
                return;
            }
            var g = res.data;
            var status = (g.repayment_status || 'good').toLowerCase();
            var statusLabel = status === 'bad' ? 'Bad' : (status === 'watch' ? 'Watch' : 'Good');
            drawerTitle.textContent = g.name ? (g.name + ' – Members') : 'Group members';
            var html = '<p><strong>Group:</strong> ' + (g.name || '—') + '</p>';
            html += '<p><strong>MFI:</strong> ' + (g.mfi_name || '—') + '</p>';
            html += '<p><strong>Repayment status:</strong> <span class="repayment-status-badge repayment-status-' + status + '">' + statusLabel + '</span></p>';
            var meeting = g.meeting_location;
            if (meeting && meeting.name) html += '<p><strong>Meeting:</strong> ' + (meeting.name || '—') + '</p>';
            html += '<p style="margin-top:1rem;margin-bottom:0.5rem;"><strong>Members & contact</strong></p>';
            var members = g.members || [];
            if (members.length === 0) {
                html += '<p class="admin-table-empty">No members in this group.</p>';
            } else {
                html += '<ul style="list-style:none;padding:0;margin:0;">';
                members.forEach(function(m) {
                    var role = (m.role || 'member') === 'head' ? ' (Head)' : '';
                    html += '<li style="padding:0.5rem 0;border-bottom:1px solid var(--border);"><strong>' + (m.full_name || '—') + '</strong>' + role + '<br><span style="font-size:0.875rem;color:var(--text-muted);">' + (m.phone || '—') + '</span></li>';
                });
                html += '</ul>';
            }
            drawerBody.innerHTML = html;
        });
    }

    function closeGroupDrawer() {
        if (drawer) { drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); }
    }

    document.getElementById('btnCreateGroup').addEventListener('click', function() {
        var name = prompt('Group name:');
        var mfiId = prompt('MFI ID:');
        if (!name || !mfiId) return;
        Admin.postAction('create_group_action', { name: name, mfi_id: mfiId }).then(function(res) {
            if (res.ok) { loadGroups(); Admin.swalSuccess('Group created', ''); }
            else Admin.swalError('Error', res.error || 'Failed');
        });
    });
    document.getElementById('groupDrawerBackdrop').addEventListener('click', closeGroupDrawer);
    document.getElementById('groupDrawerClose').addEventListener('click', closeGroupDrawer);
    loadGroups();
})();
</script>
