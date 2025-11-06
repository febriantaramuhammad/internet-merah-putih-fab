<?php
include 'auth.php';
include 'rbac.php';
include 'config.php';

$message = '';
// Handle form submit: buat order baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $order_id = trim($_POST['order_id'] ?? '');
    $customer = trim($_POST['customer'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $revenue = floatval($_POST['revenue'] ?? 0);

    if ($order_id && $customer && $service) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO fulfillment_orders (order_id, customer_name, service_type, status, revenue)
                VALUES (?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([$order_id, $customer, $service, $revenue]);
            $message = "✅ Order $order_id berhasil dibuat.";
        } catch (PDOException $e) {
            $message = "❌ Gagal: " . ($e->getCode() == 23000 ? "Order ID sudah ada." : "Error sistem.");
        }
    } else {
        $message = "❌ Semua kolom harus diisi.";
    }
}

// Handle tombol "Selesaikan"
// Handle tombol "Selesaikan"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $orderId = $_POST['order_id'];
    $updatedBy = $_SESSION['username'] ?? 'system'; // Pastikan ada nilai

    try {
        // Update status ke completed
        $stmt = $pdo->prepare("UPDATE fulfillment_orders SET status = 'completed', updated_at = NOW() WHERE order_id = ? AND status IN ('pending','processing')");
        $stmt->execute([$orderId]);

        if ($stmt->rowCount() > 0) {
            // Ambil data order
            $stmt = $pdo->prepare("SELECT customer_name, revenue FROM fulfillment_orders WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Order tidak ditemukan setelah update.");
            }

            // Generate Invoice ID
            $invoiceId = 'INV-' . strtoupper(substr(uniqid(), 0, 8));
            $dueDate = date('Y-m-d', strtotime('+14 days'));

            // Buat invoice
            $stmt = $pdo->prepare("
                INSERT INTO billing_invoices 
                (invoice_id, order_id, customer_name, amount, due_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$invoiceId, $orderId, $order['customer_name'], $order['revenue'], $dueDate]);

            // 🔹 CATAT LOG AKTIVITAS — pindahkan ke dalam blok sukses
            $stmt = $pdo->prepare("
                INSERT INTO fulfillment_logs (order_id, action, old_status, new_status, performed_by)
                VALUES (?, 'completed', 'pending', 'completed', ?)
            ");
            $stmt->execute([$orderId, $updatedBy]);

            $message = "✅ Order $orderId berhasil diselesaikan. Invoice $invoiceId dibuat.";
        } else {
            $message = "❌ Order sudah selesai atau tidak ditemukan.";
        }
    } catch (Exception $e) {
        $message = "❌ Gagal: " . $e->getMessage();
    }
}

// Ambil semua order
$stmt = $pdo->query("SELECT * FROM fulfillment_orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fulfillment - FAB</title>
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

        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-processing { background: #e3f2fd; color: #1565c0; }
        .status-completed { background: #e8f5e8; color: #2e7d32; }
        .status-failed { background: #ffebee; color: #c62828; }

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

        /* RESPONSIF UNTUK MOBILE */
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
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="assets/images/logo-login.png" alt="imp logo" width="100%">
            </div>
            <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
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

    <!-- Konten Utama -->
    <main class="main-content">
        <header class="main-header">
            <h1>Fulfillment</h1>
            <a href="dashboard.php">← Kembali ke Dashboard</a>
        </header>

        <?php if (canEditFulfillment()): ?>
            <section class="form-section">
                <h3>Tambah Order Baru</h3>
                <!-- ... form tetap sama ... -->
            </section>
        <?php endif; ?>

        <!-- Form Tambah Order -->
        <section class="form-section">
            <h3>Tambah Order Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="text" name="order_id" placeholder="ID Order (contoh: ORD-1001)" required>
                <input type="text" name="customer" placeholder="Nama Pelanggan" required>
                <input type="text" name="service" placeholder="Jenis Layanan" required>
                <input type="number" name="revenue" placeholder="Nilai Order (Rp)" step="0.01" min="0">
                <button type="submit">Buat Order</button>
            </form>
        </section>

        <!-- Tabel Data -->
        <section class="table-section">
            <h3>Daftar Order</h3>
            <?php if ($orders): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Order</th>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th>Nilai (Rp)</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="order_detail?id=<?= urlencode($order['order_id']) ?>" class="link-detail">
                                        <?= htmlspecialchars($order['order_id']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($order['service_type']) ?></td>
                                <td>
                                    <span class="status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                                </td>
                                <td>Rp <?= number_format($order['revenue'], 0, ',', '.') ?></td>
                                <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <?php if (canEditFulfillment() && ($order['status'] === 'pending' || $order['status'] === 'processing')): ?>
                                        <button type="button" class="btn-small" 
                                            onclick="confirmComplete('<?= htmlspecialchars($order['order_id']) ?>')">
                                            ✅ Selesaikan
                                        </button>
                                    <?php else: ?>
                                        <span class="status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Belum ada data order.</p>
            <?php endif; ?>
        </section>
    </main>
    <script>
function confirmComplete(orderId) {
    Swal.fire({
        title: 'Yakin?',
        text: `Ingin menyelesaikan order ${orderId}? Invoice otomatis dibuat.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Selesaikan!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit form via fetch atau buat form sementara
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const action = document.createElement('input');
            action.name = 'action';
            action.value = 'complete';
            form.appendChild(action);
            
            const orderIdInput = document.createElement('input');
            orderIdInput.name = 'order_id';
            orderIdInput.value = orderId;
            form.appendChild(orderIdInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</body>
</html>