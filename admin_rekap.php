<?php
// Pastikan file ini ada di root /absensi/
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');
$karyawan_id = $_GET['karyawan'] ?? 'all';

// Logika Export CSV (Jika tombol export diklik)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Query yang sama akan digunakan untuk export
    $sql = "SELECT a.tanggal, u.nama, a.jam_masuk, a.jam_pulang, a.status 
            FROM absensi a JOIN users u ON a.user_id = u.id 
            WHERE a.tanggal BETWEEN ? AND ? ORDER BY a.tanggal DESC";
    
    $stmt_export = $conn->prepare($sql);
    $stmt_export->bind_param("ss", $start_date, $end_date);
    $stmt_export->execute();
    $export_result = $stmt_export->get_result();
    
    // Header untuk CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=rekap_absensi_' . date('Ymd') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Tanggal', 'Nama Karyawan', 'Jam Masuk', 'Jam Pulang', 'Status')); // Header CSV
    
    while ($row = $export_result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Logika Tampilan Data
$sql = "SELECT a.tanggal, u.nama, a.jam_masuk, a.jam_pulang, a.status, a.lat_masuk, a.lng_masuk 
        FROM absensi a JOIN users u ON a.user_id = u.id 
        WHERE a.tanggal BETWEEN ? AND ? ";

if ($karyawan_id != 'all') {
    $sql .= "AND a.user_id = ? ";
}

$sql .= "ORDER BY a.tanggal DESC";

$stmt = $conn->prepare($sql);

if ($karyawan_id != 'all') {
    $stmt->bind_param("ssi", $start_date, $end_date, $karyawan_id);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$rekap_result = $stmt->get_result();

$data_karyawan_list = $conn->query("SELECT id, nama FROM users WHERE role='pegawai'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; // Anggap ada file sidebar ?>

    <div class="content">
        <div class="navbar"><h2>Rekap Absensi</h2></div>
        
        <h1>Rekap Data Kehadiran</h1>
        
        <div class="card">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label>Dari:</label>
                <input type="date" name="start" value="<?= $start_date ?>" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                
                <label>Sampai:</label>
                <input type="date" name="end" value="<?= $end_date ?>" required style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                
                <label>Karyawan:</label>
                <select name="karyawan" style="padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="all">Semua Karyawan</option>
                    <?php while($row = $data_karyawan_list->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($karyawan_id == $row['id']) ? 'selected' : '' ?>>
                            <?= $row['nama'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="?start=<?= $start_date ?>&end=<?= $end_date ?>&karyawan=<?= $karyawan_id ?>&export=csv" class="btn" style="background-color: #28a745; color: white;">Export CSV</a>
                <?php // Export PDF memerlukan library pihak ketiga (misalnya FPDF/DomPDF) ?>
            </form>
        </div>

        <div class="card">
            <h3>Hasil Rekap (<?= $start_date ?> s/d <?= $end_date ?>)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Lokasi Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $rekap_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['tanggal'] ?></td>
                        <td><?= $row['nama'] ?></td>
                        <td><?= $row['jam_masuk'] ?? '-' ?></td>
                        <td><?= $row['jam_pulang'] ?? '-' ?></td>
                        <td><?= $row['status'] ?></td>
                        <td>
                            <?php if($row['lat_masuk']): ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['lat_masuk'] ?>,<?= $row['lng_masuk'] ?>" target="_blank">Lihat Map</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>