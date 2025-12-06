<?php
session_start();
// Jika sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /absensi_web/admin_dashboard.php');
        exit();
    } else {
        header('Location: /absensi_web/pegawai_dashboard.php');
        exit();
    }
}

// Jika belum login, langsung redirect ke halaman login
header('Location: /absensi_web/login.php');
exit();

?>
