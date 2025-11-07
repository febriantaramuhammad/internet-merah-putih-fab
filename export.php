<?php
include 'auth.php';
include 'rbac.php'; // Pastikan file ini ada
include 'config.php';

// 🔒 Hanya admin dan operator yang boleh ekspor
if (!canEditFulfillment() && !canEditAssurance() && !canEditBilling()) {
    http_response_code(403);
    die("<h2>❌ Akses Ditolak</h2><p>Hanya admin dan operator yang boleh mengekspor data.</p>");
}

$module = $_GET['module'] ?? '';
if (!in_array($module, ['fulfillment', 'assurance', 'billing'])) {
    die('Modul tidak valid.');
}

// Set header untuk download file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fab_export_' . $module . '_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

// Judul file
fputcsv($output, ['FAB Dashboard - Ekspor Data - ' . date('Y-m-d H:i:s')]);
fputcsv($output, []); // baris kosong

// Ambil data sesuai modul
if ($module === 'fulfillment') {
    fputcsv($output, ['ID Order', 'Pelanggan', 'Layanan', 'Status', 'Nilai (Rp)', 'Dibuat', 'Diupdate', 'Oleh']);
    $stmt = $pdo->query("SELECT * FROM fulfillment_orders ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['order_id'],
            $row['customer_name'],
            $row['service_type'],
            $row['status'],
            number_format($row['revenue'], 2, ',', '.'),
            $row['created_at'],
            $row['updated_at'],
            $row['updated_by'] ?? '-'
        ]);
    }
} 
elseif ($module === 'assurance') {
    fputcsv($output, ['ID Tiket', 'Pelanggan', 'Layanan', 'Deskripsi', 'Status', 'Dilaporkan', 'Selesai', 'SLA Breach']);
    $stmt = $pdo->query("
        SELECT *, 
        CASE WHEN sla_breached = 1 THEN 'Ya' ELSE 'Tidak' END as breach_text
        FROM assurance_tickets ORDER BY reported_at DESC
    ");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['ticket_id'],
            $row['customer_name'],
            $row['service_affected'],
            $row['issue_description'],
            $row['status'],
            $row['reported_at'],
            $row['resolved_at'] ?? '-',
            $row['breach_text']
        ]);
    }
} 
elseif ($module === 'billing') {
    fputcsv($output, ['ID Invoice', 'Pelanggan', 'Order ID', 'Jumlah (Rp)', 'Jatuh Tempo', 'Status', 'Dibayar']);
    $stmt = $pdo->query("SELECT * FROM billing_invoices ORDER BY due_date ASC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['invoice_id'],
            $row['customer_name'],
            $row['order_id'],
            number_format($row['amount'], 2, ',', '.'),
            $row['due_date'],
            $row['status'],
            $row['paid_at'] ?? '-'
        ]);
    }
}

fclose($output);
exit();
?>