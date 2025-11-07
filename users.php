<?php
include 'auth.php';
include 'rbac.php';
requireRole(['admin']); // Hanya admin yang boleh akses
include 'config.php';

// Logika tambah/ubah user (sederhana)
?>
<!-- UI untuk tambah user dengan role -->