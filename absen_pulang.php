<?php
require 'auth_check.php';
checkRole(['pegawai']);
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $current_time = date('H:i:s');

    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : '';
    $foto_data = isset($_POST['photo_data']) ? $_POST['photo_data'] : '';

    // 1. Cek Absensi Masuk yang belum pulang
    $q_check = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ? AND jam_pulang IS NULL");
    if (!$q_check) {
        error_log('Prepare failed (q_check pulang): ' . $conn->error);
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error"); exit();
    }
    $q_check->bind_param("is", $user_id, $today);
    $q_check->execute();
    $result = $q_check->get_result();

    if ($result->num_rows === 0) {
        header("Location: /absensi_web/pegawai_dashboard.php?msg=not_eligible_pulang"); exit();
    }

    $absensi_row = $result->fetch_assoc();
    $absensi_id = $absensi_row['id'];
    $q_check->close();

    // 2. Validasi Jarak GPS (Sama seperti masuk)
    $jarak = hitungJarak($lat, $lng, LAT_KANTOR, LNG_KANTOR);
    if ($jarak > RADIUS_TOLERANSI_METER) {
        // Jika ingin mengaktifkan batas jarak untuk pulang, uncomment redirect
        // header("Location: /absensi_web/pegawai_dashboard.php?msg=out_of_range_pulang"); exit();
    }

    // 3. Simpan Foto Selfie (pastikan uploads ada)
    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0777, true);
    }
    $filename = 'pulang_' . $user_id . '_' . time() . '.png';
    $data_to_save = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_data));
    if ($data_to_save === false) {
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error_foto"); exit();
    }
    file_put_contents(__DIR__ . '/uploads/' . $filename, $data_to_save);

    // 4. Update Database
    $stmt = $conn->prepare("UPDATE absensi SET jam_pulang = ?, foto_pulang = ?, lat_pulang = ?, lng_pulang = ? WHERE id = ?");
    if (!$stmt) {
        error_log('Prepare failed (absen_pulang): ' . $conn->error);
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error"); exit();
    }

    // Pastikan numeric
    $lat_val = is_numeric($lat) ? (float)$lat : null;
    $lng_val = is_numeric($lng) ? (float)$lng : null;

    // Bind: jam_pulang (s), foto_pulang (s), lat_pulang (d), lng_pulang (d), id (i)
    $stmt->bind_param("ssddi", $current_time, $filename, $lat_val, $lng_val, $absensi_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: /absensi_web/pegawai_dashboard.php?msg=success_out");
    } else {
        error_log('Execute failed (absen_pulang): ' . $stmt->error);
        $stmt->close();
        header("Location: /absensi_web/pegawai_dashboard.php?msg=error");
    }
    exit();
} else {
    header("Location: /absensi_web/pegawai_dashboard.php");
    exit();
}