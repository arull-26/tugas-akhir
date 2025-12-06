<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, nama, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: pegawai_dashboard.php");
            }
            exit();
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Absensi Karyawan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS Tambahan untuk Login */
        body { background: linear-gradient(135deg, #6c5ce7 0%, #0984e3 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-container { max-width: 400px; width: 90%; padding: 40px; background-color: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .login-container h2 { text-align: center; color: #6c5ce7; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-primary { width: 100%; padding: 12px; border: none; background-color: #6c5ce7; color: white; border-radius: 8px; cursor: pointer; transition: background-color 0.3s; }
        .btn-primary:hover { background-color: #5544c7; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Absensi Digital</h2>
        <?php if (isset($error)): ?>
            <p style="color: red; text-align: center;"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn-primary">LOGIN</button>
        </form>
    </div>
</body>
</html>