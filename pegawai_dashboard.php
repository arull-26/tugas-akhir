<?php
require 'auth_check.php';
checkRole(['pegawai']);
require 'config.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Cek status absensi hari ini
$absensi_q = $conn->prepare("SELECT jam_masuk, jam_pulang FROM absensi WHERE user_id = ? AND tanggal = ?");
$absensi_q->bind_param("is", $user_id, $today);
$absensi_q->execute();
$absensi_result = $absensi_q->get_result();
$data_absensi = $absensi_result->fetch_assoc();

$sudah_masuk = !empty($data_absensi['jam_masuk']);
$sudah_pulang = !empty($data_absensi['jam_pulang']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan</title>
    <link rel="stylesheet" href="/absensi_web/assets/css/style.css">
    <style>
        /* Floating izin button bottom-right */
        .fixed-izin-btn{
            position:fixed;
            right:20px;
            bottom:20px;
            z-index:9999;
            padding:12px 16px;
            border-radius:50px;
            background:#28a745;
            color:#fff;
            border:none;
            box-shadow:0 8px 20px rgba(0,0,0,0.12);
            text-decoration:none;
            font-weight:600;
        }
        @media (max-width:520px){ .fixed-izin-btn{ right:12px; bottom:12px; padding:10px 12px } }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Hi, <?= $_SESSION['nama'] ?>!</h2>
        <a href="/absensi_web/logout.php" class="btn btn-primary">Logout</a>
    </div>
    
    <div class="content">
        <h1>Dashboard Absensi</h1>
        <p id="realtime_clock" style="font-size: 2.5em; font-weight: 700; color: var(--primary-color); margin-bottom: 20px;"><?= date('H:i:s') ?></p>
        
        <?php if (isset($_GET['msg'])): 
            $msg = $_GET['msg'];
            $alert_map = [
                'success' => ['bg'=>'#d4edda','color'=>'#155724','text'=>'Absen masuk berhasil!'],
                'success_out' => ['bg'=>'#d1ecf1','color'=>'#0c5460','text'=>'Absen pulang berhasil!'],
                'already_in' => ['bg'=>'#fff3cd','color'=>'#856404','text'=>'Anda sudah absen masuk hari ini.'],
                'location_error' => ['bg'=>'#f8d7da','color'=>'#721c24','text'=>'Gagal absen: lokasi diluar area kantor.'],
                'not_eligible_pulang' => ['bg'=>'#fff3cd','color'=>'#856404','text'=>'Tidak dapat absen pulang: belum melakukan absen masuk atau sudah pulang.'],
                'error_foto' => ['bg'=>'#f8d7da','color'=>'#721c24','text'=>'Format foto tidak valid atau gagal mengunggah.'],
                'error' => ['bg'=>'#f8d7da','color'=>'#721c24','text'=>'Terjadi kesalahan. Silakan ulangi dan periksa koneksi.']
            ];
            $alert = isset($alert_map[$msg]) ? $alert_map[$msg] : ['bg'=>'#d1ecf1','color'=>'#0c5460','text'=>htmlspecialchars($msg)];
        ?>
            <div class="card" style="background-color: <?= $alert['bg'] ?>; color: <?= $alert['color'] ?>; border-color: rgba(0,0,0,0.05); padding: 12px; margin-bottom: 12px; position:fixed; left:50%; top:20px; transform:translateX(-50%); z-index:9999; width:400px; max-width:90%; text-align:center; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.12);">
                <?= $alert['text'] ?>
            </div>
        <?php endif; ?>

        <div class="row" style="display: flex; gap: 20px;">
            <div class="card" style="flex: 1;">
                <h3>Absen Masuk (Check-in)</h3>
                <?php if (!$sudah_masuk): ?>
                    <form id="absensi_masuk_form" method="POST" action="/absensi_web/absen_masuk.php" enctype="multipart/form-data">
                        <div style="text-align: center; margin: 15px 0;">
                            <video id="webcam_video" width="320" height="240" autoplay style="border: 1px solid #ddd; border-radius: 8px;"></video>
                            <canvas id="webcam_canvas" style="display: none;"></canvas>
                        </div>
                        <p id="gps_status" style="color: red; text-align: center; margin-bottom: 10px;">Memuat GPS...</p>
                        <input type="hidden" name="latitude" id="lat">
                        <input type="hidden" name="longitude" id="lng">
                        <input type="hidden" name="photo_data" id="photo_data">
                        <button type="button" class="btn btn-primary" onclick="takePhotoAndSubmit('absensi_masuk_form')">ABSEN MASUK</button>
                    </form>
                <?php else: ?>
                    <p style="color: green; font-weight: bold;">Anda sudah Absen Masuk hari ini pada pukul: <?= $data_absensi['jam_masuk'] ?></p>
                <?php endif; ?>
            </div>

            <!-- izin button moved to fixed bottom-right -->

            <div class="card" style="flex: 1;">
                <h3>Absen Pulang (Check-out)</h3>
                <?php if ($sudah_masuk && !$sudah_pulang): ?>
                    <form id="absensi_pulang_form" method="POST" action="/absensi_web/absen_pulang.php" enctype="multipart/form-data">
                        <div style="text-align: center; margin: 15px 0;">
                            <video id="webcam_video_pulang" width="320" height="240" autoplay style="border: 1px solid #ddd; border-radius: 8px;"></video>
                            <canvas id="webcam_canvas_pulang" style="display: none;"></canvas>
                        </div>
                        <p id="gps_status_pulang" style="color: red; text-align: center; margin-bottom: 10px;">Memuat GPS...</p>
                        <input type="hidden" name="latitude" id="lat_pulang">
                        <input type="hidden" name="longitude" id="lng_pulang">
                        <input type="hidden" name="photo_data" id="photo_data_pulang">
                        <button type="button" class="btn btn-primary" onclick="takePhotoAndSubmit('absensi_pulang_form')">ABSEN PULANG</button>
                    </form>
                    <script>
                        // Inisialisasi Absensi Pulang (Perlu inisialisasi ulang dengan ID unik jika ada di halaman yang sama)
                        document.addEventListener('DOMContentLoaded', () => {
                            if (document.getElementById('webcam_video_pulang')) {
                                // Re-implementasi sederhana untuk form pulang
                                navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } }).then((stream) => {
                                    document.getElementById('webcam_video_pulang').srcObject = stream;
                                });
                                
                                navigator.geolocation.getCurrentPosition((pos) => {
                                    document.getElementById('lat_pulang').value = pos.coords.latitude;
                                    document.getElementById('lng_pulang').value = pos.coords.longitude;
                                    document.getElementById('gps_status_pulang').innerText = 'Lokasi ditemukan!';
                                }, (err) => {
                                    document.getElementById('gps_status_pulang').innerText = 'Gagal akses lokasi!';
                                });
                                
                                window.takePhotoAndSubmit_Pulang = function() {
                                    const video = document.getElementById('webcam_video_pulang');
                                    const canvas = document.getElementById('webcam_canvas_pulang');
                                    canvas.width = video.videoWidth;
                                    canvas.height = video.videoHeight;
                                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                                    document.getElementById('photo_data_pulang').value = canvas.toDataURL('image/png');
                                    document.getElementById('absensi_pulang_form').submit();
                                }
                                
                                // Ganti fungsi onclick pada tombol
                                const buttonPulang = document.querySelector('#absensi_pulang_form button');
                                buttonPulang.onclick = takePhotoAndSubmit_Pulang;
                            }
                        });
                    </script>
                <?php elseif ($sudah_pulang): ?>
                    <p style="color: #007bff; font-weight: bold;">Anda sudah Absen Pulang pada pukul: <?= $data_absensi['jam_pulang'] ?></p>
                <?php else: ?>
                    <p style="color: orange; font-weight: bold;">Anda harus Absen Masuk terlebih dahulu.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php include 'riwayat.php'; ?>
    </div>
    
    <script>
        // Konstanta lokasi kantor dari server (digunakan untuk debug jarak)
        const LAT_KANTOR = <?= defined('LAT_KANTOR') ? LAT_KANTOR : -6.2 ?>;
        const LNG_KANTOR = <?= defined('LNG_KANTOR') ? LNG_KANTOR : 106.8 ?>;
        const RADIUS_TOLERANSI_METER = <?= defined('RADIUS_TOLERANSI_METER') ? RADIUS_TOLERANSI_METER : 100 ?>;
    </script>
    <script src="/absensi_web/assets/js/script.js"></script>
    <a href="/absensi_web/izin_sakit.php" class="fixed-izin-btn">Ajukan Izin / Sakit</a>
</body>
</html>