<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Opsional: include rbac.php jika perlu proteksi per modul