<?php
// File: admin_approval_izin.php
require 'auth_check.php';
checkRole(['admin']);
require 'config.php';

$message = $_GET['msg'] ?? '';

// --- LOGIKA APPROVE/REJECT ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] == 'approve') ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE izin SET status = ? WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Permintaan izin berhasil diubah menjadi " . $new_status . ".";
    } else {
        $message = "Gagal mengubah status izin atau izin sudah diproses.";
    }
    header("Location: admin_approval_izin.php?msg=" . urlencode($message));
    exit();
}

// --- AMBIL DATA IZIN PENDING ---
$query = "
    SELECT i.*, u.nama, u.username 
    FROM izin i
    JOIN users u ON i.user_id = u.id
    ORDER BY i.created_at DESC
";
$izin_data = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Izin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pending { background-color: #fff3cd; color: #856404; }
        .approved { background-color: #d4edda; color: #155724; }
        .rejected { background-color: #f8d7da; color: #721c24; }
        .status-badge { padding: 5px 10px; border-radius: 5px; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="content">
        <div class="navbar"><h2>Approval Izin & Cuti</h2></div>
        
        <h1>Daftar Permintaan Izin</h1>

        <?php if (!empty($message)): ?>
            <div class="alert" style="background-color: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $izin_data->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama']) ?> (<?= htmlspecialchars($row['username']) ?>)</td>
                        <td><?= $row['jenis_izin'] ?></td>
                        <td><?= $row['tanggal_mulai'] ?> s/d <?= $row['tanggal_akhir'] ?></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td><span class="status-badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <a href="?action=approve&id=<?= $row['id'] ?>" class="action-btn" style="background-color: #28a745; color: white;">Approve</a>
                                <a href="?action=reject&id=<?= $row['id'] ?>" class="action-btn" style="background-color: #dc3545; color: white;">Reject</a>
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
</body>
</html>