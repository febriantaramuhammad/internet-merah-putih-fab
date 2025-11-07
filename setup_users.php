<?php
include 'config.php';

// Hash password yang valid
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert atau update user
$users = [
    ['username' => 'admin',    'role' => 'admin'],
    ['username' => 'operator', 'role' => 'operator'],
    ['username' => 'viewer',   'role' => 'viewer']
];

foreach ($users as $user) {
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, role) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)
    ");
    $stmt->execute([$user['username'], $hash, $user['role']]);
}

echo "<h2>✅ User berhasil disiapkan!</h2>";
echo "<p>Gunakan kredensial berikut:</p>";
echo "<ul>";
echo "<li><strong>admin</strong> / admin123</li>";
echo "<li><strong>operator</strong> / admin123</li>";
echo "<li><strong>viewer</strong> / admin123</li>";
echo "</ul>";
echo "<p><a href='login.php'>→ Login Sekarang</a></p>";
?>