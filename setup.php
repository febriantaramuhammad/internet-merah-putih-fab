<?php
// Tambahkan di awal setup.php
session_start();
if (isset($_SESSION['setup_done'])) {
    die("<h2>✅ Setup sudah pernah dijalankan.</h2><p>Hapus file ini untuk keamanan.</p>");
}

// ... (isi setup seperti sebelumnya)

// Di akhir, set flag
$_SESSION['setup_done'] = true;

// setup.php - Jalankan sekali di browser untuk buat DB + user
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `fab_db`");
    $pdo->exec("USE `fab_db`");

    // Buat tabel users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Hash password 'admin123'
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    // Insert atau update user 'admin'
    $stmt = $pdo->prepare("
        INSERT INTO `users` (`username`, `password`) 
        VALUES ('admin', ?) 
        ON DUPLICATE KEY UPDATE `password` = VALUES(`password`)
    ");
    $stmt->execute([$hash]); // ⚠️ Ini yang hilang!

    // Buat tabel fulfillment_orders
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `fulfillment_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` VARCHAR(50) NOT NULL UNIQUE,
            `customer_name` VARCHAR(100) NOT NULL,
            `service_type` VARCHAR(50) NOT NULL,
            `status` ENUM('pending','processing','completed','failed') DEFAULT 'pending',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Insert data demo
    $pdo->exec("INSERT IGNORE INTO fulfillment_orders (order_id, customer_name, service_type, status) VALUES
    ('ORD-001', 'PT Telkom', 'VSAT Enterprise', 'completed'),
    ('ORD-002', 'PT Indosat', 'Internet Satelit', 'processing'),
    ('ORD-003', 'Kementerian Kominfo', 'Backbone Link', 'pending')
    ");

    // Update data demo (opsional, karena ORD-001 sudah completed)
    $pdo->exec("UPDATE fulfillment_orders SET status = 'completed' WHERE id = 1");

    echo "<h2>✅ Setup berhasil! Database dan data demo telah dibuat.</h2>";
    echo "<p>Akun: <strong>admin</strong> / <strong>admin123</strong></p>";

} catch (PDOException $e) {
    die("<h2>❌ Setup gagal:</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>");
}
?>