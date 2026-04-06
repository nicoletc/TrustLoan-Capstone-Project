<?php
$adminPage = 'dashboard';
?>
<div class="admin-page">
    <p class="admin-desc">Overview of verifications, applicants, and loans.</p>

    <div class="admin-stats">
        <div class="admin-stat-card">
            <span class="admin-stat-value" id="statVerifications">0</span>
            <span class="admin-stat-label">Needs review (new &amp; in progress)</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-value" id="statApplicants">0</span>
            <span class="admin-stat-label">Applicants</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-value" id="statLoans">0</span>
            <span class="admin-stat-label">Active loans</span>
        </div>
    </div>

    <div class="admin-charts">
        <div class="admin-chart-card">
            <h3 class="admin-chart-title">Applications over time</h3>
            <div class="admin-chart-wrap">
                <canvas id="chartApplications" width="400" height="200"></canvas>
            </div>
        </div>
        <div class="admin-chart-card">
            <h3 class="admin-chart-title">Status breakdown</h3>
            <div class="admin-chart-wrap">
                <canvas id="chartStatus" width="300" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function() {
    var Admin = window.TrustLoanAdmin;

    function drawCharts(d) {
        if (typeof Chart === 'undefined') {
            setTimeout(function() { drawCharts(d); }, 100);
            return;
        }
        var overTime = d.applications_over_time || [];
        var labels = overTime.map(function(x) { return x.d; });
        var values = overTime.map(function(x) { return Number(x.cnt); });
        var ctx1 = document.getElementById('chartApplications');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: labels.length ? labels : ['Jan','Feb','Mar','Apr','May','Jun'],
                    datasets: [{ label: 'Applications', data: values.length ? values : [0,0,0,0,0,0], borderColor: 'rgb(37, 99, 235)', backgroundColor: 'rgba(37, 99, 235, 0.1)', fill: true, tension: 0.3 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            // Counts are whole applications — avoid 0.2, 0.4… ticks from auto scale
                            ticks: { stepSize: 1, precision: 0 }
                        }
                    }
                }
            });
        }
        var breakdown = d.status_breakdown || [];
        var statusLabels = ['New', 'In progress', 'Approved', 'Rejected'];
        var statusData = [0,0,0,0];
        breakdown.forEach(function(x) {
            var i = ['new','in_progress','approved','rejected'].indexOf((x.status || '').toLowerCase());
            if (i >= 0) statusData[i] = parseInt(x.cnt, 10) || 0;
        });
        var total = statusData.reduce(function(a, b) { return a + b; }, 0);
        if (total === 0) {
            statusLabels = ['No applications'];
            statusData = [1];
        }
        var ctx2 = document.getElementById('chartStatus');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: total === 0 ? ['#e2e8f0'] : ['#94a3b8', '#2563eb', '#16a34a', '#dc2626']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    }

    Admin.postAction('fetch_admin_stats_action', {}).then(function(res) {
        if (!res.ok || !res.data) return;
        var d = res.data;
        var v = document.getElementById('statVerifications');
        var a = document.getElementById('statApplicants');
        var l = document.getElementById('statLoans');
        if (v) v.textContent = d.verifications || 0;
        if (a) a.textContent = d.applicants || 0;
        if (l) l.textContent = d.active_loans || 0;
        drawCharts(d);
    });
})();
</script>
