<?php
// rbac.php — Role-Based Access Control Helper

function requireRole($allowedRoles) {
    $userRole = $_SESSION['role'] ?? null;
    if (!$userRole || !in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        die("<h2>❌ Akses Ditolak</h2><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>");
    }
}

function canEditFulfillment() {
    return in_array($_SESSION['role'] ?? '', ['admin', 'operator']);
}

function canEditAssurance() {
    return in_array($_SESSION['role'] ?? '', ['admin', 'operator']);
}

function canEditBilling() {
    return in_array($_SESSION['role'] ?? '', ['admin', 'operator']);
}
?>