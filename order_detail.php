<?php
include 'auth.php';
include 'config.php';

$order_id = $_GET['id'] ?? '';
if (!$order_id) {
    die("ID order tidak ditemukan.");
}

// Ambil data order + invoice
$stmt = $pdo->prepare("
    SELECT f.*, 
           i.id as invoice_id,
           i.invoice_id as inv_number,
           i.amount,
           i.due_date,
           i.status as invoice_status
    FROM fulfillment_orders f
    LEFT JOIN billing_invoices i ON f.order_id = i.order_id
    WHERE f.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order tidak ditemukan.");
}

// Ambil log aktivitas
$stmt = $pdo->prepare("SELECT * FROM fulfillment_logs WHERE order_id = ? ORDER BY performed_at DESC");
$stmt->execute([$order_id]);
$logs = $stmt->fetchAll();

// Ambil tiket assurance terkait (misal: berdasarkan order_id di deskripsi)
$stmt = $pdo->prepare("
    SELECT * FROM assurance_tickets 
    WHERE issue_description LIKE ? 
    ORDER BY reported_at DESC
");
$stmt->execute(["%$order_id%"]);
$tickets = $stmt->fetchAll();

// Handle tombol "Buat Invoice"
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    try {
        if (!empty($order['invoice_id'])) {
            $message = "❌ Invoice sudah ada.";
        } else {
            $invoiceId = 'INV-' . strtoupper(substr(uniqid(), 0, 8));
            $dueDate = date('Y-m-d', strtotime('+14 days'));

            $stmt = $pdo->prepare("
                INSERT INTO billing_invoices 
                (invoice_id, order_id, customer_name, amount, due_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$invoiceId, $order['order_id'], $order['customer_name'], $order['revenue'], $dueDate]);

            $message = "✅ Invoice $invoiceId berhasil dibuat.";
            // Refresh data
            $stmt = $pdo->prepare("
                SELECT f.*, 
                       i.id as invoice_id,
                       i.invoice_id as inv_number,
                       i.amount,
                       i.due_date,
                       i.status as invoice_status
                FROM fulfillment_orders f
                LEFT JOIN billing_invoices i ON f.order_id = i.order_id
                WHERE f.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
        }
    } catch (Exception $e) {
        $message = "❌ Gagal membuat invoice.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Order - <?= htmlspecialchars($order['order_id']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .toast { position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 6px; color: white; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; transform: translateX(120%); transition: transform 0.3s ease-out; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #4CAF50; }
        .toast.error { background: #F44336; }

        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px; }
        .detail-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .detail-card h3 { margin-bottom: 15px; color: #d32f2f; font-size: 1.1em; }
        .detail-item { margin-bottom: 10px; }
        .detail-label { font-weight: bold; color: #555; }
        .detail-value { margin-left: 8px; }

        .btn-invoice { background: #2196F3; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-invoice:hover { background: #0b7dda; }

        .status-invoice { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: bold; }
        .status-paid { background: #e8f5e8; color: #2e7d32; }
        .status-unpaid { background: #ffebee; color: #c62828; }

        /* Log & Tiket */
        .log-item, .ticket-item {
            padding: 10px;
            border-left: 3px solid #d32f2f;
            margin-bottom: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .log-time, .ticket-time {
            font-size: 0.85em;
            color: #666;
        }
    </style>
</head>
<body class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">🛰️ FAB</div>
            <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard" class="nav-item">📊 Dashboard</a>
            <a href="fulfillment" class="nav-item active">📦 Fulfillment</a>
            <a href="assurance" class="nav-item">🛠️ Assurance</a>
            <a href="billing" class="nav-item">💰 Billing</a>
            <a href="logout" class="nav-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1>Detail Order: <?= htmlspecialchars($order['order_id']) ?></h1>
            <a href="fulfillment">← Kembali ke Fulfillment</a>
        </header>

        <?php if ($message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const msg = <?= json_encode($message) ?>;
                    const type = msg.includes('❌') ? 'error' : 'success';
                    showToast(msg.replace('✅ ', '').replace('❌ ', ''), type);
                });
            </script>
        <?php endif; ?>

        <div class="detail-grid">
            <!-- Detail Order -->
            <div class="detail-card">
                <h3>Data Pelanggan & Layanan</h3>
                <div class="detail-item">
                    <span class="detail-label">ID Order:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['order_id']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Pelanggan:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Layanan:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['service_type']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Nilai Order:</span>
                    <span class="detail-value">Rp <?= number_format($order['revenue'], 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- Status & Waktu -->
            <div class="detail-card">
                <h3>Status & Waktu</h3>
                <div class="detail-item">
                    <span class="detail-label">Status Order:</span>
                    <span class="detail-value">
                        <span class="status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Dibuat:</span>
                    <span class="detail-value"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <?php if (!empty($order['updated_at']) && $order['updated_at'] !== $order['created_at']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Diupdate:</span>
                        <span class="detail-value"><?= date('d M Y H:i', strtotime($order['updated_at'])) ?></span>
                    </div>
                    <?php if (!empty($order['updated_by'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Oleh:</span>
                            <span class="detail-value"><?= htmlspecialchars($order['updated_by']) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Invoice -->
            <div class="detail-card">
                <h3>Invoice Terkait</h3>
                <?php if (!empty($order['invoice_id'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">ID Invoice:</span>
                        <span class="detail-value"><?= htmlspecialchars($order['inv_number']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Jumlah:</span>
                        <span class="detail-value">Rp <?= number_format($order['amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Jatuh Tempo:</span>
                        <span class="detail-value"><?= date('d M Y', strtotime($order['due_date'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <?php if ($order['invoice_status'] === 'paid'): ?>
                                <span class="status-invoice status-paid">Lunas</span>
                            <?php else: ?>
                                <span class="status-invoice status-unpaid">Belum Bayar</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php else: ?>
                    <p>Belum ada invoice.</p>
                    <?php if ($order['status'] === 'completed'): ?>
                        <form method="POST" style="margin-top: 10px;">
                            <button type="submit" name="create_invoice" class="btn-invoice">
                                + Buat Invoice
                            </button>
                        </form>
                    <?php else: ?>
                        <p><em>Invoice hanya bisa dibuat setelah order selesai.</em></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Log Aktivitas -->
            <div class="detail-card">
                <h3>Log Aktivitas</h3>
                <?php if ($logs): ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item">
                            <div><strong><?= htmlspecialchars($log['action']) ?></strong></div>
                            <div>Dari: <?= htmlspecialchars($log['old_status']) ?> → <?= htmlspecialchars($log['new_status']) ?></div>
                            <div class="log-time">
                                <?= htmlspecialchars($log['performed_by']) ?> • 
                                <?= date('d M Y H:i', strtotime($log['performed_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Belum ada log aktivitas.</p>
                <?php endif; ?>
            </div>

            <!-- Tiket Assurance Terkait -->
            <div class="detail-card">
                <h3>Tiket Assurance Terkait</h3>
                <?php if ($tickets): ?>
                    <?php foreach ($tickets as $t): ?>
                        <div class="ticket-item">
                            <div><strong><?= htmlspecialchars($t['ticket_id']) ?></strong></div>
                            <div><?= htmlspecialchars(substr($t['issue_description'], 0, 50)) ?>...</div>
                            <div>
                                <span class="status status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                <?php if ($t['sla_breached'] == 1): ?>
                                    <span style="color: #c62828;">⚠️ Breach</span>
                                <?php endif; ?>
                            </div>
                            <div class="ticket-time">
                                <?= date('d M Y H:i', strtotime($t['reported_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Tidak ada tiket gangguan terkait order ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

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
    </script>
</body>
</html>