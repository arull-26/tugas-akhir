<?php
require 'auth_check.php';
checkRole(['pegawai']);
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Ambil data POST
    $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : '';
    $foto_data = $_POST['photo_data'];

    // 1. Cek Duplikasi Absensi Masuk
    $q_check = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
    $q_check->bind_param("is", $user_id, $today);
    $q_check->execute();
    
    if ($q_check->get_result()->num_rows > 0) {
        $q_check->close();
        // Mengarahkan ke dashboard pegawai dengan pesan bahwa sudah absen
        header("Location: /absensi_web/pegawai_dashboard.php?msg=already_in"); exit();
    }
    $q_check->close();
    
    // 2. Validasi Jarak GPS (Validasi Sisi Server)
    $jarak = hitungJarak($latitude, $longitude, LAT_KANTOR, LNG_KANTOR);
    
    if ($jarak > RADIUS_TOLERANSI_METER) {
        // Jika jarak melebihi batas, gagal absen dan kembalikan pesan error lokasi
        header("Location: /absensi_web/pegawai_dashboard.php?msg=location_error"); 
        exit();
    }

    // 3. Tentukan Status (Hadir/Terlambat)
    $status = ($current_time <= JAM_MASUK_STANDAR) ? 'Hadir' : 'Terlambat';
    
    // 4. Simpan Foto Selfie
    // Pastikan folder 'uploads/' sudah ada dan bisa ditulis (writable)
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    // Format nama file: masuk_IDUSER_TIMESTAMP.png
    $filename = 'masuk_' . $user_id . '_' . time() . '.png';
    // Membersihkan header data URI sebelum decode
    $data_to_save = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_data));
    
    // Cek apakah data foto valid sebelum menyimpan
    if ($data_to_save === false) {
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error_foto"); exit();
    }

    file_put_contents(__DIR__ . '/uploads/' . $filename, $data_to_save);

    // 5. Insert ke Database (Pastikan kolom foto_masuk, lat_masuk, lng_masuk, dan status ada!)
    $stmt = $conn->prepare("INSERT INTO absensi (user_id, tanggal, jam_masuk, foto_masuk, lat_masuk, lng_masuk, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('Prepare failed (absen_masuk): ' . $conn->error);
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error"); exit();
    }

    // Pastikan latitude/longitude bertipe numerik untuk binding 'd'
    $lat_val = is_numeric($latitude) ? (float)$latitude : null;
    $lng_val = is_numeric($longitude) ? (float)$longitude : null;

    // Tipe binding: integer, string, string, string (filename), double, double, string (status) -> 'isssdds'
    $stmt->bind_param("isssdds", $user_id, $today, $current_time, $filename, $lat_val, $lng_val, $status);
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: /absensi_web/pegawai_dashboard.php?msg=success");
    } else {
        // Log atau tampilkan error database
        error_log("Database Error Absen Masuk: " . $stmt->error);
        $stmt->close();
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error");
    }
    exit();
} else {
    // Akses langsung ke skrip ini tanpa POST
    header("Location: /absensi_web/pegawai_dashboard.php");
    exit();
}