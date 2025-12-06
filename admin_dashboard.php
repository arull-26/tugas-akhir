<?php
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';
// Logic untuk mengambil data statistik, misal:
$total_karyawan = $conn->query("SELECT COUNT(id) FROM users WHERE role='pegawai'")->fetch_row()[0];
// Ambil data untuk Chart.js (perlu query kompleks, disederhanakan di sini)
$data_chart = [
    'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'],
    'hadir' => [20, 22, 18, 25, 23],
    'terlambat' => [2, 1, 4, 0, 2]
];

// Statistik hari ini: hadir, terlambat, izin pending
$today = date('Y-m-d');
$hadir_today = 0;
$terlambat_today = 0;
$izin_pending = 0;
// hadir hari ini (jam_masuk not null)
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = ? AND jam_masuk IS NOT NULL")) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $stmt->bind_result($hadir_today);
    $stmt->fetch();
    $stmt->close();
}
// terlambat (jam_masuk > standar)
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = ? AND jam_masuk > ?")) {
    $standar = defined('JAM_MASUK_STANDAR') ? JAM_MASUK_STANDAR : '09:00:00';
    $stmt->bind_param('ss', $today, $standar);
    $stmt->execute();
    $stmt->bind_result($terlambat_today);
    $stmt->fetch();
    $stmt->close();
}
// izin sakit pending
if ($res = $conn->query("SELECT COUNT(*) FROM izin WHERE status = 'Pending'")) {
    $izin_pending = $res->fetch_row()[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="/absensi_web/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <div class="content">
        <div class="navbar">
            <h2>Selamat Datang, Admin!</h2>
            <button onclick="toggleSidebar()" class="btn btn-primary" style="display: none;">Menu</button>
        </div>
        
        <h1>Dashboard Administrasi</h1>

        <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap:wrap;">
            <div class="card" style="flex: 1; min-width:180px; text-align: center;">
                <h4>Total Karyawan</h4>
                <p style="font-size: 2em; color: var(--secondary-color); font-weight: 700;"><?= $total_karyawan ?></p>
            </div>
            <div class="card" style="flex: 1; min-width:180px; text-align: center;">
                <h4>Hadir Hari Ini</h4>
                <p style="font-size: 2em; color: #28a745; font-weight: 700;"><?= intval($hadir_today) ?></p>
            </div>
            <div class="card" style="flex: 1; min-width:180px; text-align: center;">
                <h4>Terlambat Hari Ini</h4>
                <p style="font-size: 2em; color: #ffc107; font-weight: 700;"><?= intval($terlambat_today) ?></p>
            </div>
            <div class="card" style="flex: 1; min-width:180px; text-align: center;">
                <h4>Izin / Sakit (Pending)</h4>
                <p style="font-size: 2em; color: #17a2b8; font-weight: 700;"><?= intval($izin_pending) ?></p>
            </div>
        </div>

        <div class="card">
            <h3>Grafik Kehadiran Mingguan</h3>
            <canvas id="attendanceChart" height="100"></canvas>
        </div>

    </div>

    <script src="/absensi_web/assets/js/script.js"></script>
    <script>
        // Chart.js implementation
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($data_chart['labels']) ?>,
                datasets: [
                    {
                        label: 'Hadir',
                        data: <?= json_encode($data_chart['hadir']) ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true
                    },
                    {
                        label: 'Terlambat',
                        data: <?= json_encode($data_chart['terlambat']) ?>,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>