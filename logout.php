<?php
session_start();
if (isset($_SESSION['user_id'])) {
    include 'config.php';
    include 'logger.php';
    logAction('logout', 'Logout berhasil');
}
session_destroy();
header('Location: index.php');
exit();
?>