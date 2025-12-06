<?php
// File: admin_karyawan_edit.php
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

$message = '';
$karyawan = null;

// Cek ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_karyawan.php?msg=" . urlencode("ID karyawan tidak valid."));
    exit();
}

$id = $_GET['id'];

// --- LOGIC UPDATE (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    
    if (empty($nama) || empty($username)) {
         $message = "Nama dan Username harus diisi!";
    } else {
        // Query Update
        $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ? WHERE id = ? AND role = 'pegawai'");
        $stmt->bind_param("ssi", $nama, $username, $id);
        
        if ($stmt->execute()) {
            $message = "Data karyawan $nama berhasil diperbarui!";
        } else {
            if ($conn->errno == 1062) {
                 $message = "Gagal memperbarui. Username sudah digunakan.";
            } else {
                 $message = "Gagal memperbarui. Terjadi kesalahan database.";
            }
        }
    }
    // Setelah update, kembali ke halaman karyawan dengan pesan
    header("Location: admin_karyawan.php?msg=" . urlencode($message));
    exit();
}

// --- AMBIL DATA LAMA (GET) ---
$q_data = $conn->prepare("SELECT id, nama, username FROM users WHERE id = ? AND role = 'pegawai'");
$q_data->bind_param("i", $id);
$q_data->execute();
$result = $q_data->get_result();
$karyawan = $result->fetch_assoc();

if (!$karyawan) {
    header("Location: admin_karyawan.php?msg=" . urlencode("Karyawan tidak ditemukan atau bukan pegawai."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style> .form-group { margin-bottom: 15px; } .form-control { padding: 10px; width: 100%; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; } </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="content">
        <div class="navbar"><h2>Edit Data Karyawan</h2></div>
        
        <h1>Edit Karyawan: <?= htmlspecialchars($karyawan['nama']) ?></h1>
        
        <div class="card" style="max-width: 500px;">
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap:</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($karyawan['nama']) ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Username (Unique):</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($karyawan['username']) ?>" class="form-control" required>
                </div>
                
                <button type="submit" name="edit" class="btn btn-primary" style="background-color: #007bff;">Simpan Perubahan</button>
                <a href="admin_karyawan.php" class="btn" style="background-color: #6c757d; color: white; padding: 10px; border-radius: 5px; text-decoration: none;">Batal</a>
            </form>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>