<?php
// Konfigurasi global aplikasi
date_default_timezone_set('Asia/Jakarta');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Database (sesuaikan jika berbeda)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'db_absensi';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Default lokasi kantor dan aturan absensi (dapat di-override sebelum include jika perlu)
if (!defined('LAT_KANTOR')) define('LAT_KANTOR', -6.733900);
if (!defined('LNG_KANTOR')) define('LNG_KANTOR', 108.464600);
if (!defined('RADIUS_TOLERANSI_METER')) define('RADIUS_TOLERANSI_METER', 100);
if (!defined('JAM_MASUK_STANDAR')) define('JAM_MASUK_STANDAR', '09:00:00');

// Fungsi utilitas: hitung jarak (Haversine) — hanya definisikan jika belum ada
if (!function_exists('hitungJarak')) {
    function hitungJarak($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth_radius * $c;
    }
}

// Siapkan konstanta/variabel lain yang umum jika diperlukan di masa depan
?>