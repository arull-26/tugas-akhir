<?php
// File: admin_karyawan.php
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

$message = '';

// --- LOGIC CRUD ---

// 1. TAMBAH Karyawan via modal popup (role pegawai)
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nip = trim($_POST['nip'] ?? null);
    $password_raw = $_POST['password'] ?? '';
    $lokasi_id = isset($_POST['lokasi_id']) && is_numeric($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : null;
    $role = isset($_POST['role']) && in_array($_POST['role'], ['admin','pegawai']) ? $_POST['role'] : 'pegawai';

    if (empty($nama) || empty($username)) {
         $message = "Nama dan Username harus diisi!";
    } else {
        // Jika password tidak diisi, pakai default "123456"
        if (empty($password_raw)) $password_raw = '123456';
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

        // Prepare insert; gunakan query berbeda jika lokasi dipilih atau tidak
        if ($lokasi_id === null) {
            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role, nip) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                $message = "Gagal menyiapkan query: " . $conn->error;
            } else {
                $stmt->bind_param('sssss', $nama, $username, $password_hashed, $role, $nip);
                if ($stmt->execute()) {
                    $message = "Karyawan $nama berhasil ditambahkan! Password: $password_raw.";
                } else {
                    if ($conn->errno == 1062) {
                         $message = "Gagal menambah karyawan. Username sudah digunakan.";
                    } else {
                         $message = "Gagal menambah karyawan. Terjadi kesalahan database: " . $conn->error;
                    }
                }
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role, nip, lokasi_id) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $message = "Gagal menyiapkan query: " . $conn->error;
            } else {
                $stmt->bind_param('sssssi', $nama, $username, $password_hashed, $role, $nip, $lokasi_id);
                if ($stmt->execute()) {
                    $message = "Karyawan $nama berhasil ditambahkan! Password: $password_raw.";
                } else {
                    if ($conn->errno == 1062) {
                         $message = "Gagal menambah karyawan. Username sudah digunakan.";
                    } else {
                         $message = "Gagal menambah karyawan. Terjadi kesalahan database: " . $conn->error;
                    }
                }
            }
        }
    }

    header("Location: admin_karyawan.php?msg=" . urlencode($message));
    exit();
}

