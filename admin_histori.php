<?php
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

// Recent absensi (gabungkan jam_masuk/jam_pulang sebagai event)
$absensi_q = $conn->prepare("SELECT a.id, a.user_id, u.nama, a.tanggal, a.jam_masuk, a.jam_pulang FROM absensi a JOIN users u ON a.user_id = u.id ORDER BY a.tanggal DESC, a.jam_masuk DESC LIMIT 50");
$absensi_q->execute();
$absensi_res = $absensi_q->get_result();

$izin_q = $conn->prepare("SELECT i.id, i.user_id, u.nama, i.jenis_izin, i.tanggal_mulai, i.tanggal_akhir, i.keterangan, i.status, i.created_at FROM izin i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 50");
$izin_q->execute();
$izin_res = $izin_q->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Histori Aktivitas - Admin</title>
    <link rel="stylesheet" href="/absensi_web/assets/css/style.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="content">
        <div class="navbar"><h2>Histori Aktivitas</h2></div>

        <div class="card">
            <h3>Absensi Terbaru</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Nama</th><th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th></tr>
                    </thead>
                    <tbody>
                        <?php while($r = $absensi_res->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td><?= htmlspecialchars($r['tanggal']) ?></td>
                            <td><?= htmlspecialchars($r['jam_masuk']) ?></td>
                            <td><?= htmlspecialchars($r['jam_pulang']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Izin / Sakit Terbaru</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Nama</th><th>Jenis</th><th>Tanggal Mulai</th><th>Tanggal Akhir</th><th>Status</th><th>Diajukan</th></tr>
                    </thead>
                    <tbody>
                        <?php while($z = $izin_res->fetch_assoc()): ?>
                        <tr>
                            <td><?= $z['id'] ?></td>
                            <td><?= htmlspecialchars($z['nama']) ?></td>
                            <td><?= htmlspecialchars($z['jenis_izin']) ?></td>
                            <td><?= htmlspecialchars($z['tanggal_mulai']) ?></td>
                            <td><?= htmlspecialchars($z['tanggal_akhir']) ?></td>
                            <td><?= htmlspecialchars($z['status']) ?></td>
                            <td><?= htmlspecialchars($z['created_at']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="/absensi_web/assets/js/script.js"></script>
</body>
</html>
