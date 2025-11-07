<?php
include 'auth.php';
include 'config.php';

// Ambil data Fulfillment
$fulfillmentStats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(revenue) as total_revenue
    FROM fulfillment_orders
")->fetch();

// Ambil data Assurance
$assuranceStats = $pdo->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN resolved_at IS NULL AND NOW() > sla_deadline THEN 1 ELSE 0 END) as breached
    FROM assurance_tickets
")->fetch();

// Ambil data Billing
$billingStats = $pdo->query("
    SELECT 
        SUM(amount) as total_revenue,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as collected
    FROM billing_invoices
")->fetch();

// Data untuk grafik
$chartData = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM fulfillment_orders 
    GROUP BY status
")->fetchAll();

$chartLabels = [];
$chartValues = [];
$statusColors = [
    'pending' => '#FFA726',
    'processing' => '#29B6F6',
    'completed' => '#66BB6A',
    'failed' => '#EF5350'
];

foreach ($chartData as $row) {
    $chartLabels[] = ucfirst($row['status']);
    $chartValues[] = (int)$row['count'];
}

$chartColors = [];
foreach ($chartData as $row) {
    $chartColors[] = $statusColors[$row['status']] ?? '#9E9E9E';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FAB</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Toast Notification (dengan CSS di atas) -->
<script>
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

// Tampilkan toast jika ada pesan dari PHP
<?php if (!empty($message)): ?>
    document.addEventListener('DOMContentLoaded', () => {
        const msg = <?= json_encode($message) ?>;
        const type = msg.includes('❌') ? 'error' : 'success';
        showToast(msg.replace('✅ ', '').replace('❌ ', ''), type);
    });
<?php endif; ?>
</script>
</head>
<body class="app-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="assets/images/logo-login.png" alt="imp logo" width="100%">
            </div>
            <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
            <span class="user-role"><?= ucfirst($_SESSION['role']) ?></span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                📊 Dashboard
            </a>
            <a href="fulfillment" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'fulfillment.php' ? 'active' : '' ?>">
                📦 Fulfillment
            </a>
            <a href="assurance" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'assurance.php' ? 'active' : '' ?>">
                🛠️ Assurance
            </a>
            <a href="billing" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'billing.php' ? 'active' : '' ?>">
                💰 Billing
            </a>
            <a href="logout" class="nav-item logout">
                🚪 Logout
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="main-header">
            <h1>Dashboard</h1>
            <div class="user-info">
                Halo, <?= htmlspecialchars($_SESSION['username']) ?> | <a href="logout.php">Logout</a>
            </div>
        </header>

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">Total Order</div>
                <div class="kpi-value"><?= number_format($fulfillmentStats['total_orders'], 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Order Selesai</div>
                <div class="kpi-value"><?= number_format($fulfillmentStats['completed'], 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Nilai Order</div>
                <div class="kpi-value">Rp <?= number_format($fulfillmentStats['total_revenue'], 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Pendapatan</div>
                <div class="kpi-value">Rp <?= number_format($billingStats['collected'] ?? 0, 0, ',', '.') ?></div>
                <div class="kpi-sub">Terbayar: Rp <?= number_format($billingStats['collected'] ?? 0, 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Tiket Gangguan</div>
                <div class="kpi-value"><?= number_format($assuranceStats['total_tickets'], 0, ',', '.') ?></div>
                <?php if ($assuranceStats['breached'] > 0): ?>
                    <div class="kpi-alert"><span>⚠️ <?= $assuranceStats['breached'] ?> breach</span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHART -->
        <div class="chart-section">
            <div class="chart-container">
                <h2>Distribusi Status Order</h2>
                <canvas id="fulfillmentChart" width="300" height="150"></canvas>
            </div>
        </div>
    </main>

    <script>
    const ctx = document.getElementById('fulfillmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Jumlah Order',
                data: <?= json_encode($chartValues) ?>,  // ← Tambahkan "data:"
                backgroundColor: <?= json_encode($chartColors) ?>,
                borderWidth: 0,
                borderRadius: 4
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { display: false }
                }
            },
            layout: {
                padding: { top: 5, bottom: 5, left: 5, right: 5 }
            }
        }
    });
</script>
</body>
</html>