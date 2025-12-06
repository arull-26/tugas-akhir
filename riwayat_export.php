<?php
require 'auth_check.php';
checkRole(['pegawai']);
require 'config.php';

$user_id = $_SESSION['user_id'];

$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : '';
$to = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : '';

$where_sql = "user_id = " . intval($user_id);
if ($from) {
    $from_esc = $conn->real_escape_string($from);
    $where_sql .= " AND tanggal >= '" . $from_esc . "'";
}
if ($to) {
    $to_esc = $conn->real_escape_string($to);
    $where_sql .= " AND tanggal <= '" . $to_esc . "'";
}

$q = $conn->query("SELECT tanggal, jam_masuk, jam_pulang, status, latitude, longitude FROM absensi WHERE $where_sql ORDER BY tanggal DESC");

// Output CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=riwayat_absensi_'.date('Ymd').'.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Tanggal','Jam Masuk','Jam Pulang','Status','Latitude','Longitude']);
if ($q && $q->num_rows > 0) {
    while($row = $q->fetch_assoc()) {
        fputcsv($out, [
            $row['tanggal'],
            $row['jam_masuk'],
            $row['jam_pulang'],
            $row['status'],
            $row['latitude'],
            $row['longitude']
        ]);
    }
}
fclose($out);
exit;
