<?php
// File: admin_lokasi.php
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

$message = $_GET['msg'] ?? '';

// --- LOGIKA TAMBAH LOKASI (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_lokasi'])) {
    $nama = trim($_POST['nama_kantor']);
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $radius = $_POST['radius'] ?? 100; // Default 100m
    
    if (empty($nama) || !is_numeric($lat) || !is_numeric($lng)) {
        $message = "Nama, Latitude, dan Longitude harus diisi dengan benar.";
    } else {
        $stmt = $conn->prepare("INSERT INTO lokasi_kantor (nama_lokasi, latitude, longitude, radius) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sddi", $nama, $lat, $lng, $radius);
        
        if ($stmt->execute()) {
            $message = "Lokasi kantor '$nama' berhasil ditambahkan.";
        } else {
            $message = "Gagal menambahkan lokasi. Nama lokasi mungkin sudah ada.";
        }
    }
    header("Location: admin_lokasi.php?msg=" . urlencode($message));
    exit();
}

// --- LOGIKA HAPUS LOKASI (GET) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Cegah penghapusan jika ada karyawan yang masih terhubung ke lokasi ini
    $check_users = $conn->query("SELECT id FROM users WHERE lokasi_id = $id");
    if ($check_users->num_rows > 0) {
        $message = "Gagal menghapus lokasi. Masih ada karyawan yang terhubung.";
    } else {
        $stmt = $conn->prepare("DELETE FROM lokasi_kantor WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Lokasi kantor berhasil dihapus.";
        } else {
            $message = "Gagal menghapus lokasi.";
        }
    }
    header("Location: admin_lokasi.php?msg=" . urlencode($message));
    exit();
}

// --- AMBIL DATA LOKASI ---
$lokasi_data = $conn->query("SELECT * FROM lokasi_kantor ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Lokasi Kantor</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .location-card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: inline-block; width: 300px; margin-right: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .modal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border-radius: 8px; width: 80%; max-width: 500px; }
        .form-group { margin-bottom: 15px; } .form-control { padding: 10px; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="content">
        <div class="navbar">
            <h2>Lokasi Kantor</h2>
            <button id="tambahLokasiBtn" class="btn btn-primary">+ Tambah Lokasi</button>
        </div>
        
        <h1>Lokasi Kantor</h1>
        <p>Gunakan Google Maps untuk mendapatkan Latitude dan Longitude yang akurat.</p>

        <?php if (!empty($message)): ?>
            <div class="alert" style="background-color: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="lokasi-list">
            <?php while($lokasi = $lokasi_data->fetch_assoc()): ?>
            <div class="location-card">
                <h3><?= htmlspecialchars($lokasi['nama_lokasi']) ?></h3>
                <p>Lat: <?= $lokasi['latitude'] ?><br>
                Lon: <?= $lokasi['longitude'] ?><br>
                Radius: <?= $lokasi['radius'] ?> m</p>
                
                <a href="admin_lokasi.php?action=delete&id=<?= $lokasi['id'] ?>" 
                   onclick="return confirm('Yakin hapus lokasi ini?')" 
                   class="btn" style="background-color: #dc3545; color: white; padding: 5px 10px; text-decoration: none;">Hapus</a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="tambahLokasiModal" class="modal">
        <div class="modal-content">
            <span class="close" style="float: right; font-size: 28px; font-weight: bold;">&times;</span>
            <h2>Tambah Lokasi Kantor Baru</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Kantor (mis: Pusat):</label>
                    <input type="text" name="nama_kantor" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Latitude:</label>
                    <input type="number" step="any" name="latitude" placeholder="-6.xxxx" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Longitude:</label>
                    <input type="number" step="any" name="longitude" placeholder="106.xxxx" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Radius Toleransi (Meter, default 100):</label>
                    <input type="number" name="radius" placeholder="100" value="100" class="form-control">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="document.getElementById('tambahLokasiModal').style.display='none'" style="background-color: #6c757d; color: white;">Batal</button>
                    <button type="submit" name="tambah_lokasi" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var modal = document.getElementById("tambahLokasiModal");
        var btn = document.getElementById("tambahLokasiBtn");
        var span = modal.getElementsByClassName("close")[0];

        btn.onclick = function() { modal.style.display = "block"; }
        span.onclick = function() { modal.style.display = "none"; }
        window.onclick = function(event) {
            if (event.target == modal) { modal.style.display = "none"; }
        }
    </script>
</body>
</html>