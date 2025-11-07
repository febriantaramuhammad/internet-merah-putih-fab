<?php
include 'auth.php';
include 'rbac.php';
include 'config.php';

$message = '';

// Handle form submit: buat tiket baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!canEditAssurance()) {
        die("Akses ditolak.");
    }

    $customer = trim($_POST['customer'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $issue = trim($_POST['issue'] ?? '');

    if ($customer && $service && $issue) {
        try {
            $datePart = date('Ymd');
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM assurance_tickets WHERE ticket_id LIKE 'TKT-{$datePart}-%'");
            $count = $stmt->fetch()['cnt'] + 1;
            $ticketId = "TKT-{$datePart}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
            $slaDeadline = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $pdo->prepare("
                INSERT INTO assurance_tickets 
                (ticket_id, customer_name, service_affected, issue_description, sla_deadline)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$ticketId, $customer, $service, $issue, $slaDeadline]);
            $message = "✅ Tiket $ticketId berhasil dibuat. SLA: 24 jam.";
        } catch (Exception $e) {
            $message = "❌ Gagal membuat tiket.";
        }
    } else {
        $message = "❌ Semua kolom wajib diisi.";
    }
}

// Handle tombol "Selesaikan"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve') {
    if (!canEditAssurance()) {
        die("Akses ditolak.");
    }

    $ticketId = $_POST['ticket_id'];
    try {
        $stmt = $pdo->prepare("UPDATE assurance_tickets SET status = 'resolved', resolved_at = NOW() WHERE ticket_id = ? AND status = 'open'");
        $stmt->execute([$ticketId]);
        if ($stmt->rowCount() > 0) {
            $message = "✅ Tiket $ticketId berhasil diselesaikan.";
        } else {
            $message = "❌ Tiket sudah diselesaikan atau tidak ditemukan.";
        }
    } catch (Exception $e) {
        $message = "❌ Gagal: " . $e->getMessage();
    }
}

$stmt = $pdo->query("
    SELECT *, 
    CASE 
        WHEN status = 'resolved' THEN 'Selesai'
        WHEN NOW() > sla_deadline THEN 'SLA Breach!'
        ELSE 'On Time'
    END as sla_status
    FROM assurance_tickets 
    ORDER BY reported_at DESC
");
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assurance - FAB</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* === FORM SECTION === */
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .form-section h3 {
            margin-bottom: 15px;
            color: #d32f2f;
            font-size: 1.1em;
        }

        .form-section input,
        .form-section textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            margin-bottom: 10px;
        }

        .form-section input:focus,
        .form-section textarea:focus {
            outline: none;
            border-color: #d32f2f;
        }

        .form-section button {
            width: 100%;
            padding: 12px;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .form-section button:hover {
            background: #b71c1c;
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

        .status-open { background: #ffebee; color: #c62828; }
        .status-in_progress { background: #fff3e0; color: #ef6c00; }
        .status-resolved { background: #e8f5e8; color: #2e7d32; }

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
            .form-section,
            .table-section {
                padding: 15px;
            }
            .form-section input,
            .form-section textarea {
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
            <h1>Assurance</h1>
            <a href="dashboard">← Kembali ke Dashboard</a>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Form Lapor Gangguan — HANYA UNTUK ADMIN/OPERATOR -->
        <?php if (canEditAssurance()): ?>
            <section class="form-section">
                <h3>Laporkan Gangguan Layanan</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="text" name="customer" placeholder="Nama Pelanggan" required>
                    <input type="text" name="service" placeholder="Layanan Terdampak" required>
                    <textarea name="issue" placeholder="Deskripsi gangguan" rows="4" required></textarea>
                    <button type="submit">Buat Tiket Gangguan</button>
                </form>
            </section>
        <?php endif; ?>

        <!-- Tabel Tiket -->
        <section class="table-section">
            <h3>Daftar Tiket Gangguan</h3>
            <?php if ($tickets): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Tiket</th>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th>SLA</th>
                            <th>Dilaporkan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['ticket_id']) ?></td>
                                <td><?= htmlspecialchars($t['customer_name']) ?></td>
                                <td><?= htmlspecialchars($t['service_affected']) ?></td>
                                <td>
                                    <span class="status status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($t['sla_status'] === 'SLA Breach!'): ?>
                                        <span style="color: #c62828; font-weight: bold;">⚠️ Breach</span>
                                    <?php else: ?>
                                        <span style="color: #2e7d32;">✅ On Time</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M H:i', strtotime($t['reported_at'])) ?></td>
                                <td>
                                    <?php if (canEditAssurance() && $t['status'] === 'open'): ?>
                                        <button type="button" class="btn-small" 
                                            onclick="confirmResolve('<?= htmlspecialchars($t['ticket_id']) ?>')">
                                            ✅ Selesaikan
                                        </button>
                                    <?php else: ?>
                                        <span class="status status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Belum ada tiket gangguan.</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
    function confirmResolve(ticketId) {
        Swal.fire({
            title: 'Selesaikan tiket?',
            text: `Tiket ${ticketId} akan ditandai sebagai selesai.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesai!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const action = document.createElement('input');
                action.name = 'action';
                action.value = 'resolve';
                form.appendChild(action);
                
                const ticketIdInput = document.createElement('input');
                ticketIdInput.name = 'ticket_id';
                ticketIdInput.value = ticketId;
                form.appendChild(ticketIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>
</body>
</html>