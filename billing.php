<?php
include 'auth.php';
include 'rbac.php';
include 'config.php';

$message = '';
// Handle pembayaran (jika ada aksi "Bayar")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invoice_id'])) {
    if (!canEditBilling()) {
        die("Akses ditolak.");
    }

    $invoiceId = $_POST['invoice_id'];
    $stmt = $pdo->prepare("UPDATE billing_invoices SET status = 'paid', paid_at = NOW() WHERE invoice_id = ? AND status = 'unpaid'");
    $stmt->execute([$invoiceId]);
    $message = "✅ Pembayaran untuk invoice $invoiceId berhasil dicatat.";
}

$invoices = $pdo->query("
    SELECT * FROM billing_invoices 
    ORDER BY due_date ASC, created_at DESC
")->fetchAll();

$summary = $pdo->query("
    SELECT 
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount
    FROM billing_invoices
")->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - FAB</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* === KPI CARDS === */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .kpi-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #d32f2f;
            transition: transform 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
        }

        .kpi-title {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 1.6em;
            font-weight: bold;
            color: #d32f2f;
        }

        .kpi-sub {
            font-size: 0.85em;
            color: #2e7d32;
            margin-top: 4px;
        }

        .kpi-alert {
            margin-top: 8px;
            padding: 4px 8px;
            background: #ffebee;
            color: #c62828;
            border-radius: 4px;
            font-size: 0.85em;
            display: inline-block;
        }

        /* === TABLE SECTION === */
        .table-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .table-section h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1em;
        }

        .table-section table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .table-section th,
        .table-section td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table-section th {
            background: #f5f5f5;
            color: #444;
            font-weight: 600;
            font-size: 0.9em;
        }

        .status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .status-paid { background: #e8f5e8; color: #2e7d32; }
        .status-unpaid { background: #ffebee; color: #c62828; }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-small:hover {
            background: #388E3C;
        }

        /* === ALERT === */
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        @media (max-width: 768px) {
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            .table-section {
                padding: 15px;
            }
            .table-section th,
            .table-section td {
                padding: 10px;
                font-size: 0.9em;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="assets/images/logo-login.png" alt="imp logo" width="100%">
            </div>
            <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="fulfillment" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'fulfillment.php' ? 'active' : '' ?>">📦 Fulfillment</a>
            <a href="assurance" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'assurance.php' ? 'active' : '' ?>">🛠️ Assurance</a>
            <a href="billing" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'billing.php' ? 'active' : '' ?>">💰 Billing</a>
            <a href="logout" class="nav-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <?php if (canEditFulfillment()): ?>
                <div style="text-align: right; margin-bottom: 15px;">
                    <a href="export.php?module=fulfillment" class="btn-export">📥 Ekspor ke CSV</a>
                </div>
            <?php endif; ?>
            <h1>Modul Billing</h1>
            <a href="dashboard">← Kembali ke Dashboard</a>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Ringkasan Keuangan -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">Total Tagihan</div>
                <div class="kpi-value">Rp <?= number_format($summary['total_amount'] ?? 0, 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Tertagih</div>
                <div class="kpi-value">Rp <?= number_format($summary['paid_amount'] ?? 0, 0, ',', '.') ?></div>
                <div class="kpi-sub">Terbayar: Rp <?= number_format($summary['paid_amount'] ?? 0, 0, ',', '.') ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Tertunggak</div>
                <div class="kpi-value" style="color: #c62828;">
                    Rp <?= number_format(($summary['total_amount'] ?? 0) - ($summary['paid_amount'] ?? 0), 0, ',', '.') ?>
                </div>
            </div>
        </div>

        <!-- Tabel Invoice -->
        <section class="table-section">
            <h3>Daftar Invoice</h3>
            <?php if ($invoices): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Invoice</th>
                            <th>Pelanggan</th>
                            <th>Order ID</th>
                            <th>Jumlah</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><?= htmlspecialchars($inv['invoice_id']) ?></td>
                                <td><?= htmlspecialchars($inv['customer_name']) ?></td>
                                <td><?= htmlspecialchars($inv['order_id']) ?></td>
                                <td>Rp <?= number_format($inv['amount'], 0, ',', '.') ?></td>
                                <td><?= date('d M Y', strtotime($inv['due_date'])) ?></td>
                                <td>
                                    <?php if ($inv['status'] === 'paid'): ?>
                                        <span class="status status-paid">Lunas</span>
                                    <?php else: ?>
                                        <span class="status status-unpaid">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (canEditBilling() && $inv['status'] === 'unpaid'): ?>
                                        <button type="button" class="btn-small" 
                                            onclick="confirmPayment('<?= htmlspecialchars($inv['invoice_id']) ?>')">
                                            Tandai Lunas
                                        </button>
                                    <?php else: ?>
                                        <?php if ($inv['status'] === 'paid'): ?>
                                            <span class="status status-paid">Lunas</span>
                                        <?php else: ?>
                                            <span class="status status-unpaid">Belum Bayar</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Belum ada invoice.</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
    function confirmPayment(invoiceId) {
        Swal.fire({
            title: 'Konfirmasi Pembayaran?',
            text: `Invoice ${invoiceId} akan ditandai sebagai LUNAS.`,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lunaskan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const invoiceIdInput = document.createElement('input');
                invoiceIdInput.name = 'invoice_id';
                invoiceIdInput.value = invoiceId;
                form.appendChild(invoiceIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>
</body>
</html>