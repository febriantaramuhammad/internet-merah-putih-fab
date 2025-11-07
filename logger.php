<?php
// logger.php
function logAction($action, $description = '') {
    global $pdo;
    
    // Safety check: pastikan $pdo ada
    if (!$pdo instanceof PDO) {
        error_log("Logger gagal: \$pdo tidak tersedia");
        return;
    }

    $userId = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255); // batasi panjang

    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, username, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $username, $action, $description, $ip, $userAgent]);
    } catch (Exception $e) {
        error_log("Gagal menyimpan log: " . $e->getMessage());
    }
}
?>