<?php
require 'auth_check.php';
checkRole(['pegawai']);
require 'config.php';

$user_id = $_SESSION['user_id'];

// Pastikan tabel izin ada (sederhana - create if not exists)
$create_sql = "CREATE TABLE IF NOT EXISTS izin (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    jenis_izin ENUM('Sakit','Izin') NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_akhir DATE NOT NULL,
    keterangan TEXT DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);";
$conn->query($create_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis_izin'] ?? 'Sakit';
    $mulai = $_POST['tanggal_mulai'] ?? '';
    $akhir = $_POST['tanggal_akhir'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';

    // Validasi sederhana
    if (!$mulai || !$akhir) {
        header('Location: izin_sakit.php?msg=missing_dates'); exit();
    }

    // Handle file upload (opsional)
    $file_path = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['bukti']['tmp_name'];
        $name = basename($_FILES['bukti']['name']);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $allowed = ['pdf','jpg','jpeg','png'];
        if (!in_array(strtolower($ext), $allowed)) {
            header('Location: izin_sakit.php?msg=invalid_file'); exit();
        }
        if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0777, true);
        $newname = 'izin_' . $user_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($tmp, __DIR__ . '/uploads/' . $newname)) {
            $file_path = 'uploads/' . $newname;
        }
    }

    $stmt = $conn->prepare("INSERT INTO izin (user_id, jenis_izin, tanggal_mulai, tanggal_akhir, keterangan, file_path) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('Prepare failed (izin insert): ' . $conn->error);
        header('Location: izin_sakit.php?msg=error'); exit();
    }
    $stmt->bind_param('isssss', $user_id, $jenis, $mulai, $akhir, $keterangan, $file_path);
    if ($stmt->execute()) {
        header('Location: pegawai_dashboard.php?msg=success_izin'); exit();
    } else {
        error_log('Execute failed (izin insert): ' . $stmt->error);
        header('Location: izin_sakit.php?msg=error'); exit();
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ajukan Izin / Sakit</title>
    <link rel="stylesheet" href="/absensi_web/assets/css/style.css">
    <style>
        /* Tampilkan form di bawah (bottom-center) dengan tampilan rapi */
        .container{
            max-width:720px;
            margin:0 auto;
            padding:18px 20px;
            position:fixed;
            left:50%;
            transform:translateX(-50%);
            bottom:20px;
            width:95%;
            max-width:720px;
            background:#ffffff;
            border-radius:10px;
            box-shadow:0 12px 40px rgba(0,0,0,0.15);
            z-index:9999;
            border: 1px solid rgba(0,0,0,0.06);
        }
        body { padding-bottom: 180px; }
        .izin-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px }
        .izin-title { margin:0; font-size:1.25rem; font-weight:600 }
        .izin-close { background:transparent; border:0; font-size:1.1rem; cursor:pointer; color:#666 }
        .form-row { display:flex; gap:12px; }
        .form-row .form-group { flex:1 }
        .form-group { margin-bottom:10px; display:flex; flex-direction:column }
        .form-group label { font-size:0.95rem; margin-bottom:6px; color:#333 }
        .form-control { padding:10px 12px; border:1px solid #ddd; border-radius:6px; font-size:0.95rem }
        textarea.form-control { resize:vertical }
        .btn-row { display:flex; gap:8px; justify-content:flex-end; margin-top:6px }
        .btn { padding:8px 12px; border-radius:6px; text-decoration:none; cursor:pointer }
        .btn-primary { background:#007bff; color:#fff; border:none }
        .btn-secondary { background:#6c757d; color:#fff; border:none }
        .msg { margin-bottom:10px; padding:8px 10px; border-radius:6px }
        @media (max-width:520px){ .form-row{flex-direction:column} .btn-row{justify-content:stretch} }
    </style>
</head>
<body>
    <div class="container" id="izinPanel">
        <div class="izin-header">
            <h2 class="izin-title">Ajukan Izin / Sakit</h2>
            <button class="izin-close" id="closeIzinBtn" aria-label="Tutup">✕</button>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg" style="background:#f8d7da;color:#721c24"><?=htmlspecialchars($_GET['msg'])?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Jenis</label>
                <select name="jenis_izin" class="form-control">
                    <option value="Sakit">Sakit</option>
                    <option value="Izin">Izin</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="4" placeholder="Tuliskan alasan atau catatan tambahan (opsional)"></textarea>
            </div>

            <div class="form-group">
                <label>Bukti (opsional, pdf/jpg/png)</label>
                <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
            </div>

            <div class="btn-row">
                <a class="btn btn-secondary" href="/absensi_web/pegawai_dashboard.php">Batal</a>
                <button class="btn btn-primary" type="submit">Kirim Permintaan</button>
            </div>
        </form>
    </div>
    <script>
        // Close panel without navigation
        (function(){
            var btn = document.getElementById('closeIzinBtn');
            if (btn) btn.addEventListener('click', function(){
                var panel = document.getElementById('izinPanel');
                if (panel) panel.style.display = 'none';
            });
        })();
    </script>
</body>
</html>
