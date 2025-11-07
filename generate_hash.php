<?php
// generate_hash.php
$password = 'admin123'; // atau password apa pun yang Anda inginkan
echo "Password: " . $password . "<br>";
echo "Hash: " . password_hash($password, PASSWORD_DEFAULT);
?>