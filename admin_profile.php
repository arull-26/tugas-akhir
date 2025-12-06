<?php
// Pastikan file ini ada di root /absensi/
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

$message = '';
$user_id = $_SESSION['user_id'];

// Ambil data user saat ini
$stmt_user = $conn->prepare("SELECT nama, username, nip FROM users WHERE id = ?");
$stmt_user->bind_param('i', $user_id);
$stmt_user->execute();
$user_res = $stmt_user->get_result();
$current_user = $user_res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ubah_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Konfirmasi password baru tidak cocok.";
    } else {
        // 1. Cek Password Lama
        $stmt_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($old_password, $user['password'])) {
            // 2. Update Password Baru
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_password_hashed, $user_id);
            
            if ($stmt_update->execute()) {
                $message = "Password berhasil diubah!";
            } else {
                $message = "Gagal mengubah password. Silakan coba lagi.";
            }
        } else {
            $message = "Password lama salah.";
        }
    }
}

// Update profile (nama, username, nip)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nip = trim($_POST['nip'] ?? null);

    if (empty($nama) || empty($username)) {
        $message = 'Nama dan Username harus diisi.';
    } else {
        // cek apakah username sudah dipakai oleh user lain
        $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
        $chk->bind_param('si', $username, $user_id);
        $chk->execute();
        $chk_res = $chk->get_result();
        if ($chk_res && $chk_res->num_rows > 0) {
            $message = 'Username sudah digunakan oleh user lain.';
        } else {
            $upd = $conn->prepare("UPDATE users SET nama = ?, username = ?, nip = ? WHERE id = ?");
            $upd->bind_param('sssi', $nama, $username, $nip, $user_id);
            if ($upd->execute()) {
                $message = 'Profil berhasil diperbarui.';
                $_SESSION['nama'] = $nama; // update session display name
                // refresh current_user values for form
                $current_user['nama'] = $nama;
                $current_user['username'] = $username;
                $current_user['nip'] = $nip;
            } else {
                $message = 'Gagal memperbarui profil: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; // Anggap ada file sidebar ?>

    <div class="content">
        <div class="navbar"><h2>Profil Admin</h2></div>
        
        <h1>Manajemen Profil & Password</h1>
        <?php if (!empty($message)): ?>
            <div class="card" style="max-width: 700px;">
                <p style="color: <?= (strpos($message, 'berhasil') !== false) ? 'green' : 'red' ?>; margin-bottom: 15px;"><?= $message ?></p>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 700px; display:flex; gap:20px; flex-wrap:wrap;">
            <div style="flex:1; min-width:300px;">
                <h3>Ubah Profil</h3>
                <form method="POST">
                    <div style="margin-bottom:10px;"><label>Nama Lengkap</label><input type="text" name="nama" value="<?= htmlspecialchars($current_user['nama'] ?? '') ?>" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;"></div>
                    <div style="margin-bottom:10px;"><label>NIP / NIK</label><input type="text" name="nip" value="<?= htmlspecialchars($current_user['nip'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;"></div>
                    <div style="margin-bottom:10px;"><label>Username</label><input type="text" name="username" value="<?= htmlspecialchars($current_user['username'] ?? '') ?>" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;"></div>
                    <div style="text-align:right;"><button type="submit" name="update_profile" class="btn btn-primary">Simpan Profil</button></div>
                </form>
            </div>

            <div style="flex:1; min-width:300px;">
                <h3>Ubah Password (Opsional)</h3>
                <p>Tekan tombol di bawah untuk menampilkan form ganti password.</p>
                <button id="togglePasswordBtn" type="button" class="btn btn-secondary" style="margin-bottom:12px;">Ganti Password</button>

                <div id="passwordPanel" style="display:none; border-top:1px solid #eee; padding-top:12px;">
                    <form method="POST" id="passwordForm">
                        <div style="margin-bottom: 15px;"><label>Password Lama:</label><input type="password" name="old_password" id="old_password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;"></div>
                        <div style="margin-bottom: 15px;"><label>Password Baru:</label><input type="password" name="new_password" id="new_password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;"></div>
                        <div style="margin-bottom: 15px;"><label>Konfirmasi Password Baru:</label><input type="password" name="confirm_password" id="confirm_password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;"></div>
                        <div style="text-align:right;"><button type="submit" name="ubah_password" class="btn btn-primary">Ubah Password</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/absensi_web/assets/js/script.js"></script>
    <script>
        // Toggle password panel and set required attributes dynamically
        (function(){
            var btn = document.getElementById('togglePasswordBtn');
            var panel = document.getElementById('passwordPanel');
            var oldPw = document.getElementById('old_password');
            var newPw = document.getElementById('new_password');
            var confPw = document.getElementById('confirm_password');
            if (!btn || !panel) return;
            btn.addEventListener('click', function(){
                if (panel.style.display === 'none' || panel.style.display === '') {
                    panel.style.display = 'block';
                    // set required only when visible
                    if (oldPw) oldPw.required = true;
                    if (newPw) newPw.required = true;
                    if (confPw) confPw.required = true;
                    btn.innerText = 'Batal Ganti Password';
                    btn.classList.remove('btn-secondary'); btn.classList.add('btn-danger');
                } else {
                    panel.style.display = 'none';
                    if (oldPw) oldPw.required = false; oldPw.value = '';
                    if (newPw) newPw.required = false; newPw.value = '';
                    if (confPw) confPw.required = false; confPw.value = '';
                    btn.innerText = 'Ganti Password';
                    btn.classList.remove('btn-danger'); btn.classList.add('btn-secondary');
                }
            });
        })();
    </script>
</body>
</html>