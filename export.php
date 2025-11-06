<?php
include 'auth.php';
include 'config.php';

$module = $_GET['module'] ?? '';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fab_export_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['FAB - Data Export - ' . date('Y-m-d H:i:s')]);
fputcsv($output, []); // baris kosong

if ($module === 'fulfillment') {
    fputcsv($output, ['ID Order', 'Pelanggan', 'Layanan', 'Status', 'Nilai (Rp)', 'Dibuat']);
    $stmt = $pdo->query("SELECT * FROM fulfillment_orders ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['order_id'],
            $row['customer_name'],
            $row['service_type'],
            $row['status'],
            number_format($row['revenue'], 2, ',', '.'),
            $row['created_at']
        ]);
    }
} elseif ($module === 'assurance') {
    fputcsv($output, ['ID Tiket', 'Pelanggan', 'Layanan', 'Status', 'SLA Breach', 'Dilaporkan']);
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
            $row['status'],
            $row['breach_text'],
            $row['reported_at']
        ]);
    }
} elseif ($module === 'billing') {
    fputcsv($output, ['ID Invoice', 'Pelanggan', 'Order ID', 'Jumlah (Rp)', 'Jatuh Tempo', 'Status']);
    $stmt = $pdo->query("SELECT * FROM billing_invoices ORDER BY due_date ASC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['invoice_id'],
            $row['customer_name'],
            $row['order_id'],
            number_format($row['amount'], 2, ',', '.'),
            $row['due_date'],
            $row['status']
        ]);
    }
} else {
    fputcsv($output, ['Error: Modul tidak valid']);
}

fclose($output);
exit();
?>