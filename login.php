<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'config.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'] ?? 'user';
        header('Location: dashboard');
        exit();
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FAB Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('assets/images/background-login.png') no-repeat center center;
            background-size: cover;          /* Menutupi seluruh area */
            background-attachment: fixed;    /* Agar gambar tetap stabil saat scroll */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;                       /* Hilangkan margin default browser */
            padding: 0;                      /* Hilangkan padding default */
            color: #333;
        }

        .login-container {
            display: flex;
            width: 900px;
            height: 500px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .login-image {
            width: 40%;
            /*background: url('https://via.placeholder.com/500x500/007bff/ffffff?text=Submarine+Cable+Surveillance') no-repeat center center;*/
            background-size: cover;
        }

        .login-image img {
            width: 100%;
            height: 100%;
            object-fit: fill;
            vertical-align: middle;
        }

        .login-form {
            width: 60%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 40px;
            /*filter: brightness(0) saturate(100%) invert(27%) sepia(94%) saturate(298%) hue-rotate(186deg) brightness(94%) contrast(92%);*/
        }

        h2 {
            font-size: 1.4em;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #3f51b5;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #d32f2f;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover {
            background: #000000;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.85em;
            color: #777;
        }

        @media (max-width: 900px) {
            .login-container {
                width: 100%;
                height: auto;
                flex-direction: column;
            }
            .login-image {
                width: 100%;
                height: 200px;
            }
            .login-form {
                width: 100%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Ilustrasi Kiri -->
        <div class="login-image">
            <img src="assets/images/login-img.png" alt="fab image">
        </div>

        <!-- Form Login Kanan -->
        <div class="login-form">
            <div class="logo">
                <img src="assets/images/logo-login.png" alt="Telkomsat Logo">
            </div>
            <h2>F . A <span style="color: #d32f2f;">. B</span></h2>
            <!-- <h2>Fulfillment . Assurance <span style="color: #d32f2f;"> . Billing</span></h2> -->

            <?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="username" placeholder="NIK Pegawai" required autocomplete="off">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="footer">
                © Telkomsat 2025
            </div>
        </div>
    </div>
</body>
</html>