// 2. EDIT Karyawan (via modal popup)
if (isset($_POST['edit'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nip = trim($_POST['nip'] ?? null);
    $lokasi_id = isset($_POST['lokasi_id']) && is_numeric($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : null;
    $new_password = $_POST['password'] ?? '';
    $role = isset($_POST['role']) && in_array($_POST['role'], ['admin','pegawai']) ? $_POST['role'] : 'pegawai';

    if ($id <= 0 || empty($nama) || empty($username)) {
        $message = 'ID, Nama dan Username harus diisi untuk edit.';
    } else {
        // Prevent demoting self from admin to pegawai
        if ($id == $_SESSION['user_id'] && $role !== 'admin') {
            $message = 'Anda tidak bisa mengubah peran diri sendiri menjadi non-admin.';
            header('Location: admin_karyawan.php?msg=' . urlencode($message)); exit();
        }

        if (!empty($new_password)) {
            $pw_hash = password_hash($new_password, PASSWORD_DEFAULT);
            if ($lokasi_id === null) {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, nip = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param('sssssi', $nama, $username, $nip, $role, $pw_hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, nip = ?, lokasi_id = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param('sssissi', $nama, $username, $nip, $lokasi_id, $role, $pw_hash, $id);
            }
        } else {
            if ($lokasi_id === null) {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, nip = ?, role = ? WHERE id = ?");
                $stmt->bind_param('ssssi', $nama, $username, $nip, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, username = ?, nip = ?, lokasi_id = ?, role = ? WHERE id = ?");
                $stmt->bind_param('sssisi', $nama, $username, $nip, $lokasi_id, $role, $id);
            }
        }

        if ($stmt && $stmt->execute()) {
            $message = "Data karyawan berhasil diperbarui.";
        } else {
            $message = "Gagal memperbarui karyawan: " . ($stmt ? $stmt->error : $conn->error);
        }
    }
    header('Location: admin_karyawan.php?msg=' . urlencode($message)); exit();
}

// 3. HAPUS Karyawan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'pegawai'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Karyawan berhasil dihapus.";
    } else {
        $message = "Gagal menghapus karyawan.";
    }
    header("Location: admin_karyawan.php?msg=" . urlencode($message));
    exit();
}

// 3. RESET Password Karyawan (Setel ulang ke default "123456")
if (isset($_GET['action']) && $_GET['action'] == 'reset' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $password_hashed = password_hash("123456", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'pegawai'");
    $stmt->bind_param("si", $password_hashed, $id);
    if ($stmt->execute()) {
        $message = "Password berhasil direset ke default '123456'.";
    } else {
        $message = "Gagal mereset password.";
    }
    header("Location: admin_karyawan.php?msg=" . urlencode($message));
    exit();
}

// AMBIL DATA PENGGUNA (tampilkan semua role agar admin dapat melihat dan mengatur akses)
$data_karyawan = $conn->query("SELECT id, nama, username, role, nip, lokasi_id FROM users ORDER BY id DESC");
// Ambil daftar lokasi kantor untuk dipilih saat tambah karyawan
$lokasi_list = $conn->query("SELECT id, nama_lokasi FROM lokasi_kantor ORDER BY nama_lokasi ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Karyawan</title>
    <link rel="stylesheet" href="/absensi_web/assets/css/style.css">
    <style> 
        .action-btn { margin-right: 5px; text-decoration: none; padding: 5px 10px; border-radius: 5px; } 
        .table-container { overflow-x: auto; } 
        .table th, .table td { padding: 12px 15px; text-align: left; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="content">
        <div class="navbar"><h2>Manajemen Karyawan</h2></div>
        
        <h1>Data Karyawan</h1>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="card" style="background-color: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 20px;">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Tambah Karyawan Baru</h3>
            <p>
                <button id="openTambahBtn" class="btn btn-primary">Tambah Karyawan </button>
            </p>

            <!-- Modal Tambah Karyawan -->
            <div id="tambahModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
                <div style="background:#fff; max-width:600px; margin:60px auto; padding:20px; border-radius:8px; position:relative;">
                    <button id="closeTambah" style="position:absolute; right:12px; top:12px; background:transparent; border:0; font-size:18px;">✕</button>
                    <h3>Form Tambah Karyawan</h3>
                    <form method="POST">
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <input type="text" name="nama" placeholder="Nama Lengkap" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                            <input type="text" name="nip" placeholder="NIP / NIK (opsional)" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                            <input type="text" name="username" placeholder="Username (Unique)" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                            <input type="password" name="password" placeholder="Password (biarkan kosong untuk default)" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Pilih Lokasi Kantor</label>
                            <select name="lokasi_id" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;">
                                <option value="">-- Tidak spesifik --</option>
                                <?php while($lok = $lokasi_list->fetch_assoc()): ?>
                                    <option value="<?= $lok['id'] ?>"><?= htmlspecialchars($lok['nama_lokasi']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="margin-top:10px;">
                            <label>Role Akses</label>
                            <select name="role" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;">
                                <option value="pegawai">Pegawai</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div style="margin-top:12px; text-align:right;">
                            <button type="button" id="cancelTambah" class="btn btn-secondary">Batal</button>
                            <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Daftar Pengguna</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $data_karyawan->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $row['role'] ?></td>
                            <td>
                                <button class="action-btn edit-btn" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>" 
                                    data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>" 
                                    data-nip="<?= htmlspecialchars($row['nip'] ?? '', ENT_QUOTES) ?>" 
                                    data-lokasi="<?= $row['lokasi_id'] ?? '' ?>"
                                    data-role="<?= $row['role'] ?>"
                                    style="background-color: #ffc107; color: #333;">Edit</button>
                                <a href="?action=reset&id=<?= $row['id'] ?>" onclick="return confirm('Reset password <?= htmlspecialchars($row['username']) ?> ke 123456?')" class="action-btn" style="background-color: #007bff; color: white;">Reset PW</a>
                                <?php if ($row['role'] !== 'admin'): ?>
                                    <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus karyawan <?= htmlspecialchars($row['username']) ?>?')" class="action-btn" style="background-color: #dc3545; color: white;">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Modal Edit Karyawan -->
        <div id="editModal" style="display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
            <div style="background:#fff; max-width:600px; margin:60px auto; padding:20px; border-radius:8px; position:relative;">
                <button id="closeEdit" style="position:absolute; right:12px; top:12px; background:transparent; border:0; font-size:18px;">✕</button>
                <h3>Edit Karyawan</h3>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" name="nama" id="edit_nama" placeholder="Nama Lengkap" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                        <input type="text" name="nip" id="edit_nip" placeholder="NIP / NIK" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                        <input type="text" name="username" id="edit_username" placeholder="Username (Unique)" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                        <input type="password" name="password" id="edit_password" placeholder="Password baru (biarkan kosong untuk tidak mengubah)" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:5px;">
                    </div>
                    <div style="margin-top:10px;">
                        <label>Pilih Lokasi Kantor</label>
                        <select name="lokasi_id" id="edit_lokasi" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;">
                            <option value="">-- Tidak spesifik --</option>
                            <?php // reset pointer if already used earlier
                                $lokasi_list->data_seek(0);
                                while($lok = $lokasi_list->fetch_assoc()): ?>
                                <option value="<?= $lok['id'] ?>"><?= htmlspecialchars($lok['nama_lokasi']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div style="margin-top:10px;">
                        <label>Role Akses</label>
                        <select name="role" id="edit_role" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:5px;">
                            <option value="pegawai">Pegawai</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div style="margin-top:12px; text-align:right;">
                        <button type="button" id="cancelEdit" class="btn btn-secondary">Batal</button>
                        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="/absensi_web/assets/js/script.js"></script>
    <script>
        // Modal behavior for tambah karyawan
        (function(){
            var openBtn = document.getElementById('openTambahBtn');
            var modal = document.getElementById('tambahModal');
            var closeBtn = document.getElementById('closeTambah');
            var cancelBtn = document.getElementById('cancelTambah');
            if (openBtn && modal) {
                openBtn.addEventListener('click', function(){ modal.style.display = 'block'; });
                if (closeBtn) closeBtn.addEventListener('click', function(){ modal.style.display = 'none'; });
                if (cancelBtn) cancelBtn.addEventListener('click', function(){ modal.style.display = 'none'; });
                window.addEventListener('click', function(e){ if(e.target === modal) modal.style.display = 'none'; });
            }
        })();

        // Modal behavior for edit karyawan and filling data
        (function(){
            var editModal = document.getElementById('editModal');
            var closeEdit = document.getElementById('closeEdit');
            var cancelEdit = document.getElementById('cancelEdit');
            var editButtons = document.querySelectorAll('.edit-btn');
            if (!editModal) return;

            function openEdit(data){
                document.getElementById('edit_id').value = data.id || '';
                document.getElementById('edit_nama').value = data.nama || '';
                document.getElementById('edit_username').value = data.username || '';
                document.getElementById('edit_nip').value = data.nip || '';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_lokasi').value = data.lokasi || '';
                if (document.getElementById('edit_role')) document.getElementById('edit_role').value = data.role || 'pegawai';
                editModal.style.display = 'block';
            }

            editButtons.forEach(function(btn){
                btn.addEventListener('click', function(){
                    var data = {
                        id: this.getAttribute('data-id'),
                        nama: this.getAttribute('data-nama'),
                        username: this.getAttribute('data-username'),
                        nip: this.getAttribute('data-nip'),
                        lokasi: this.getAttribute('data-lokasi')
                    };
                    openEdit(data);
                });
            });

            if (closeEdit) closeEdit.addEventListener('click', function(){ editModal.style.display = 'none'; });
            if (cancelEdit) cancelEdit.addEventListener('click', function(){ editModal.style.display = 'none'; });
            window.addEventListener('click', function(e){ if(e.target === editModal) editModal.style.display = 'none'; });
        })();
    </script>
</body>
</html>